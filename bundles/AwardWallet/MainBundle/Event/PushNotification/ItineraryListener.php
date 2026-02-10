<?php

namespace AwardWallet\MainBundle\Event\PushNotification;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\Common\Geo\GeoAirportFinder;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Repositories\CountryRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Event\PushNotification\Target\ItineraryUpdate;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TransChoice;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Timeline\Diff\Properties;
use AwardWallet\MainBundle\Timeline\Diff\WhiteList;
use AwardWallet\MainBundle\Timeline\TripInfo\TripInfo;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ItineraryListener implements TranslationContainerInterface
{
    public const TRIP_DESTINATION_LENGTH_LIMIT = 20;
    private EntityManagerInterface $entityManager;
    private Sender $sender;
    private LocalizeService $localizer;
    private UsrRepository $userRep;
    private CountryRepository $countryRep;
    private \Memcached $memcached;
    private GeoAirportFinder $airportFinder;
    private ClockInterface $clock;

    public function __construct(
        EntityManagerInterface $entityManager,
        Sender $sender,
        LocalizeService $localizer,
        \Memcached $memcached,
        GeoAirportFinder $airportFinder,
        ClockInterface $clock
    ) {
        $this->entityManager = $entityManager;
        $this->sender = $sender;
        $this->localizer = $localizer;
        $this->userRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->countryRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Country::class);
        $this->memcached = $memcached;
        $this->airportFinder = $airportFinder;
        $this->clock = $clock;
    }

    public function onItineraryUpdate(ItineraryUpdateEvent $event)
    {
        if ($event->isSilent()) {
            return;
        }

        if (
            empty($event->getUserId())
            || !($user = $this->userRep->find($event->getUserId()))
        ) {
            return;
        }

        if (WhiteList::shouldNotify($event->getChanged(), $event->getChangedOld(), $event->getChangedNames(), $this->clock->current()->getAsSecondsInt())) {
            $devices = $this->sender->loadDevices([$user], MobileDevice::TYPES_ALL, Content::TYPE_CHANGED_ITINERARY);

            if (!$devices) {
                return;
            }
            $this->sendChangedItineraries($event->getChanged(), $devices);
        }

        $devices = $this->sender->loadDevices([$user], MobileDevice::TYPES_ALL, Content::TYPE_NEW_ITINERARY);

        if (!$devices) {
            return;
        }
        $this->sendAddedItineraries($event->getAdded(), $devices);
    }

    public static function getTranslationMessages()
    {
        return [
            // Hotel

            (new Message('push-notifications.new-travel-item.hotel.no-items'))
                ->setDesc('%hotel-name% in %hotel-location% on %checkin-date%, has been added.'),

            (new Message('push-notifications.new-travel-item.hotel'))
                ->setDesc(joinPlural([
                    '{1}%hotel-name% in %hotel-location% on %checkin-date%, and 1 additional item added.',
                    '[2,Inf]%hotel-name% in %hotel-location% on %checkin-date%, and %items-count% additional items added.',
                ])),

            // Event

            (new Message('push-notifications.new-travel-item.event.no-items'))
                ->setDesc('%event-name% on %event-date% at %event-time%, has been added.'),

            (new Message('push-notifications.new-travel-item.event'))
                ->setDesc(joinPlural([
                    '{1}%event-name% on %event-date% at %event-time%, and 1 additional item added.',
                    '[2,Inf]%event-name% on %event-date% at %event-time%, and %items-count% additional items added.',
                ])),

            // Rental

            (new Message('push-notifications.new-travel-item.rental.no-items'))
                ->setDesc('%company-name% car rental in %pickup-location% on %pickup-date%, has been added.'),

            (new Message('push-notifications.new-travel-item.rental.airport.no-items'))
                ->setDesc('%company-name% car rental at %pickup-airport% airport on %pickup-date%, has been added.'),

            (new Message('push-notifications.new-travel-item.rental'))
                ->setDesc(joinPlural([
                    '{1}%company-name% car rental in %pickup-location% on %pickup-date%, and 1 additional item added.',
                    '[2,Inf]%company-name% car rental in %pickup-location% on %pickup-date%, and %items-count% additional items added.',
                ])),

            (new Message('push-notifications.new-travel-item.rental.airport'))
                ->setDesc(joinPlural([
                    '{1}%company-name% car rental at %pickup-airport% airport on %pickup-date%, and 1 additional item added.',
                    '[2,Inf]%company-name% car rental at %pickup-airport% airport on %pickup-date%, and %items-count% additional items added.',
                ])),

            // Tripsegment

            (new Message('push-notifications.new-travel-item.tripsegment.no-items'))
                ->setDesc('%airline-name% flight %flight-number% %departure-name% to %arrival-name% on %departure-date%, has been added.'),

            (new Message('push-notifications.new-travel-item.tripsegment'))
                ->setDesc(joinPlural([
                    '{1}%airline-name% flight %flight-number% %departure-name% to %arrival-name% on %departure-date%, and 1 additional item added.',
                    '[2,Inf]%airline-name% flight %flight-number% %departure-name% to %arrival-name% on %departure-date%, and %items-count% additional items added.',
                ])),

            (new Message('push-notifications.new-travel-item.tripsegment.no-items.no-flight-number'))
                ->setDesc('%airline-name% flight %departure-name% to %arrival-name% on %departure-date%, has been added.'),

            (new Message('push-notifications.new-travel-item.tripsegment.no-flight-number'))
                ->setDesc(joinPlural([
                    '{1}%airline-name% flight %departure-name% to %arrival-name% on %departure-date%, and 1 additional item added.',
                    '[2,Inf]%airline-name% flight %departure-name% to %arrival-name% on %departure-date%, and %items-count% additional items added.',
                ])),

            (new Message('push-notifications.seats-assignments-changed'))
                ->setDesc('Seat assignments changed from %old-seats% to %new-seats% on %airline-name% flight %flight-number% from %departure-name% to %arrival-name% on %departure-date%.'),

            (new Message('push-notifications.seats-assignments-changed.no-flight-number'))
                ->setDesc('Seat assignments changed from %old-seats% to %new-seats% on %airline-name% flight from %departure-name% to %arrival-name% on %departure-date%.'),

            // Titles

            (new Message('push-notifications.new-travel-item'))
                ->setDesc(joinPlural([
                    '{1}Travel Item Added',
                    '[2,Inf]Travel Items Added',
                ])),

            (new Message('push-notifications.seats-assignments-changed.title'))
                ->setDesc('Seat Change Alert'),
        ];
    }

    /**
     * @param string $key
     * @param string $keyDelimiter
     * @return Trans
     */
    protected function generateVaryingTrans($key, array $transParams, array $optionalTransParams = [], $keyDelimiter = '.')
    {
        foreach ($optionalTransParams as $transParamKey => $transParamValue) {
            if (StringHandler::isEmpty($transParamValue)) {
                continue;
            }

            $key .= "{$keyDelimiter}{$transParamKey}";
            $transParams["%{$transParamKey}%"] = $transParamValue;
        }

        return new Trans(/** @Ignore */ $key, $transParams);
    }

    /**
     * @param Itinerary|Tripsegment $entity
     * @param int $additionalItemsCount
     * @param int $deadlineTimestamp
     * @param MobileDevice[] $devices
     */
    protected function sendItinerary($entity, $additionalItemsCount, $deadlineTimestamp, array $devices)
    {
        switch (true) {
            case $entity instanceof Reservation:
                $this->sendNewReservation($entity, $additionalItemsCount, $devices);

                break;

            case $entity instanceof Rental:
                $this->sendNewRental($entity, $additionalItemsCount, $devices);

                break;

            case $entity instanceof Restaurant:
                $this->sendNewEvent($entity, $additionalItemsCount, $devices);

                break;

            case $entity instanceof Tripsegment:
                $this->sendNewTripsegment($entity, $additionalItemsCount, $deadlineTimestamp, $devices);

                break;
        }
    }

    protected function sendNewRental(Rental $entity, $additionalItemsCount, array $devices)
    {
        if (!($geotag = $entity->getPickupgeotagid()) || $geotag->getLat() === null || $geotag->getLng() === null) {
            return;
        }

        $airport = $this->airportFinder->getNearestAirport($geotag->getLat(), $geotag->getLng(), 2);

        if ($airport !== null) {
            $pickupAirport = $airport->getAircode();
        } elseif (!StringUtils::isEmpty($city = $this->getCityLocation($geotag))) {
            $pickupLocation = $city;
        }

        if (
            !isset($pickupLocation)
            && !isset($pickupAirport)
        ) {
            return;
        }

        $rentalCompany = null;

        if (null !== ($company = $entity->getRentalCompanyName())) {
            $rentalCompany = $company;
        }

        if (
            !isset($rentalCompany)
            && !StringUtils::isEmpty($company = $entity->getRentalCompanyName())
        ) {
            $rentalCompany = $company;
        }

        $transParameters = [
            '%company-name%' => $rentalCompany,
            '%items-count%' => $additionalItemsCount,
            '%pickup-date%' => $this->getDateFormatter($entity->getStartDate(), 'long', null),
        ];

        if (isset($pickupAirport)) {
            $transParameters['%pickup-airport%'] = $pickupAirport;
        } elseif (isset($pickupLocation)) {
            $transParameters['%pickup-location%'] = $pickupLocation;
        }

        if (ListenerUtils::isAnyEmptyParams($transParameters = ListenerUtils::decodeStrings($transParameters))) {
            return;
        }

        $this->sender->send(
            new Content(
                $this->getAddedItineraryTitle($additionalItemsCount + 1),
                isset($pickupAirport) ?
                    (
                        $additionalItemsCount ?
                            new TransChoice('push-notifications.new-travel-item.rental.airport', $additionalItemsCount, $transParameters) :
                            new Trans('push-notifications.new-travel-item.rental.airport.no-items', $transParameters)
                    ) :
                    (
                        $additionalItemsCount ?
                            new TransChoice('push-notifications.new-travel-item.rental', $additionalItemsCount, $transParameters) :
                            new Trans('push-notifications.new-travel-item.rental.no-items', $transParameters)
                    ),
                Content::TYPE_NEW_ITINERARY,
                new ItineraryUpdate($entity, $additionalItemsCount + 1, true),
                (new Options())
                    ->setPriority(3)
                    ->setDeadlineTimestamp($entity->getUTCStartDate()->getTimestamp() + SECONDS_PER_HOUR)
                    ->setInterruptionLevel(InterruptionLevel::ACTIVE)
            ),
            $devices
        );
    }

    protected function sendNewEvent(Restaurant $entity, $additionalItemsCount, array $devices)
    {
        $transParameters = [
            '%event-date%' => $this->getDateFormatter($entity->getStartDate(), 'long', null),
            '%event-time%' => $this->getDateFormatter($entity->getStartDate(), null, 'short'),
            '%event-name%' => $entity->getName(),
            '%items-count%' => $additionalItemsCount,
        ];

        if (ListenerUtils::isAnyEmptyParams($transParameters = ListenerUtils::decodeStrings($transParameters))) {
            return;
        }

        $this->sender->send(
            new Content(
                $this->getAddedItineraryTitle($additionalItemsCount + 1),
                $additionalItemsCount ?
                    new TransChoice('push-notifications.new-travel-item.event', $additionalItemsCount, $transParameters) :
                    new Trans('push-notifications.new-travel-item.event.no-items', $transParameters),
                Content::TYPE_NEW_ITINERARY,
                new ItineraryUpdate($entity, 1, true),
                (new Options())
                    ->setPriority(3)
                    ->setDeadlineTimestamp($entity->getUTCStartDate()->getTimestamp())
                    ->setInterruptionLevel(InterruptionLevel::ACTIVE)
            ),
            $devices
        );
    }

    protected function sendNewReservation(Reservation $entity, $additionalItemsCount, array $devices)
    {
        if (!($geotag = $entity->getGeotagid())) {
            return;
        }

        if (ListenerUtils::isAnyEmptyParams([
            $hotelName = htmlspecialchars_decode($entity->getHotelname()),
            $hotelLocation = htmlspecialchars_decode($this->getCityLocation($geotag)),
        ])) {
            return;
        }

        $baseTransParams = [
            '%hotel-name%' => $hotelName,
            '%hotel-location%' => $hotelLocation,
            '%items-count%' => $additionalItemsCount,
            '%checkin-date%' => $this->getDateFormatter($entity->getStartDate(), 'long', null),
        ];

        $this->sender->send(
            new Content(
                $this->getAddedItineraryTitle($additionalItemsCount + 1),
                $additionalItemsCount ?
                    new TransChoice('push-notifications.new-travel-item.hotel', $additionalItemsCount, $baseTransParams) :
                    new Trans('push-notifications.new-travel-item.hotel.no-items', $baseTransParams),
                Content::TYPE_NEW_ITINERARY,
                new ItineraryUpdate($entity, $additionalItemsCount + 1, true),
                (new Options())
                    ->setPriority(3)
                    ->setDeadlineTimestamp($entity->getUTCEndDate()->getTimestamp())
                    ->setInterruptionLevel(InterruptionLevel::ACTIVE)
            ),
            $devices
        );
    }

    /**
     * @param string $dateFormat
     * @param string $timeFormat
     * @return \Closure
     */
    protected function getDateFormatter(\DateTimeInterface $date, $dateFormat, $timeFormat)
    {
        return function ($id, $params, $domain, $locale) use ($date, $dateFormat, $timeFormat) {
            return $this->localizer->formatDateTime($date, $dateFormat, $timeFormat, $locale);
        };
    }

    /**
     * @return string|null
     */
    protected function getCityLocation(Geotag $geotag)
    {
        if (
            StringUtils::isEmpty($countryCode = $geotag->getCountryCode())
            || empty($country = $this->countryRep->findOneBy(['code' => strtoupper($countryCode)]))
        ) {
            return null;
        }

        $cityLocation = null;

        if (
            $country->getHavestates()
            && !StringUtils::isEmpty($state = $geotag->getState(true))
            && !StringUtils::isEmpty($city = $geotag->getCity())
        ) {
            $cityLocation = "{$city}, {$state}";
        } elseif (
            !StringUtils::isEmpty($city = $geotag->getCity())
            && !StringUtils::isEmpty($country = $geotag->getCountry())
        ) {
            $cityLocation = "{$city}, {$country}";
        }

        return $cityLocation;
    }

    protected function sendNewTripsegment(Tripsegment $entity, $additionalItemsCount, $deadlineTimestamp, array $devices)
    {
        $parameters = ListenerUtils::decodeStrings([
            '%departure-name%' => $entity->getDepcode(),
            '%arrival-name%' => $entity->getArrcode(),
            '%airline-name%' => $this->resolveAirline($entity),
            '%items-count%' => $additionalItemsCount,
            '%departure-date%' => $this->getDateFormatter($entity->getStartDate(), 'long', null),
        ]);

        if (ListenerUtils::isAnyEmptyParams($parameters)) {
            return;
        }

        $parameters['%flight-number%'] = $flightNumber = $this->getFlightNumber($entity);

        $this->sender->send(
            new Content(
                $this->getAddedItineraryTitle($additionalItemsCount + 1),
                $additionalItemsCount ?
                    new TransChoice(
                        isset($flightNumber) ?
                            'push-notifications.new-travel-item.tripsegment' :
                            'push-notifications.new-travel-item.tripsegment.no-flight-number',
                        $additionalItemsCount,
                        $parameters
                    ) :
                    new Trans(
                        isset($flightNumber) ?
                            'push-notifications.new-travel-item.tripsegment.no-items' :
                            'push-notifications.new-travel-item.tripsegment.no-items.no-flight-number',
                        $parameters
                    ),
                Content::TYPE_NEW_ITINERARY,
                new ItineraryUpdate($entity, $additionalItemsCount + 1, true),
                (new Options())
                    ->setDeadlineTimestamp($deadlineTimestamp)
                    ->setPriority(3)
                    ->setInterruptionLevel(InterruptionLevel::ACTIVE)
            ),
            $devices
        );
    }

    /**
     * @return TransChoice
     */
    protected function getAddedItineraryTitle($itemsCount)
    {
        return new TransChoice('push-notifications.new-travel-item', $itemsCount);
    }

    protected function getFlightNumber(Tripsegment $tripsegment)
    {
        if (
            !StringHandler::isEmpty($flightNumber = $tripsegment->getFlightNumber())
            && !StringHandler::isEmpty(preg_replace('/[^0-9]/', '', $flightNumber))
        ) {
            return $flightNumber;
        }

        return null;
    }

    /**
     * @return string
     */
    protected function resolveAirline(Tripsegment $tripsegment)
    {
        $tripInfo = TripInfo::createFromTripSegment($tripsegment);

        if (isset($tripInfo->primaryTripNumberInfo->companyInfo) && !empty($companyName = $tripInfo->primaryTripNumberInfo->companyInfo->companyName)) {
            return $companyName;
        }

        return null;
    }

    /**
     * @return string|null
     */
    protected function getTripDestination(Tripsegment $tripsegment)
    {
        if (
            ($geotag = $tripsegment->getArrgeotagid())
            && (null !== ($name = $this->getGeotagDestination($geotag)))
        ) {
            return $name;
        } elseif (
            !StringHandler::isEmpty($name = $tripsegment->getArrname())
            && (mb_strlen($name) <= self::TRIP_DESTINATION_LENGTH_LIMIT)
        ) {
            return $name;
        } elseif (!StringHandler::isEmpty($name = $tripsegment->getArrcode())) {
            return $name;
        }

        return null;
    }

    /**
     * @return string|null
     */
    protected function getGeotagDestination(Geotag $geotag)
    {
        if (
            in_array($geotag->getCountryCode(), ['US', 'CA'], true)
            && !StringHandler::isEmpty($state = $geotag->getState(true))
            && !StringHandler::isEmpty($city = $geotag->getCity())
            && (mb_strlen($name = "{$city}, {$state}") <= self::TRIP_DESTINATION_LENGTH_LIMIT)
        ) {
            return $name;
        } elseif (
            !StringHandler::isEmpty($address = $geotag->getAddress())
            && (strlen($address) <= self::TRIP_DESTINATION_LENGTH_LIMIT)
        ) {
            return $address;
        }

        return null;
    }

    /**
     * @param Properties[] $changedProperties
     * @param MobileDevice[] $devices
     */
    protected function sendChangedItineraries($changedProperties, $devices)
    {
        $changedProperties = array_filter($changedProperties, function ($property) {
            return (bool) $property->getEntity();
        });
        $tripSegments = [];

        foreach ($changedProperties as $changedProperty) {
            if (
                /** @var Tripsegment $entity */
                (($entity = $changedProperty->getEntity()) instanceof Tripsegment)
                && !$entity->getHidden()
                && !$entity->getTripid()->getHidden()
            ) {
                $tripSegments[$changedProperty->sourceId] = [
                    'sourceId' => $changedProperty->sourceId,
                    'segment' => $entity,
                    'property' => $changedProperty,
                ];
            }
        }

        $propertiesByTripSegment = [];

        if ($tripSegments) {
            $stmt = $this->entityManager->getConnection()->executeQuery(
                "
                SELECT
                    SourceID,
                    Property,
                    OldVal,
                    NewVal
                FROM DiffChange
                WHERE 
                    SourceID IN (:sourceIds) AND
                    Property IN ('Seats')
                ORDER BY 
                    SourceID ASC, 
                    ChangeDate ASC
            ",
                [':sourceIds' => array_column($tripSegments, 'sourceId')],
                [':sourceIds' => Connection::PARAM_STR_ARRAY]
            );

            while ($row = $stmt->fetch()) {
                $sourceId = $row['SourceID'];
                $propertiesByTripSegment[$sourceId]['segment'] = $tripSegments[$sourceId]['segment'];
                $propertiesByTripSegment[$sourceId]['changes'][$row['Property']] = [
                    'old' => $row['OldVal'],
                    'new' => $row['NewVal'],
                ];
                $propertiesByTripSegment[$sourceId]['properties'] = $tripSegments[$sourceId]['property']->values;
            }
        }

        foreach ($propertiesByTripSegment as $sourceId => $tripSegmentData) {
            if (!(count($tripSegmentData['changes']) === 1)) {
                continue;
            }

            $nowTimestamp = time();
            /** @var Tripsegment $tripsegment */
            $tripsegment = $tripSegmentData['segment'];

            if (
                StringHandler::isEmpty($departureName = $tripsegment->getDepcode())
                || StringHandler::isEmpty($arrivalName = $tripsegment->getArrcode())
                || StringHandler::isEmpty($airlineName = $this->resolveAirline($tripsegment))
            ) {
                continue;
            }

            $baseTransParams = [
                '%departure-name%' => $departureName,
                '%arrival-name%' => $arrivalName,
                '%airline-name%' => $airlineName,
                '%flight-number%' => $flightNumber = $this->getFlightNumber($tripsegment),
                '%departure-date%' => $this->getDateFormatter($tripsegment->getStartDate(), 'long', null),
            ];

            switch ($property = key($tripSegmentData['changes'])) {
                case 'Seats':
                    $transParams = array_merge(
                        [
                            '%old-seats%' => $this->listNormalizer(',', $tripSegmentData['changes'][$property]['old']),
                            '%new-seats%' => $this->listNormalizer(',', $tripSegmentData['changes'][$property]['new']),
                        ],
                        $baseTransParams
                    );

                    $this->sender->send(
                        new Content(
                            new Trans('push-notifications.seats-assignments-changed.title'),
                            new Trans(
                                isset($flightNumber) ?
                                'push-notifications.seats-assignments-changed' :
                                'push-notifications.seats-assignments-changed.no-flight-number',
                                $transParams
                            ),
                            Content::TYPE_CHANGED_ITINERARY,
                            $tripsegment,
                            (new Options())
                                ->setPriority(5)
                                ->setDeadlineTimestamp($tripsegment->getUTCStartDate()->getTimestamp())
                                ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                        ),
                        $devices
                    );

                    break;

                default:
                    continue 2;
            }
        }
    }

    /**
     * @param Properties[] $addedProperties
     * @param MobileDevice[] $devices
     */
    private function sendAddedItineraries(array $addedProperties, array $devices)
    {
        /** @var Itinerary[] $filteredItineraries */
        $filteredItineraries = [];

        foreach ($addedProperties as $addedProperty) {
            $entity = $addedProperty->getEntity();

            if ($entity instanceof Tripsegment) {
                $trip = $entity->getTripid();

                if (!$trip) {
                    continue;
                }

                if ($trip->getCopied()) {
                    continue;
                }
            } elseif (
                $entity instanceof Rental
                || $entity instanceof Reservation
                || $entity instanceof Restaurant
            ) {
                if ($entity->getCopied()) {
                    continue;
                }
            } else {
                continue;
            }

            // workaround, DiffTracker will send fake new segments when Trip.AccountID is changed
            // this could happen when Trip gathered from two accounts for example AA and Amex
            // each account update will rewrite Trip.AccountID
            // TODO: rewrite DiffTracker to track changes by events before/after itinerary update, not by account snapshots, see #16986
            if ($entity instanceof Tripsegment) {
                $itinerary = $entity->getTripid();
            } else {
                $itinerary = $entity;
            }
            $cacheKey = "it_added_" . $itinerary->getIdString();

            if ($itinerary->getCreateDate()->getTimestamp() < (time() - 3600) || $this->memcached->get($cacheKey) !== false) {
                // prevent double push sending, when there are only new properties added
                continue;
            }
            $this->memcached->set($cacheKey, time(), 7200);

            $filteredItineraries[] = $entity;
        }

        if (!$filteredItineraries) {
            return;
        }

        usort($filteredItineraries, function ($a, $b) {
            return $a->getUTCStartDate()->getTimestamp() - $b->getUTCStartDate()->getTimestamp();
        });
        $tripsegments = array_values(array_filter(
            $filteredItineraries,
            function ($itinerary) {
                return $itinerary instanceof Tripsegment;
            }
        ));
        $this->sendItinerary(
            $filteredItineraries[0],
            count($filteredItineraries) - 1,
            $tripsegments ?
                $tripsegments[count($tripsegments) - 1]->getUTCEndDate()->getTimestamp() - SECONDS_PER_HOUR :
                null,
            $devices
        );
    }

    private function listNormalizer(string $delimeter, string $value): string
    {
        return it(\explode($delimeter, $value))
            ->mapByTrim()
            ->collect()
            ->joinToString($delimeter . ' ');
    }
}

/**
 * @param string[] $variants
 * @return string
 */
function joinPlural(array $variants)
{
    return implode('|', $variants);
}
