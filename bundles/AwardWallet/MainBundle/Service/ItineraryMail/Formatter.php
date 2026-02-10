<?php

namespace AwardWallet\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter\MailFormatterFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter\SimpleFormatterInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertiesDB;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertyInfo;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\Tags;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Util;
use AwardWallet\MainBundle\Timeline\Diff;
use AwardWallet\MainBundle\Timeline\Diff\Changes;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Formatter
{
    public const PRIORITY_NORMAL = 0;
    public const PRIORITY_HIGH = 1;
    public const PRIORITY_HIGHER = 2;
    public const PRIORITY_HIGHEST = 3;

    private Util $util;

    private Diff\Query $diffQuery;

    private MailFormatterFactory $mailFormatterFactory;

    private PropertiesDB $propertiesDB;

    public function __construct(
        Util $util,
        Diff\Query $diffQuery,
        MailFormatterFactory $mailFormatterFactory,
        PropertiesDB $propertiesDB
    ) {
        $this->util = $util;
        $this->diffQuery = $diffQuery;
        $this->mailFormatterFactory = $mailFormatterFactory;
        $this->propertiesDB = $propertiesDB;
    }

    /**
     * @param Itinerary|Tripsegment $it
     * @param string|null $defaultLocale
     * @param string|null $defaultLang
     * @return Segment[]
     */
    public function format($it, ?\DateTime $changeDate = null, $defaultLocale = null, $defaultLang = null, ?Changes $changes = null)
    {
        if ($it instanceof Trip) {
            /** @var Trip $it */
            if (!sizeof($it->getSegments())) {
                throw new \LogicException("Missing segments");
            }

            return $this->formatTrip($it, $changeDate, $defaultLocale, $defaultLang);
        } elseif ($it instanceof Tripsegment) {
            /** @var Tripsegment $it */
            return $this->formatTrip($it, $changeDate, $defaultLocale, $defaultLang, $changes);
        } elseif ($it instanceof Rental) {
            /** @var Rental $it */
            return $this->formatRental($it, $changeDate, $defaultLocale, $defaultLang, $changes);
        } elseif ($it instanceof Reservation) {
            /** @var Reservation $it */
            return $this->formatReservation($it, $changeDate, $defaultLocale, $defaultLang, $changes);
        } elseif ($it instanceof Restaurant) {
            /** @var Restaurant $it */
            return $this->formatRestaurant($it, $changeDate, $defaultLocale, $defaultLang, $changes);
        } elseif ($it instanceof Parking) {
            /** @var Parking $it */
            return $this->formatParking($it, $changeDate, $defaultLocale, $defaultLang, $changes);
        }
    }

    /**
     * @param Trip|Tripsegment $trip
     * @param string|null $defaultLocale
     * @param string|null $defaultLang
     * @return Segment[]
     */
    public function formatTrip($trip, ?\DateTime $changeDate = null, $defaultLocale = null, $defaultLang = null, ?Changes $changes = null)
    {
        if ($trip instanceof Trip) {
            $tripSegments = $trip->getSegmentsSorted();
        } elseif ($trip instanceof Tripsegment) {
            $tripSegments = [$trip];
            $trip = $trip->getTripid();
        } else {
            throw new \LogicException("Invalid argument trip");
        }

        $segments = [];

        foreach ($tripSegments as $tripSegment) {
            $formatter = $this->mailFormatterFactory->createFromTripSegment(
                $tripSegment,
                $this->getChanges($tripSegment, $changes),
                $changeDate,
                $defaultLocale,
                $defaultLang
            );

            $segment = $this->getEmptySegment($tripSegment, isset($changeDate), $formatter, $defaultLocale, $defaultLang);

            foreach (it($this->propertiesDB->getProperties())
                ->filter(function (PropertyInfo $propertyInfo) {
                    return
                        (
                            $propertyInfo->hasTag(Tags::COMMON)
                            || $propertyInfo->hasTag(Tags::TRIP)
                        ) && !in_array($propertyInfo->getCode(), [
                            PropertiesList::ARRIVAL_AIRPORT_CODE,
                            PropertiesList::DEPARTURE_AIRPORT_CODE,
                        ]);
                }) as $property) {
                /** @var PropertyInfo $property */
                $segment->addProperty($property->getCode(), !$property->hasTag(Tags::INTERNAL), $property->isPrivate());
            }

            $segments[] = $segment;
        }

        $groups = $this->prepareGroups([
            self::PRIORITY_HIGHEST => [PropertiesList::CONFIRMATION_NUMBER],
            self::PRIORITY_HIGHER => [PropertiesList::DEPARTURE_NAME, PropertiesList::ARRIVAL_NAME],
            self::PRIORITY_HIGH => [
                PropertiesList::DEPARTURE_TERMINAL,
                PropertiesList::ARRIVAL_TERMINAL,
                PropertiesList::DEPARTURE_GATE,
                PropertiesList::ARRIVAL_GATE,
                PropertiesList::DEPARTURE_DATE,
                PropertiesList::ARRIVAL_DATE,
            ],
        ]);

        foreach ($segments as $segment) {
            $this->setGroups($groups, [self::PRIORITY_NORMAL], $segment);
            $segment->setProperties(PropertySorter::sort($segment->getProperties(), PropertiesList::$tripPropertiesOrder));
        }

        return $segments;
    }

    /**
     * @param string|null $defaultLocale
     * @param string|null $defaultLang
     * @return Segment[]
     */
    public function formatRental(Rental $rental, ?\DateTime $changeDate = null, $defaultLocale = null, $defaultLang = null, ?Changes $changes = null)
    {
        $formatter = $this->mailFormatterFactory->createFromItinerary(
            $rental,
            $this->getChanges($rental, $changes),
            $changeDate,
            $defaultLang,
            $defaultLocale
        );

        $segment = $this->getEmptySegment($rental, isset($changeDate), $formatter, $defaultLocale, $defaultLang);

        foreach (it($this->propertiesDB->getProperties())
                     ->filter(function (PropertyInfo $propertyInfo) {
                         return
                             (
                                 $propertyInfo->hasTag(Tags::COMMON)
                                 || $propertyInfo->hasTag(Tags::RENTAL)
                             ) && !in_array($propertyInfo->getCode(), [
                                 PropertiesList::CAR_IMAGE_URL,
                             ]);
                     }) as $property) {
            /** @var PropertyInfo $property */
            $segment->addProperty($property->getCode(), !$property->hasTag(Tags::INTERNAL), $property->isPrivate());
        }
        $segments = [$segment];

        $groups = $this->prepareGroups([
            self::PRIORITY_HIGHEST => [PropertiesList::CONFIRMATION_NUMBER],
            self::PRIORITY_HIGHER => [PropertiesList::PICK_UP_LOCATION, PropertiesList::DROP_OFF_LOCATION],
            self::PRIORITY_HIGH => [
                PropertiesList::PICK_UP_DATE,
                PropertiesList::PICK_UP_HOURS,
                PropertiesList::PICK_UP_PHONE,
                PropertiesList::PICK_UP_FAX,
                PropertiesList::DROP_OFF_DATE,
                PropertiesList::DROP_OFF_HOURS,
                PropertiesList::DROP_OFF_PHONE,
                PropertiesList::DROP_OFF_FAX,
            ],
        ]);

        foreach ($segments as $segment) {
            $this->setGroups($groups, [self::PRIORITY_NORMAL], $segment);
            $segment->setProperties(PropertySorter::sort($segment->getProperties(), PropertiesList::$rentalPropertiesOrder));
        }

        return $segments;
    }

    /**
     * @param string|null $defaultLocale
     * @param string|null $defaultLang
     * @return Segment[]
     */
    public function formatReservation(Reservation $reservation, ?\DateTime $changeDate = null, $defaultLocale = null, $defaultLang = null, ?Changes $changes = null)
    {
        $formatter = $this->mailFormatterFactory->createFromItinerary(
            $reservation,
            $this->getChanges($reservation, $changes),
            $changeDate,
            $defaultLang,
            $defaultLocale
        );

        $segment = $this->getEmptySegment($reservation, isset($changeDate), $formatter, $defaultLocale, $defaultLang);

        foreach (it($this->propertiesDB->getProperties())
                     ->filter(function (PropertyInfo $propertyInfo) {
                         return

                                 $propertyInfo->hasTag(Tags::COMMON)
                                 || $propertyInfo->hasTag(Tags::RESERVATION)
                         ;
                     }) as $property) {
            /** @var PropertyInfo $property */
            $segment->addProperty($property->getCode(), !$property->hasTag(Tags::INTERNAL), $property->isPrivate());
        }
        $segments = [$segment];

        $groups = $this->prepareGroups([
            self::PRIORITY_HIGHEST => [PropertiesList::CONFIRMATION_NUMBER],
            self::PRIORITY_HIGHER => [PropertiesList::CHECK_IN_DATE, PropertiesList::CHECK_OUT_DATE],
            self::PRIORITY_HIGH => [PropertiesList::HOTEL_NAME],
        ]);

        foreach ($segments as $segment) {
            $this->setGroups($groups, [self::PRIORITY_NORMAL], $segment);
            $segment->setProperties(PropertySorter::sort($segment->getProperties(), PropertiesList::$reservationPropertiesOrder));
        }

        return $segments;
    }

    /**
     * @param string|null $defaultLocale
     * @param string|null $defaultLang
     * @return Segment[]
     */
    public function formatRestaurant(Restaurant $restaurant, ?\DateTime $changeDate = null, $defaultLocale = null, $defaultLang = null, ?Changes $changes = null)
    {
        $formatter = $this->mailFormatterFactory->createFromItinerary(
            $restaurant,
            $this->getChanges($restaurant, $changes),
            $changeDate,
            $defaultLang,
            $defaultLocale
        );

        $segment = $this->getEmptySegment($restaurant, isset($changeDate), $formatter, $defaultLocale, $defaultLang);

        foreach (it($this->propertiesDB->getProperties())
                     ->filter(function (PropertyInfo $propertyInfo) {
                         return

                                 $propertyInfo->hasTag(Tags::COMMON)
                                 || $propertyInfo->hasTag(Tags::RESTAURANT)
                         ;
                     }) as $property) {
            /** @var PropertyInfo $property */
            $segment->addProperty($property->getCode(), !$property->hasTag(Tags::INTERNAL), $property->isPrivate());
        }
        $segments = [$segment];

        $groups = $this->prepareGroups([
            self::PRIORITY_HIGHEST => [PropertiesList::CONFIRMATION_NUMBER],
            self::PRIORITY_HIGHER => [PropertiesList::START_DATE, PropertiesList::END_DATE],
            self::PRIORITY_HIGH => [PropertiesList::EVENT_NAME, PropertiesList::ADDRESS, PropertiesList::PHONE],
        ]);

        foreach ($segments as $segment) {
            $this->setGroups($groups, [self::PRIORITY_NORMAL], $segment);
            $segment->setProperties(PropertySorter::sort($segment->getProperties(), PropertiesList::$restaurantPropertiesOrder));
        }

        return $segments;
    }

    /**
     * @param string|null $defaultLocale
     * @param string|null $defaultLang
     * @return Segment[]
     */
    public function formatParking(Parking $parking, ?\DateTime $changeDate = null, $defaultLocale = null, $defaultLang = null, ?Changes $changes = null)
    {
        $formatter = $this->mailFormatterFactory->createFromItinerary(
            $parking,
            $this->getChanges($parking, $changes),
            $changeDate,
            $defaultLang,
            $defaultLocale
        );

        $segment = $this->getEmptySegment($parking, isset($changeDate), $formatter, $defaultLocale, $defaultLang);

        foreach (it($this->propertiesDB->getProperties())
                     ->filter(function (PropertyInfo $propertyInfo) {
                         return

                                 $propertyInfo->hasTag(Tags::COMMON)
                                 || $propertyInfo->hasTag(Tags::PARKING)
                         ;
                     }) as $property) {
            /** @var PropertyInfo $property */
            $segment->addProperty($property->getCode(), !$property->hasTag(Tags::INTERNAL), $property->isPrivate());
        }
        $segments = [$segment];

        $groups = $this->prepareGroups([
            self::PRIORITY_HIGHEST => [PropertiesList::CONFIRMATION_NUMBER],
            self::PRIORITY_HIGHER => [PropertiesList::START_DATE, PropertiesList::END_DATE],
            self::PRIORITY_HIGH => [
                PropertiesList::PHONE, PropertiesList::LOCATION,
            ],
        ]);

        foreach ($segments as $segment) {
            $this->setGroups($groups, [self::PRIORITY_NORMAL], $segment);
            $segment->setProperties(PropertySorter::sort($segment->getProperties(), PropertiesList::$parkingPropertiesOrder));
        }

        return $segments;
    }

    private function getChanges(object $itinerary, $externalChanges = null): Changes
    {
        if (!$externalChanges) {
            return $this->diffQuery->query(Segment::getSourceId($itinerary));
        }

        return $externalChanges;
    }

    private function getEmptySegment($it, bool $showChanges, SimpleFormatterInterface $formatter, $defaultLocale = null, $defaultLang = null)
    {
        return new Segment($it, $showChanges, $formatter, $this->util, $defaultLocale, $defaultLang);
    }

    private function prepareGroups(array $groups)
    {
        $prepared = [];

        foreach ($groups as $groupId => $properties) {
            foreach ($properties as $property) {
                if (!isset($prepared[$property])) {
                    $prepared[$property] = [$groupId];
                } else {
                    $prepared[$property][] = $groupId;
                }
            }
        }

        return $prepared;
    }

    private function setGroups(array $groups, array $defaultGroups, Segment $segment)
    {
        foreach ($segment->getProperties() as $property) {
            $code = $property->getCode();

            if (isset($groups[$code])) {
                $property->setGroups($groups[$code]);
            } else {
                $property->setGroups($defaultGroups);
            }
        }
    }
}
