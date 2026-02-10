<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Type\Phones;
use AwardWallet\MainBundle\Globals\StringUtils;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class PhonesSectionsBuilder
{
    /**
     * @var bool[]
     */
    private array $usedAirlines = [];

    /**
     * @var bool[]
     */
    private array $usedAirlineNames = [];

    /**
     * @var bool[]
     */
    private array $usedProviders = [];

    /**
     * @var Itinerary|Tripsegment
     */
    private $itinerary;

    private Phones $addressBook;

    private array $eliteLevels;

    /**
     * @var ProviderAirlinePair[]
     */
    private array $airlinePairsMap;

    /**
     * @param Itinerary|Tripsegment $itinerary
     */
    public function __construct($itinerary, Phones $addressBook, array $eliteLevels, array $airlinePairsMap)
    {
        $this->itinerary = $itinerary;
        $this->addressBook = $addressBook;
        $this->eliteLevels = $eliteLevels;
        $this->airlinePairsMap = $airlinePairsMap;
    }

    /**
     * @return PhonesSection[]
     */
    public function buildSections(): array
    {
        return it()
            ->chain($this->sectionFromAccount())
            ->chain($this->sectionsFromTrip())
            ->chain($this->sectionFromTravelAgency())
            ->toArrayWithKeys();
    }

    private function sectionFromTravelAgency(): iterable
    {
        [$itinerary] = $this->getItineraryAndTripSegment();

        if (
            ($travelAgency = $itinerary->getTravelAgency())
            && !isset($this->usedProviders[$travelAgency->getId()])
            && ($phonesSection = $this->addressBook->getPhonesByProvider($travelAgency, null, $this->getCountry()))
        ) {
            $this->usedProviders[$travelAgency->getId()] = true;

            yield PhonesSection::SECTION_TRAVEL_AGENCY => new PhonesSection($travelAgency, $phonesSection, []);
        }
    }

    private function sectionsFromTrip(): iterable
    {
        if (
            !($this->itinerary instanceof Tripsegment)
            && !($this->itinerary instanceof Trip)
        ) {
            return;
        }

        [$trip, $tripSegment] = $this->getItineraryAndTripSegment();

        $phones = [
            $this->phonesFromAirlineInfoTuple(
                PhonesSection::SECTION_ISSUING_AIRLINE,
                [
                    $trip->getIssuingAirline(),
                    $trip->getIssuingAirlineName(),
                ],
                $trip->getPhones()
            ),
        ];

        if ($tripSegment) {
            $phones[] = $this->phonesFromAirlineInfoTuple(
                PhonesSection::SECTION_MARKETING_AIRLINE,
                [
                    $tripSegment->getMarketingAirline(),
                    $tripSegment->getMarketingAirlineName(),
                ],
                $tripSegment->getMarketingAirlinePhoneNumbers()
            );
            $phones[] = $this->phonesFromAirlineInfoTuple(
                PhonesSection::SECTION_OPERATING_AIRLINE,
                [
                    $tripSegment->getOperatingAirline(),
                    $tripSegment->getOperatingAirlineName(),
                ],
                $tripSegment->getOperatingAirlinePhoneNumbers()
            );
        }

        yield from it($phones)->flatten(1);
    }

    private function phonesFromAirlineAndProvider(string $sectionCode, Airline $airline, ?array $localPhones): iterable
    {
        $addressBookPhones = [];
        /** @var Provider[] $providers */
        $providers = $this->airlinePairsMap[$airline->getCode()]->getProviders();

        if (
            it($providers)
            ->propertyPath('providerid')
            ->filterByInMap($this->usedProviders)
            ->isNotEmpty()
        ) {
            return;
        }

        foreach ($providers as $provider) {
            if (
                !isset($this->usedProviders[$provider->getId()])
                && ($addressBookPhonesChunk = $this->addressBook->getPhonesByProvider($provider, null, $this->getCountry()))
            ) {
                $this->usedProviders[$provider->getId()] = true;
                $addressBookPhones = \array_merge(
                    $addressBookPhones,
                    $addressBookPhonesChunk
                );
            }
        }

        if ($addressBookPhones) {
            yield $sectionCode => new PhonesSection($this->airlinePairsMap[$airline->getCode()], $addressBookPhones, $localPhones ?? []);
        }
    }

    private function phonesFromAirline(string $sectionCode, Airline $airline, ?array $localPhones): iterable
    {
        if (
            !isset($this->usedAirlines[$airline->getAirlineid()])
            && $localPhones
        ) {
            $this->usedAirlines[$airline->getAirlineid()] = true;

            yield $sectionCode => new PhonesSection($airline, [], $localPhones);
        }
    }

    private function phonesFromAirlineName(string $sectionCode, string $airlineName, ?array $localPhones): iterable
    {
        if (
            !isset($this->usedAirlineNames[$airlineName])
            && $localPhones
        ) {
            $this->usedAirlineNames[$airlineName] = true;

            yield $sectionCode => new PhonesSection($airlineName, [], $localPhones);
        }
    }

    private function phonesFromAirlineInfoTuple(string $sectionCode, array $airlineInfoTuple, ?array $localPhones): iterable
    {
        [$airlineObject, $airlineString] = $airlineInfoTuple;

        if ($airlineObject instanceof Airline) {
            if (isset($this->airlinePairsMap[$airlineObject->getCode()])) {
                yield from $this->phonesFromAirlineAndProvider($sectionCode, $airlineObject, $localPhones);
            } else {
                yield from $this->phonesFromAirline($sectionCode, $airlineObject, $localPhones);
            }
        } elseif (StringUtils::isNotEmpty($airlineString)) {
            yield from $this->phonesFromAirlineName($sectionCode, $airlineString, $localPhones);
        }
    }

    /**
     * @return iterable<PhonesSection>
     */
    private function sectionFromAccount(): iterable
    {
        [$itinerary] = $this->getItineraryAndTripSegment();
        $account = $itinerary->getAccount();

        if (!empty($account) && !empty($this->eliteLevels[$account->getAccountid()])) {
            $eliteLevel = $this->eliteLevels[$account->getAccountid()];
        } else {
            $eliteLevel = null;
        }

        $travelAgency = $itinerary->getTravelAgency();
        $provider =
            $itinerary->getRealProvider() ??
            (
                $account ?
                    $account->getProviderid() :
                        null
            );

        if (
            $provider
            && (
                !$travelAgency
                || ($provider->getId() !== $travelAgency->getId())
            )
            && !isset($this->usedProviders[$provider->getId()])
        ) {
            $this->usedProviders[$provider->getId()] = true;
            $addressBookPhones = $this->addressBook->getPhonesByProvider($provider, $eliteLevel, $this->getCountry());
            $origin = $provider;
        } else {
            $addressBookPhones = [];
            $origin = $itinerary;
        }

        $localPhones = $this->getItineraryLocalPhones($itinerary);

        if ($addressBookPhones || $localPhones) {
            yield PhonesSection::SECTION_ACCOUNT => new PhonesSection($origin, $addressBookPhones, $localPhones);
        }
    }

    private function getItineraryLocalPhones(Itinerary $itinerary): array
    {
        if ($itinerary instanceof Reservation) {
            $name = $itinerary->getHotelname();
        } elseif ($itinerary instanceof Restaurant) {
            $name = $itinerary->getName();
        } else {
            $name = null;
        }

        return it($itinerary->getPhones())
            ->map(function ($phone) use ($name) {
                $result = ['Phone' => $phone];

                if (StringUtils::isNotEmpty($name)) {
                    $result['Name'] = $name;
                }

                return $result;
            })->toArray();
    }

    private function getCountry(): ?string
    {
        /** @var Itinerary $itinerary */
        $itinerary = $this->itinerary instanceof Tripsegment ? $this->itinerary->getTripid() : $this->itinerary;
        $geotags = $itinerary->getGeoTags();

        return count($geotags) > 0 ? $geotags[0]->getCountry() : null;
    }

    /**
     * @return array{Itinerary, Tripsegment|null}
     */
    private function getItineraryAndTripSegment(): array
    {
        if ($this->itinerary instanceof Tripsegment) {
            $itinerary = $this->itinerary->getTripid();
            $tripSegment = $this->itinerary;
        } else {
            $itinerary = $this->itinerary;
            $tripSegment = null;
        }

        return [$itinerary, $tripSegment];
    }
}
