<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Entity\Fee;
use AwardWallet\MainBundle\Entity\FlightInfo;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\ShowAIWarningForEmailSourceInterface;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter\DesktopFormatterFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline\Formatter\Origin;
use AwardWallet\MainBundle\Timeline\Formatter\Utils\ParkingHeaderResolver;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\Map;
use AwardWallet\MainBundle\Timeline\PhonesSection;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\Util\ItineraryUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

abstract class AbstractItinerary extends AbstractItem
{
    protected LocalizeService $localizeService;

    protected TranslatorInterface $translator;

    protected UrlGeneratorInterface $urlGenerator;

    protected AuthorizationCheckerInterface $authorizationChecker;

    protected TokenStorageInterface $tokenStorage;

    protected DateTimeIntervalFormatter $intervalFormatter;

    protected DesktopFormatterFactory $desktopFormatterFactory;

    protected ParkingHeaderResolver $parkingHeaderResolver;

    private Origin $originFormatter;

    public function __construct(
        LocalizeService $localizeService,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        DateTimeIntervalFormatter $intervalFormatter,
        DesktopFormatterFactory $desktopFormatterFactory,
        Origin $originFormatter,
        ParkingHeaderResolver $parkingHeaderResolver
    ) {
        $this->localizeService = $localizeService;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->intervalFormatter = $intervalFormatter;
        $this->desktopFormatterFactory = $desktopFormatterFactory;
        $this->originFormatter = $originFormatter;
        $this->parkingHeaderResolver = $parkingHeaderResolver;
    }

    /**
     * @param AbstractItineraryItem $item
     */
    public function format(ItemInterface $item, QueryOptions $queryOptions)
    {
        $source = $item->getSource();
        $itinerary = $item->getItinerary();

        if ($source instanceof Tripsegment) {
            $item->getContext()->setPropFormatter(
                $this->desktopFormatterFactory->createFromTripSegment($item->getSource(), $item->getChanges())
            );
        } elseif ($source instanceof Itinerary) {
            $item->getContext()->setPropFormatter(
                $this->desktopFormatterFactory->createFromItinerary($item->getSource(), $item->getChanges())
            );
        }

        $result = parent::format($item, $queryOptions);

        if (!empty($title = $this->getTitle($item))) {
            $result['title'] = $title;
        }

        if (!empty($map = $item->getMap())) {
            $result['map'] = $this->formatMap($map);
        }

        $result['localTime'] = $this->localizeService->formatDateTime($item->getLocalDate(), null, 'short');
        $result['localDateISO'] = sprintf('%sT00:00', $item->getStartDate()->format('Y-m-d'));
        $result['localDateTimeISO'] = $item->getStartDate()->format('c');

        if ($this->isFormatDetails()) {
            $details = $this->getDetails($item);

            if (!empty($details)) {
                $aliases = $this->getFeesAliases($itinerary->getPricingInfo()->getFees());
                $details = $this->removeEmpty($details);
                $details = $this->sortDetails($details, $aliases);
                $details = $this->translateDetails($details, $itinerary->getType());
                $result['details'] = $details;
            }
        }

        $result['type'] = 'segment';
        $result['icon'] = $item->getIcon();

        if ($this->authorizationChecker->isGranted('EDIT', $itinerary)) {
            if ($origins = $this->originFormatter->format($item)) {
                $result['origins'] = $origins;
            }

            if (!empty($confNo = $item->getConfNo())) {
                $result['confNo'] = $confNo;
                $result['group'] = $confNo;
            } elseif (!empty($itinerary)) {
                $result['group'] = $itinerary->getKind() . $itinerary->getId();
            }
        }

        if (!empty($itinerary)) {
            $result['deleted'] = $itinerary->getHidden();

            if ($this->authorizationChecker->isGranted('EDIT', $itinerary)) {
                $date = $itinerary->getUpdateDate();

                if (empty($date)) {
                    $date = $itinerary->getCreateDate();
                }
                $result['lastUpdated'] = $date->getTimestamp();

                if (!empty($dateParsed = $itinerary->getLastParseDate())) {
                    $result['lastSync'] = $dateParsed->getTimestamp();
                }
            }
        }

        if ($source instanceof Tripsegment
            && ItineraryUtil::isOverseasTravel($source->getGeoTags(), $item->isOverseasTrip())) {
            $result['isShowNoForeignFeesCards'] = true;
        }

        if (
            $this->showAIWarning()
            && ($source instanceof ShowAIWarningForEmailSourceInterface)
            && $source->isShowAIWarningForEmailSource()
        ) {
            $result['showAIWarning'] = true;
        }

        return $result;
    }

    protected function formatMap(Map $map): array
    {
        $result = [
            'points' => $map->points,
            'arrTime' => $this->localizeService->formatDateTime($map->arrDate, null, 'short'),
        ];

        if (!empty($map->getStationCodes())) {
            $result['stationCodes'] = $map->getStationCodes();
        }

        return $result;
    }

    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = [];

        $formatter = $item->getContext()->getPropFormatter();
        $itinerary = $item->getItinerary();
        $injectPropertyToResult = function (string $code) use (&$result, $formatter) {
            if (!\is_null($value = $formatter->getValue($code))) {
                $result[$code] = $value;
            }
        };

        if (!empty($account = $item->getAccount())) {
            $result['accountId'] = $account->getAccountid();
        }

        if (!empty($agent = $item->getAgent())) {
            $result['agentId'] = $agent->getId();
        }

        if ($this->authorizationChecker->isGranted('UPDATE', $itinerary)) {
            if (!empty($account)) {
                $result['refreshLink'] = $this->urlGenerator->generate('aw_trips_update') .
                    '?' . http_build_query([
                        'accounts' => [$account->getAccountid()],
                        'agentId' => $result['agentId'] ?? null,
                    ]);
            } else {
                $result['refreshLink'] = $this->urlGenerator->generate('aw_trips_retrieve_confirmation', [
                    'providerId' => $itinerary->getProvider()->getId(),
                    'itKind' => $itinerary->getKind(),
                    'itId' => $itinerary->getId(),
                    'agentId' => $result['agentId'] ?? null,
                ]);
            }
        }

        if (
            (!empty($account) || null !== $itinerary->getConfFields())
            && $this->authorizationChecker->isGranted('AUTOLOGIN', $itinerary)
        ) {
            $result['autoLoginLink'] = $this->urlGenerator->generate('aw_account_redirect', [
                'itID' => $itinerary->getId(),
                'table' => $itinerary->getKind(),
                'agentId' => $result['agentId'] ?? null,
            ]);
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if ($user instanceof Usr) {
            $result['canEdit'] = $this->authorizationChecker->isGranted('EDIT', $itinerary);
        }

        $injectPropertyToResult(PropertiesList::RESERVATION_DATE);

        if ($this->authorizationChecker->isGranted('EDIT', $itinerary)) {
            foreach (
                [
                    PropertiesList::CONFIRMATION_NUMBERS,
                    PropertiesList::ACCOUNT_NUMBERS,
                    PropertiesList::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
                ] as $propertyCode
            ) {
                $injectPropertyToResult($propertyCode);
            }

            $valueProvider = function (string $code) use ($formatter) {
                if (!\is_null($value = $formatter->getValue($code))) {
                    yield $code => $value;
                }
            };
            $valuesProvider = function (string $code) use ($formatter) {
                if (!\is_null($value = $formatter->getValue($code))) {
                    yield from $value;
                }
            };

            $pricing =
                it($valueProvider(PropertiesList::COST))
                    ->chain($valuesProvider(PropertiesList::FEES_LIST))
                    ->chain(
                        it([
                            PropertiesList::SPENT_AWARDS,
                            PropertiesList::DISCOUNT,
                            PropertiesList::TOTAL_CHARGE,
                        ])
                            ->flatMap($valueProvider)
                    )
                    ->toArrayWithKeys();

            if (!empty($pricing)) {
                $result['pricing'] = $pricing;
            }

            $otherPropsMap = [
                PropertiesList::EARNED_AWARDS => PropertiesList::EARNED_AWARDS,
                PropertiesList::TRAVEL_AGENCY_EARNED_AWARDS => PropertiesList::TRAVEL_AGENCY_EARNED_AWARDS,
                'notes' => PropertiesList::NOTES,
                PropertiesList::FILES => PropertiesList::FILES,
            ];

            foreach ($otherPropsMap as $propKey => $propCode) {
                $result[$propKey] = $formatter->getValue($propCode);
            }
        }
        $result[PropertiesList::STATUS] = $formatter->getValue(PropertiesList::STATUS);
        $result[PropertiesList::COMMENT] = $formatter->getValue(PropertiesList::COMMENT);

        // do not show share button, when user not authorized, and viewing by share link, when it is the only button
        if (!empty($result['canCheck']) || !empty($result['canEdit']) || !empty($result['canAutoLogin'])) {
            $result['shareCode'] = $itinerary->getEncodedShareCode();
        }

        $result['phones'] = $this->getPhone($item);

        if ($this->authorizationChecker->isGranted('EDIT', $itinerary)) {
            // icon for trip being monitored
            /** @var Tripsegment $segment */
            $tripSegment = $item->getSource();
            $provider = $item->getProvider();
            $monitoredByAccount =
                !empty($provider) && !empty($account) && !empty($account->getProviderid())
                && $account->getBackgroundCheck()
                && $account->canCheck($user instanceof Usr ? $user : null)
                && !$account->isDisabled()
                && $account->getErrorcode() == ACCOUNT_CHECKED;
            $monitoredByFlightStats =
                !empty($tripSegment) && $tripSegment instanceof Tripsegment
                && ($tripSegment->getTripAlertsUpdateDate() || ($tripSegment->getFlightinfoid() && $tripSegment->getFlightinfoid()->getState() == FlightInfo::STATE_CHECKED));

            if ($monitoredByAccount && $monitoredByFlightStats) {
                $result['monitoredStatus'] = $this->translator->trans(
                    /** @Desc("This trip segment is being monitored for changes by AwardWallet via FlightStats and your %providerName% online account") */
                    'trip.monitored.aw_and_flightstats',
                    ['%providerName%' => $account->getProviderid()->getShortname()]
                );
            } elseif ($monitoredByAccount && !$monitoredByFlightStats) {
                $result['monitoredStatus'] = $this->translator->trans(
                    /** @Desc("This trip segment is being monitored for changes by AwardWallet via your %providerName% online account") */
                    'trip.monitored.aw',
                    ['%providerName%' => $account->getProviderid()->getShortname()]
                );
            } elseif (!$monitoredByAccount && $monitoredByFlightStats) {
                $result['monitoredStatus'] = $this->translator->trans(
                    /** @Desc("This trip segment is being monitored for changes by AwardWallet via FlightStats") */
                    'trip.monitored.flightstats'
                );
            }
        }

        return $result;
    }

    protected function getPhone(AbstractItineraryItem $item): array
    {
        $itemPhones = $item->getPhones();

        return it([
            PhonesSection::SECTION_TRAVEL_AGENCY,
            PhonesSection::SECTION_MARKETING_AIRLINE,
            PhonesSection::SECTION_ISSUING_AIRLINE,
            PhonesSection::SECTION_OPERATING_AIRLINE,
            PhonesSection::SECTION_ACCOUNT,
        ])
            ->filterByInMap($itemPhones)
            ->flatMap(function (string $sectionCode) use ($item, $itemPhones) {
                $phones = [];
                $section = $itemPhones[$sectionCode];
                $name = $section->getName();
                $phone = it($section->getLocalPhones())
                    ->chain($section->getAddressBookPhones())
                    ->first();
                $phones[$sectionCode] = [
                    'phone' => $phone['Phone'],
                    'provider' => $name,
                ];

                if (PhonesSection::SECTION_ACCOUNT !== $sectionCode) {
                    $transKey = PhonesSection::SECTION_TRAVEL_AGENCY === $sectionCode ? 'itineraries.travel-agency.phones.title' : $sectionCode;
                    $phones[$sectionCode]['section'] = $this->translator->trans($transKey, [], 'trips');
                } elseif (!empty($segmentPhone = $this->getSegmentPhone($item))) {
                    $phones[$sectionCode]['phone'] = $segmentPhone;
                }

                if (!empty($phone['EliteLevel'])) {
                    $phones[$sectionCode]['level'] = $phone['EliteLevel'];
                }

                return $phones;
            })
            ->toArrayWithKeys();
    }

    protected function getSegmentPhone(AbstractItineraryItem $item)
    {
        return null;
    }

    protected function isFormatDetails(): bool
    {
        return true;
    }

    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        return null;
    }

    protected function showAIWarning(): bool
    {
        return false;
    }

    abstract protected function getDetailsOrder(): array;

    private function removeEmpty(array $arr): array
    {
        $clearedArr = [];

        foreach ($arr as $key => $value) {
            if (null !== $value && '' !== $value) {
                $clearedArr[$key] = $value;
            }
        }

        return $clearedArr;
    }

    private function sortDetails(array $details, array $aliases = []): array
    {
        $order = $this->getDetailsOrder();
        uksort($details, function (string $a, string $b) use ($order, $aliases) {
            $a = $aliases[$a] ?? $a;
            $b = $aliases[$b] ?? $b;

            return (int) array_search($a, $order) <=> (int) array_search($b, $order);
        });

        return $details;
    }

    private function translateDetails(array $details, string $type): array
    {
        $translatedDetails = [];

        foreach ($details as $name => $value) {
            if (!in_array($name, ['columns'])) {
                $key = PropertiesList::getTranslationKeyForProperty($name, $type);
                $translatedDetails[$this->translator->trans($key, [], 'trips')] = $value;
            } else {
                $translatedDetails[$name] = $value;
            }
        }

        if (array_key_exists('pricing', $details)) {
            $translatePricing = [];

            foreach ($details['pricing'] as $name => $value) {
                $key = PropertiesList::getTranslationKeyForProperty($name, $type);
                $translatePricing[$this->translator->trans($key, [], 'trips')] = $value;
            }
            $translatedDetails['pricing'] = $translatePricing;
        }

        return $translatedDetails;
    }

    private function getFeesAliases(?array $fees): array
    {
        if (null === $fees) {
            return [];
        }
        $aliases = [];

        /** @var Fee $fee */
        foreach ($fees as $fee) {
            $aliases[$fee->getName()] = PropertiesList::FEES;
        }

        return $aliases;
    }
}
