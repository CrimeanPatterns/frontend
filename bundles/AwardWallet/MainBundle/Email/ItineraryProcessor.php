<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\Meta\EmailInfo;
use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\ConfirmationNumber;
use AwardWallet\Schema\Itineraries as Schema;
use AwardWallet\Schema\Itineraries\Person;
use Psr\Log\LoggerInterface;

class ItineraryProcessor
{
    private LoggerInterface $logger;
    private ItinerariesProcessor $processor;
    private RetrieveConfirmationSender $sender;
    private \Memcached $memcached;
    private EmailAttachmentProcessor $emailAttachmentProcessor;

    public function __construct(
        LoggerInterface $logger,
        ItinerariesProcessor $processor,
        RetrieveConfirmationSender $sender,
        \Memcached $memcached,
        EmailAttachmentProcessor $emailAttachmentProcessor
    ) {
        $this->logger = $logger;
        $this->processor = $processor;
        $this->sender = $sender;
        $this->memcached = $memcached;
        $this->emailAttachmentProcessor = $emailAttachmentProcessor;
    }

    public function process(ParseEmailResponse $response, Owner $owner, EmailOptions $emailOptions): bool
    {
        $options = SavingOptions::savingByEmail(
            $owner,
            $emailOptions->messageId,
            $emailOptions->source,
            true,
            $emailOptions->silent,
            $emailOptions->updateOnly,
            isset($response->metadata->receivedDateTime) ? new \DateTimeImmutable($response->metadata->receivedDateTime) : null,
            $response->providerCode
        );
        $report = $this->processor->save($response->itineraries, $options);

        $result = [
            'added' => count($report->getAdded()),
            'updated' => count($report->getUpdated()),
            'removed' => count($report->getRemoved()),
        ];
        $its = array_merge($report->getAdded(), $report->getUpdated());

        if (count($its) === 1) {
            $this->emailAttachmentProcessor->process($its[0], base64_decode($response->email));
        }

        /*
         * retrieve missing info from checkConfirmation
         * price for klm/airfrance
         * cabin for virgin (#19864) and aviancataca (#20067)
         */
        foreach (array_merge($report->getAdded(), $report->getUpdated()) as $itinerary) {
            if ($this->valid($itinerary) && $this->missing($itinerary)) {
                $this->logger->info(sprintf('missing data in trip %s (%s)', $itinerary->getIdString(), $itinerary->getRealProvider()->getCode()));
                $confFields = $this->getConfFields($itinerary, $response);

                if (!empty($confFields)) {
                    $this->logger->info('calling checkConfirmation');
                    $this->sender->send($itinerary->getOwner(), $itinerary->getRealProvider()->getCode(), $confFields['ConfNo'], $confFields);
                } else {
                    $this->logger->info('not enough info for checkConfirmation call');
                }
            }

            $provider = $itinerary->getRealProvider();

            if ($itinerary instanceof Trip
                && isset($provider)
                && $provider->getCode() == 'aa'
                && isset($response->metadata->mailboxId)
                && $response->metadata->mailboxId
                && !$itinerary->getPricingInfo()->getSpentAwards()
            ) {
                $this->logger->info('Save trip information in cache for matching aa award redemption');
                $this->aaCacheMatching($itinerary, $response->metadata);
            }
        }
        $this->logger->info('save itineraries result: ' . json_encode($result), ['withGpt' => $emailOptions->source->isGpt()]);

        return array_sum($result) > 0;
    }

    private function getConfFields(Trip $trip, ParseEmailResponse $response): array
    {
        $tripLocators = array_unique(array_filter([$trip->getConfirmationNumber(), $trip->getIssuingAirlineConfirmationNumber()]));

        if (count($tripLocators) === 0) {
            return [];
        }
        $locator = array_shift($tripLocators);

        foreach ($response->itineraries as $parsed) {
            if (!$parsed instanceof Schema\Flight) {
                continue;
            }
            $parsedLocators =
                array_unique(
                    array_filter(
                        array_merge([$parsed->issuingCarrier->confirmationNumber ?? null],
                            array_map(function (Schema\FlightSegment $segment) {return $segment->marketingCarrier->confirmationNumber ?? null; }, $parsed->segments))));

            if (!in_array($locator, $parsedLocators)) {
                continue;
            }
            $confFields = array_merge(['ConfNo' => $locator], $this->matchNames($trip->getOwner(), $parsed));

            if ($this->enough($trip->getRealProvider()->getCode(), $confFields)) {
                return $confFields;
            }
        }

        return [];
    }

    private function enough($providerCode, array $fields): bool
    {
        switch ($providerCode) {
            case 'airfrance':
            case 'klm':
            case 'aviancataca':
                $keys = ['ConfNo', 'LastName'];

                break;

            case 'virgin':
                $keys = ['ConfNo', 'LastName', 'FirstName'];

                break;

            default:
                return false;
        }

        return count(array_intersect($keys, array_keys($fields))) === count($keys);
    }

    private function matchNames(Owner $owner, Schema\Flight $parsed): array
    {
        $score = 0;
        $match = [];
        $persons = array_filter($parsed->getPersons(), function (Person $person) {return !empty(trim($person->name)); });
        $first = $owner->isFamilyMember() ? $owner->getFamilyMember()->getFirstname() : $owner->getUser()->getFirstname();
        $last = $owner->isFamilyMember() ? $owner->getFamilyMember()->getLastname() : $owner->getUser()->getLastname();

        foreach ($persons as $person) {
            $parts = array_values(array_filter(explode(' ', str_replace(['\\', '/'], ' ', $person->name))));

            if (in_array($last, $parts)) {
                if (in_array($first, $parts)) {
                    return $this->addKeys([$last, $first]);
                } elseif (count($parts) === 2 && $score < 10) {
                    $match = array_values(array_merge([$last], array_diff($parts, [$last])));
                    $score = 10;
                } elseif ($score < 5) {
                    $match = [$last];
                    $score = 5;
                }
            } elseif (count($parts) >= 2 && $score < 1) {
                $match = [$parts[count($parts) - 1], $parts[0]];
                $score = 1;
            }
        }

        return $this->addKeys($match);
    }

    private function addKeys(array $indexed): array
    {
        $result = [];

        if (isset($indexed[0])) {
            $result['LastName'] = $indexed[0];
        }

        if (isset($indexed[1])) {
            $result['FirstName'] = $indexed[1];
        }

        return $result;
    }

    private function missing(Trip $trip): bool
    {
        switch ($trip->getRealProvider()->getCode()) {
            case 'airfrance':
            case 'klm':
                return empty($trip->getPricingInfo()) || null === $trip->getPricingInfo()->getTotal();

            case 'virgin':
            case 'aviancataca':
                $inTrip = !empty($trip->getShipCabinClass());
                $inSegments = true;

                foreach ($trip->getSegments() as $segment) {
                    if (empty($segment->getCabinClass())) {
                        $inSegments = false;
                    }
                }

                return !$inTrip && !$inSegments;

            default:
                return false;
        }
    }

    private function valid(Itinerary $trip): bool
    {
        if (!$trip instanceof Trip || $trip->getCategory() !== Trip::CATEGORY_AIR || !$trip->getRealProvider()) {
            return false;
        }
        $future = $checked = false;

        foreach ($trip->getSegments() as $segment) {
            if ($segment->getDepartureDate()->getTimestamp() > time()) {
                $future = true;
            }

            foreach ($segment->getSources() as $source) {
                if ($source instanceof ConfirmationNumber) {
                    $checked = true;
                }
            }
        }

        return $future && !$checked;
    }

    private function aaCacheMatching(Itinerary $itinerary, EmailInfo $metadata)
    {
        $matching = $this->memcached->get($this->getAaCacheKey($metadata->mailboxId));

        if (!$matching) {
            $travelers = array_map(
                function ($item) {
                    return Util::normalizeTravelerString($item);
                }, $itinerary->getTravelerNames()
            );

            $waitRedemptions = count($travelers);

            if (!$waitRedemptions) {
                return;
            }

            $reservationDate = $itinerary->getReservationDate();
            $reservationDate = ($reservationDate) ? strtotime(
                date('Y-m-d', $reservationDate->getTimestamp())
            ) : null;

            $cacheInfo = [
                'trip' => [
                    'id' => $itinerary->getId(),
                    'travelers' => $travelers,
                    'milesRedeemed' => 0,
                ],
                'waitRedemptions' => $waitRedemptions,
                'reservationDate' => $reservationDate,
                'receivedDateTime' => strtotime($metadata->receivedDateTime),
                'blocked' => false,
                'expiry' => 1800 + time(),
            ];

            $this->memcached->set(
                $this->getAaCacheKey($metadata->mailboxId),
                $cacheInfo,
                1800
            );
            $this->logger->info("aa trip information successful saved in cache. Mailbox: {$metadata->mailboxId}. CacheInfo: " . var_export($cacheInfo, true));

            return;
        }

        $this->logger->info("Mailbox: {$metadata->mailboxId}. Old CacheInfo: " . var_export($matching, true));

        $this->memcached->set(
            $this->getAaCacheKey($metadata->mailboxId),
            [
                'trip' => [],
                'waitRedemptions' => 0,
                'emailDate' => null,
                'blocked' => true,
                'expiry' => 14400 + time(),
            ],
            14400
        );

        $this->logger->info("Set block for aa award redemption matching. Mailbox: {$metadata->mailboxId}.");
    }

    private function getAaCacheKey(string $mailboxId): string
    {
        return 'aa_matching_reservation_' . $mailboxId;
    }
}
