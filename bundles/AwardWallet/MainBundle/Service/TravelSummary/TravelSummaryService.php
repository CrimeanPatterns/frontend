<?php

namespace AwardWallet\MainBundle\Service\TravelSummary;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Region;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Service\DateTimeResolver;
use AwardWallet\MainBundle\Service\MileValue\LongHaulDetector;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Airport;
use AwardWallet\MainBundle\Service\TravelSummary\Data\FlightsResult;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Item;
use AwardWallet\MainBundle\Service\TravelSummary\Data\ItineraryModelInterface;
use AwardWallet\MainBundle\Service\TravelSummary\Data\LocationStat;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Point;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Reservation as ReservationModel;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Route;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Segment;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Summary;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Totals;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip as TripModel;
use AwardWallet\MainBundle\Service\TravelSummary\Data\TripSegment;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Flight;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Hotel;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Parking;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Rental;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Restaurant;
use AwardWallet\MainBundle\Service\TravelSummary\DataProvider\Trip;
use AwardWallet\MainBundle\Service\TravelSummary\Statistics\Distance;
use AwardWallet\MainBundle\Service\TravelSummary\Statistics\Travel;
use AwardWallet\MainBundle\Timeline\Manager;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TravelSummaryService
{
    private EntityManagerInterface $em;
    private LongHaulDetector $longHaulDetector;
    private Manager $timelineManager;
    private DateTimeResolver $dateTimeResolver;

    private Flight $flightProvider;
    private Hotel $hotelProvider;
    private Parking $parkingProvider;
    private Rental $rentalProvider;
    private Restaurant $restaurantProvider;
    private Trip $tripProvider;

    public function __construct(
        EntityManagerInterface $em,
        LongHaulDetector $longHaulDetector,
        Manager $timelineManager,
        DateTimeResolver $dateTimeResolver,
        Flight $flightProvider,
        Hotel $hotelProvider,
        Parking $parkingProvider,
        Rental $rentalProvider,
        Restaurant $restaurantProvider,
        Trip $tripProvider
    ) {
        $this->em = $em;
        $this->longHaulDetector = $longHaulDetector;
        $this->timelineManager = $timelineManager;
        $this->dateTimeResolver = $dateTimeResolver;
        $this->flightProvider = $flightProvider;
        $this->hotelProvider = $hotelProvider;
        $this->parkingProvider = $parkingProvider;
        $this->rentalProvider = $rentalProvider;
        $this->restaurantProvider = $restaurantProvider;
        $this->tripProvider = $tripProvider;
    }

    public function buildPeriodSummary(
        Usr $user,
        ?Useragent $userAgent = null,
        \DateTime $startDate,
        \DateTime $endDate,
        bool $onlyFlights = false
    ): Summary {
        $owner = new Owner($user, $userAgent);
        $flights = $this->flightProvider->getData($owner, $startDate, $endDate);

        if (!$onlyFlights) {
            $hotels = $this->hotelProvider->getData($owner, $startDate, $endDate);
            $parkingLots = $this->parkingProvider->getData($owner, $startDate, $endDate);
            $rentals = $this->rentalProvider->getData($owner, $startDate, $endDate);
            $restaurants = $this->restaurantProvider->getData($owner, $startDate, $endDate);
            $trips = $this->tripProvider->getData($owner, $startDate, $endDate);

            $statisticsTravel = new Travel($flights, $hotels, $parkingLots, $rentals, $restaurants, $trips, $this->longHaulDetector);
            $statisticsDistance = new Distance($flights, $rentals, $trips);
        } else {
            $statisticsTravel = new Travel($flights, [], [], [], [], [], $this->longHaulDetector);
            $statisticsDistance = new Distance($flights, [], []);
        }

        if ($userAgent && $userAgent->getClientid()) {
            $user = $userAgent->getClientid();
        }

        $plans = $this->getPlans($user, $startDate, $endDate);
        $iata = [
            'airports' => [],
            'airlines' => [],
            'countries' => [],
        ];

        $flightsResult = $this->prepareAirportData($flights, $iata, $plans);

        if (!$onlyFlights) {
            $reservationsSorted = $this->prepareReservationData(array_merge($hotels, $parkingLots, $restaurants), $iata, $plans);
            $rentalsSorted = $this->prepareRentalData($rentals, $iata, $plans);
            $tripsSorted = $this->prepareTripData($trips, $iata, $plans);
            $locationStat = $this->buildLocationStatistics(array_merge($flights, $hotels, $parkingLots, $rentals, $restaurants, $trips), $user);
        } else {
            $reservationsSorted =
            $rentalsSorted =
            $tripsSorted = [];
            $locationStat = $this->buildLocationStatistics($flights, $user);
        }

        $countriesSorted = $this->buildSorted($locationStat->getCountryCodes(), $iata['countries']);
        $noData = empty($flightsResult->getAirports()) && empty($reservationsSorted) && empty($rentalsSorted) && empty($tripsSorted);

        return new Summary(
            $noData,
            $flightsResult->getAirlines(),
            $countriesSorted,
            $flightsResult->getAirports(),
            array_merge($reservationsSorted, $rentalsSorted, $tripsSorted),
            new Totals(
                array_reduce($flightsResult->getAirlines(), [$this, 'calcTotal']),
                array_reduce($countriesSorted, [$this, 'calcTotal']),
                array_reduce($flightsResult->getAirports(), [$this, 'calcTotal'])
            ),
            $statisticsTravel,
            $locationStat,
            $flightsResult->getRoutes(),
            $statisticsDistance,
            (int) ($startDate->format('Y') - 1)
        );
    }

    public function buildAvailableUserAgents(Usr $user): array
    {
        $availableTimelines = $this->timelineManager->getTotals($user);
        $entities = $this->em->getRepository(Useragent::class)->findBy(['useragentid' => array_keys($availableTimelines)]);

        $result = [null => $user->getFullName()];

        /** @var Useragent $userAgent */
        foreach ($entities as $userAgent) {
            if ($userAgent->getClientid() && $userAgent->getClientid()->isBusiness()) {
                continue;
            }
            $result[$userAgent->getUseragentid()] = $userAgent;
        }

        return $result;
    }

    private function calcTotal($total, Item $item)
    {
        return $total + $item->getValue();
    }

    private function incrementValue(array &$source, $value)
    {
        if (!isset($source[$value])) {
            $source[$value] = 0;
        }

        $source[$value]++;
    }

    private function buildSegment($depDate, $depCode, $arrCode, $tripSegmentId, array $plans): Segment
    {
        $depDateTimestamp = $depDate->getTimestamp();
        $travelPlan = '';

        /** @var Plan $plan */
        foreach ($plans as $plan) {
            /* detect segment travel plan */
            if ($depDateTimestamp >= $plan->getStartDate()->getTimestamp() && $depDateTimestamp <= $plan->getEndDate()->getTimestamp()) {
                $travelPlan = $plan->getName();

                break;
            }
        }

        return new Segment(
            $depDate,
            $depCode,
            $arrCode,
            $travelPlan,
            $tripSegmentId
        );
    }

    /**
     * Collects an array of flight segments, grouped by markers on the map.
     *
     * @param TripModel[] $flights
     */
    private function prepareAirportData(array $flights, array &$iata, array $plans): FlightsResult
    {
        $airlines =
        $segments =
        $airports =
        $markers =
        $routes = [];

        foreach (it([null])
            ->chain($flights)
            ->chain([null])
            ->sliding(3) as [$previous, $current, $next]
        ) {
            /** @var TripModel $current */
            $depCode = $current->getDeparture()->getAirCode();
            $arrCode = $current->getArrival()->getAirCode();

            if ($depCode === null || $arrCode === null) {
                continue;
            }

            if ($current->getAirlineCode() !== null) {
                $this->incrementValue($airlines, $current->getAirlineCode());

                if ($current->getTitle() !== null) {
                    $iata['airlines'][$current->getAirlineCode()] = $current->getTitle();
                }
            }

            $dep = $current->getDeparture();
            $arr = $current->getArrival();

            if ($arr->getCountry() !== null && $arr->getCountryCode() !== null) {
                $iata['countries'][$arr->getCountryCode()] = $arr->getCountry();
            }

            foreach ([$depCode, $arrCode] as $code) {
                if (!isset($segments[$code])) {
                    $segments[$code] = [];
                }
                $segments[$code][] = $this->buildSegment($current->getStartDate(), $depCode, $arrCode, $current->getSegmentId(), $plans);
            }

            $this->incrementValue($airports, $depCode);

            if (!$this->isLayover($current, $next)) {
                $this->incrementValue($airports, $arrCode);
            }

            $markers[$depCode] = new Airport($depCode, new Point($dep->getLatitude(), $dep->getLongitude()));
            $markers[$arrCode] = new Airport($arrCode, new Point($arr->getLatitude(), $arr->getLongitude()));

            $routes[] = new Route(
                new Point($dep->getLatitude(), $dep->getLongitude()),
                new Point($arr->getLatitude(), $arr->getLongitude())
            );
        }

        foreach ($segments as $airCode => $data) {
            $markers[$airCode]->setSegments($data);
        }

        $aircodeEntities = $this->em->getRepository(Aircode::class)->findBy(['aircode' => array_keys($airports)]);

        /** @var Aircode[] $aircodeEntities */
        foreach ($aircodeEntities as $entity) {
            $iata['airports'][$entity->getAircode()] = $entity->getAirportName();
            $markers[$entity->getAircode()]->setTitle($entity->getAirname());
        }

        return new FlightsResult(
            $this->buildSorted($airports, $iata['airports'], $markers),
            $this->buildSorted($airlines, $iata['airlines']),
            $routes
        );
    }

    /**
     * Collect an array of reservation segments without a route, grouped by markers on the map.
     *
     * @param ReservationModel[] $reservations
     * @return Item[]
     */
    private function prepareReservationData(array $reservations, array &$iata, array $plans): array
    {
        $segments = [];
        $locations = [];
        $markers = [];

        foreach ($reservations as $reservation) {
            $marker = $reservation->getMarker()->setLocationName($reservation->getTitle());
            $coordinates = $marker->getLatitude() . ',' . $marker->getLongitude();
            $iata['countries'][$marker->getCountryCode()] = $marker->getCountry();

            if (!isset($markers[$coordinates]) && $foundMarker = $this->findMarkerByCoordinates($marker, $markers)) {
                $coordinates = $foundMarker;
            }

            $segments[$coordinates][] = (new TripSegment($reservation, $reservation->getPrefix(), $plans))
                ->setDetails($marker->getLocationName());

            $locations[$coordinates] = $reservation->getCurrentLocation();

            if (!isset($markers[$coordinates])) {
                $markers[$coordinates] = $marker;
            }

            $previous = $markers[$coordinates];

            if (!$this->compareReservations($previous, $marker)) {
                $markers[$coordinates] = $marker;
            } elseif (
                $this->compareReservations($previous, $marker)
                && $previous->getLocationName() !== $marker->getLocationName()
            ) {
                $previous->setDifferentTitles(true);
            }
        }

        foreach ($segments as $coordinates => $data) {
            $filtered = $this->filterReservations($data);
            /** @var TripSegment $previousSegment */
            $previousSegment = null;
            $differentTitles = false;

            foreach ($filtered as $segment) {
                if ($previousSegment !== null && $previousSegment->getDetails() !== $segment->getDetails()) {
                    $differentTitles = true;

                    break;
                }

                $previousSegment = $segment;
            }

            $markers[$coordinates]->setDifferentTitles($differentTitles);
            $markers[$coordinates]->setSegments($filtered);
        }

        $source = [];

        foreach ($markers as $coordinates => $marker) {
            $source[$coordinates] = count($marker->getSegments());
        }

        $reservationsSorted = $this->buildSorted($source, $locations, $markers);

        return array_map(function (Item $item) {
            $marker = $item->getPayload();

            return $item->setKey($marker->getLocationName());
        }, $reservationsSorted);
    }

    /**
     * Collect an array of car rental reservation segments grouped by markers on the map.
     *
     * @param TripModel[] $trips
     * @return Item[]
     */
    private function prepareRentalData(array $trips, array &$iata, array $plans): array
    {
        $segments = [];
        $locations = [];
        $markers = [];

        foreach ($trips as $trip) {
            $dep = $trip->getDeparture()->setLocationName($trip->getTitle());
            $arr = $trip->getArrival()->setLocationName($trip->getTitle());
            $departure = $dep->getLatitude() . ',' . $dep->getLongitude();
            $arrival = $arr->getLatitude() . ',' . $arr->getLongitude();

            $iata['countries'][$dep->getCountryCode()] = $dep->getCountry();
            $iata['countries'][$arr->getCountryCode()] = $arr->getCountry();

            $segments[$departure][] = new TripSegment($trip, \AwardWallet\MainBundle\Entity\Rental::SEGMENT_MAP_START, $plans);
            $key = ($departure !== $arrival) ? $arrival : $departure;
            $segments[$key][] = new TripSegment($trip, \AwardWallet\MainBundle\Entity\Rental::SEGMENT_MAP_END, $plans);

            $locations[$departure] = $trip->getStartLocation();

            if ($departure !== $arrival) {
                $locations[$arrival] = $trip->getEndLocation();
            }

            $markers[$departure] = $dep;

            if ($departure !== $arrival) {
                $markers[$arrival] = $arr;
            }

            if ($departure !== $arrival) {
                $direction = new Route(
                    new Point($dep->getLatitude(), $dep->getLongitude()),
                    new Point($arr->getLatitude(), $arr->getLongitude())
                );
                $dep->addDirection($direction);
                $arr->addDirection($direction);
            }
        }

        foreach ($segments as $coordinates => $data) {
            $markers[$coordinates]->setSegments($data);
        }

        $source = [];

        foreach ($markers as $coordinates => $marker) {
            $source[$coordinates] = count($marker->getSegments());
        }

        $rentalsSorted = $this->buildSorted($source, $locations, $markers);

        return array_map(function (Item $item) {
            /** @var Marker $marker */
            $marker = $item->getPayload();

            return $item->setKey($marker->getLocationName());
        }, $rentalsSorted);
    }

    /**
     * Collect an array of trip segments grouped by markers on the map.
     *
     * @param TripModel[] $trips
     * @return Item[]
     */
    private function prepareTripData(array $trips, array &$iata, array $plans): array
    {
        $locations = [];
        $markers = [];
        $directions = $this->getDirectionsForTrips($trips);

        foreach ($trips as $trip) {
            $dep = $trip->getDeparture()->setLocationName($trip->getTitle());
            $arr = $trip->getArrival()->setLocationName($trip->getTitle());
            $departure = $dep->getLatitude() . ',' . $dep->getLongitude();
            $arrival = $arr->getLatitude() . ',' . $arr->getLongitude();

            $iata['countries'][$dep->getCountryCode()] = $dep->getCountry();
            $iata['countries'][$arr->getCountryCode()] = $arr->getCountry();

            $locations[$departure] = $trip->getStartLocation();
            $locations[$arrival] = $trip->getEndLocation();

            if (!isset($markers[$departure])) {
                $markers[$departure] = $dep;
            }

            if (!isset($markers[$arrival])) {
                $markers[$arrival] = $arr;
            }
            $markers[$departure]->addSegment(
                (new TripSegment($trip, $trip->getPrefix(), $plans))->setType(TripSegment::TYPE_DEPARTURE)
            );
            $markers[$arrival]->addSegment(
                (new TripSegment($trip, $trip->getPrefix(), $plans))->setType(TripSegment::TYPE_ARRIVAL)
            );

            if (isset($directions[$trip->getId()])) {
                $dep->addDirection($directions[$trip->getId()]);
                $arr->addDirection($directions[$trip->getId()]);
            }
        }

        $source = [];

        foreach ($markers as $coordinates => $marker) {
            $source[$coordinates] = count($marker->getSegments());
        }

        $tripsSorted = $this->buildSorted($source, $locations, $markers);

        return array_map(function (Item $item) {
            $marker = $item->getPayload();

            return $item->setKey($marker->getLocationName());
        }, $tripsSorted);
    }

    /**
     * Returns a list of routes for buses, trains, ferries and transfers.
     *
     * @param TripModel[] $trips
     * @return array an array of routes, where keys are trip IDs and values are `Route` objects
     * with a list of waypoints (if any)
     */
    private function getDirectionsForTrips(array $trips): array
    {
        $directions = [];

        foreach ($trips as $trip) {
            if ($trip->getDeparture()->getCategory() === 'cruise') {
                continue;
            }

            $dep = $trip->getDeparture()->setLocationName($trip->getTitle());
            $arr = $trip->getArrival()->setLocationName($trip->getTitle());

            if (!isset($directions[$trip->getId()])) {
                $directions[$trip->getId()] = new Route(
                    new Point($dep->getLatitude(), $dep->getLongitude()),
                    new Point($arr->getLatitude(), $arr->getLongitude())
                );
            } else {
                /** @var Route $previous */
                $previous = $directions[$trip->getId()];

                if (
                    ($previous->getDep()->getLat() === $arr->getLatitude() && $previous->getDep()->getLng() === $arr->getLongitude())
                    && ($previous->getArr()->getLat() === $dep->getLatitude() && $previous->getArr()->getLng() === $dep->getLongitude())
                ) {
                    // This indicates that the trip is a return route
                    continue;
                }

                $directions[$trip->getId()]
                    ->addWaypoint(new Point($dep->getLatitude(), $dep->getLongitude()))
                    ->setArr(new Point($arr->getLatitude(), $arr->getLongitude()));
            }
        }

        return $directions;
    }

    /**
     * Searches for the nearest marker based on geographic coordinates.
     *
     * @param Marker $marker a point on the map related to the current reservation
     * @param array $markerList a list of available markers
     * @return string|null a string of coordinates or 'null' if no marker was found
     */
    private function findMarkerByCoordinates(Marker $marker, array $markerList): ?string
    {
        $foundMarker = null;

        foreach ($markerList as $coordinates => $item) {
            /** @var Marker $item */
            [$latitude, $longitude] = explode(',', $coordinates);

            if (
                Geo::distance($marker->getLatitude(), $marker->getLongitude(), $latitude, $longitude) < 0.02
                && $marker->getCategory() === $item->getCategory()
            ) {
                $pattern = '/^\D*(\d{1,5})/';
                preg_match($pattern, $marker->getAddress(), $matches);
                preg_match($pattern, $item->getAddress(), $matches2);

                // Compares house numbers if they are in the address
                if (isset($matches[1], $matches2[1]) && $matches[1] == $matches2[1]) {
                    $foundMarker = $coordinates;

                    break;
                }
            }
        }

        return $foundMarker;
    }

    /**
     * Compare two reservations by category and geographic coordinates.
     */
    private function compareReservations(Marker $previous, Marker $current): bool
    {
        return $previous->getCategory() === $current->getCategory()
            && Geo::distance(
                $previous->getLatitude(),
                $previous->getLongitude(),
                $current->getLatitude(),
                $current->getLongitude()
            ) < 3;
    }

    /**
     * Filter segments by the rule, if there is more than one segment for the same date and some of them
     * do not have a confirmation number, then such segments will be hidden.
     *
     * @param TripSegment[] $segments segments of reservations for one marker
     */
    private function filterReservations(array $segments): array
    {
        $numbers = [];

        foreach ($segments as $segment) {
            $date = $segment->getStartDate()->format('Y-m-d');

            if (!isset($numbers[$date]['hasNumber']) || $numbers[$date]['hasNumber'] === false) {
                $numbers[$date]['hasNumber'] = $segment->isHasConfirmationNumber();
            }

            if (!isset($numbers[$date]['count'])) {
                $numbers[$date]['count'] = 1;
            } else {
                $numbers[$date]['count']++;
            }
        }

        return array_filter($segments, function ($segment) use ($numbers) {
            $date = $segment->getStartDate()->format('Y-m-d');

            return ($numbers[$date]['count'] === 1)
                || ($numbers[$date]['count'] > 1 && $numbers[$date]['hasNumber'] && $segment->isHasConfirmationNumber())
                || ($numbers[$date]['count'] > 1 && !$numbers[$date]['hasNumber']);
        });
    }

    /**
     * @return Item[]
     */
    private function buildSorted(array $source, array $title = [], array $payload = []): array
    {
        /** @var Item[] $result */
        $result = [];

        foreach ($source as $key => $value) {
            $result[] = new Item(
                $key,
                $value,
                $title[$key] ?? $key,
                $payload[$key] ?? null
            );
        }

        usort($result, function (Item $a, Item $b) {
            return $b->getValue() <=> $a->getValue();
        });

        return $result;
    }

    private function isLayover($first, $second): bool
    {
        // Todo: remake segments into objects
        $first = $first instanceof TripModel ? $this->tripToArray($first) : $first;
        $second = $second instanceof TripModel ? $this->tripToArray($second) : $second;

        if (
            is_null($first)
            || is_null($second)
            || !isset($first['ArrDateLocal'], $second['DepDateLocal'])
            || !isset($first['ArrCode'], $second['DepCode'])
        ) {
            return false;
        }

        $diffHours = ($second['DepDateLocal']->getTimestamp() - $first['ArrDateLocal']->getTimestamp()) / 3600;

        return $first['ArrCode'] === $second['DepCode'] && $diffHours > 0 && $diffHours < 24;
    }

    /**
     * Get the travel plans of the current user.
     *
     * @return Plan[]
     */
    private function getPlans(Usr $user, \DateTime $startDate, \DateTime $endDate): array
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('pl')
            ->from(Plan::class, 'pl');

        $query = $queryBuilder
            ->where('pl.user = :userId')
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->andX('pl.startDate >= :startDate', 'pl.startDate < :endDate'),
                $queryBuilder->expr()->andX('pl.endDate >= :startDate', 'pl.endDate < :endDate')
            ))
            ->setParameters([
                ':userId' => $user->getId(),
                ':startDate' => $startDate,
                ':endDate' => $endDate,
            ])->getQuery();

        return $query->getResult();
    }

    /**
     * Get the list of countries along with their related regions.
     *
     * @param array $countryCodes an array of two-letter country codes ("US", "FR", "DE")
     * @return Country[]
     */
    private function getCountriesWithRegions(array $countryCodes): array
    {
        if (empty($countryCodes)) {
            return [];
        }

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('co')
            ->from(Country::class, 'co');

        $query = $queryBuilder
            ->leftJoin('co.regions', 're')
            ->where($queryBuilder->expr()->in('co.code', $countryCodes))
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Calculates data with statistics by country, city and continent.
     *
     * @param TripModel[]|ReservationModel[] $trips
     */
    private function buildLocationStatistics(array $trips, Usr $user): LocationStat
    {
        // @see https://redmine.awardwallet.com/issues/22932
        $isCalcHomeRegion = false;
        $homeCountry = $user->getRegion();
        $homeCity = $user->getCity();
        $fromCountry = null;
        $countries = [];
        $cities = [];

        foreach (
            it([null])
                ->chain(
                    it($trips)
                        ->map(function ($trip) {
                            if ($trip instanceof TripModel) {
                                return $this->tripToArray($trip);
                            } elseif ($trip instanceof ReservationModel) {
                                return $this->reservationToArray($trip);
                            }
                        })
                )
                ->chain([null])
                ->sliding(3) as [$previous, $current, $next]
        ) {
            $isLayover = $this->isLayover($current, $next);
            $isHomeCountry = $isCalcHomeRegion && isset($current['ArrCountryCode'], $homeCountry) && $current['ArrCountryCode'] === $homeCountry;
            $isHomeCity = $isCalcHomeRegion && isset($current['DepCityName'], $homeCity) && $current['DepCityName'] === $homeCity;

            if (isset($current['ArrCountryCode']) && $current['ArrCountryCode'] !== $fromCountry && !$isLayover && !$isHomeCountry) {
                $fromCountry = $current['ArrCountryCode'];
                $this->incrementValue($countries, $current['ArrCountryCode']);
            }

            if (!$isLayover && !$isHomeCity && $current['DepCityName'] !== null) {
                $this->incrementValue($cities, $current['DepCityName']);
            }
        }

        $statistics = new LocationStat(
            count($countries),
            count($cities),
            count($this->buildContinentStatistics($countries))
        );
        $statistics->setCountryCodes($countries);

        return $statistics;
    }

    /**
     * Collects an array with all continents from a passed list of countries.
     *
     * @param array $countries an array in which the keys are two-letter codes and the values are the number of visits
     */
    private function buildContinentStatistics(array $countries): array
    {
        $continents = [];
        $countryEntities = $this->getCountriesWithRegions(array_keys($countries));

        foreach ($countryEntities as $country) {
            if ($country->getRegions()->isEmpty()) {
                continue;
            }

            $parentsId = $country->getRegions()[0]->getId();
            $continent = $this->em->getRepository(Region::class)->findRegionContinentByParentsID($parentsId);

            if ($continent !== null && !in_array($continent, $continents)) {
                $continents[] = $continent;
            }
        }

        return $continents;
    }

    /**
     * Converts a trip object to an associative array.
     *
     * @param TripModel $trip
     */
    private function tripToArray(ItineraryModelInterface $trip): array
    {
        $row = [
            'TripSegmentID' => $trip->getSegmentId(),
            'AirlineName' => $trip->getTitle(),
            'AirlineCode' => $trip->getAirlineCode(),
            'DepCode' => $trip->getDeparture()->getAirCode(),
            'DepCityName' => $trip->getDeparture()->getCity(),
            'DepLat' => $trip->getDeparture()->getLatitude(),
            'DepLng' => $trip->getDeparture()->getLongitude(),
            'DepDate' => $trip->getStartDate(),
            'ArrCode' => $trip->getArrival()->getAirCode(),
            'ArrLat' => $trip->getArrival()->getLatitude(),
            'ArrLng' => $trip->getArrival()->getLongitude(),
            'ArrDate' => $trip->getEndDate(),
            'ArrCountryCode' => $trip->getArrival()->getCountryCode(),
            'ArrCountryName' => $trip->getArrival()->getCountry(),
        ];

        if ($trip->getDeparture()->getTimeZone() !== null) {
            $row['DepDateLocal'] = $this->dateTimeResolver->resolveByTimeZoneId(
                $row['DepDate'],
                $trip->getDeparture()->getTimeZone()
            );
        }

        if ($trip->getArrival()->getTimeZone() !== null) {
            $row['ArrDateLocal'] = $this->dateTimeResolver->resolveByTimeZoneId(
                $row['ArrDate'],
                $trip->getArrival()->getTimeZone()
            );
        }

        return $row;
    }

    /**
     * Converts a reservation object to an associative array.
     *
     * @param ReservationModel $model
     */
    private function reservationToArray(ItineraryModelInterface $model): array
    {
        $row = [
            'TripSegmentID' => $model->getSegmentId(),
            'AirlineName' => $model->getTitle(),
            'AirlineCode' => null,
            'DepCode' => $model->getMarker()->getAirCode(),
            'DepCityName' => $model->getMarker()->getCity(),
            'DepLat' => $model->getMarker()->getLatitude(),
            'DepLng' => $model->getMarker()->getLongitude(),
            'DepDate' => $model->getStartDate(),
            'ArrCode' => null,
            'ArrLat' => null,
            'ArrLng' => null,
            'ArrDate' => $model->getEndDate(),
            'ArrCountryCode' => $model->getMarker()->getCountryCode(),
            'ArrCountryName' => $model->getMarker()->getCountry(),
        ];

        if ($model->getMarker()->getTimeZone() !== null) {
            $row['DepDateLocal'] = $this->dateTimeResolver->resolveByTimeZoneId(
                $row['DepDate'],
                $model->getMarker()->getTimeZone()
            );
        }

        return $row;
    }
}
