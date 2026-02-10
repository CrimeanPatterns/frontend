<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Formatter;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Airport;
use AwardWallet\MainBundle\Service\TravelSummary\Data\DistanceResult;
use AwardWallet\MainBundle\Service\TravelSummary\Data\FlightStat;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Item;
use AwardWallet\MainBundle\Service\TravelSummary\Data\PeriodDatesResult;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Point;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Route as ServiceRoute;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Segment;
use AwardWallet\MainBundle\Service\TravelSummary\Data\TripSegment;
use AwardWallet\MainBundle\Service\TravelSummary\Formatter\Tip\Formatter;
use AwardWallet\MainBundle\Service\TravelSummary\Formatter\Tip\User;
use AwardWallet\MainBundle\Service\TravelSummary\PeriodDatesHelper;
use AwardWallet\MainBundle\Service\TravelSummary\TravelSummaryService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DesktopFormatter
{
    private UserMailboxCounter $mailboxCounter;
    private UrlGeneratorInterface $urlGenerator;
    private LocalizeService $localizer;
    private TravelSummaryService $travelSummary;
    private Formatter $tipFormatter;
    private PeriodDatesHelper $periodDatesHelper;

    public function __construct(
        UserMailboxCounter $mailboxCounter,
        UrlGeneratorInterface $urlGenerator,
        LocalizeService $localizer,
        TravelSummaryService $travelSummary,
        Formatter $tipFormatter,
        PeriodDatesHelper $periodDatesHelper
    ) {
        $this->mailboxCounter = $mailboxCounter;
        $this->urlGenerator = $urlGenerator;
        $this->localizer = $localizer;
        $this->travelSummary = $travelSummary;
        $this->tipFormatter = $tipFormatter;
        $this->periodDatesHelper = $periodDatesHelper;
    }

    public function format(Usr $user, ?Useragent $userAgent, int $period): array
    {
        $datesResult = $this->periodDatesHelper->getDates($period, $user);
        $result = \array_merge(
            $this->travelSummary->buildPeriodSummary($user, $userAgent, $datesResult->getStartDate(), $datesResult->getEndDate())->jsonSerialize(),
            [
                'travelYear' => $datesResult->getStartDate()->format('Y'),
                'currentPeriod' => (string) $datesResult->getCurrentPeriod(),
                'currentUser' => $userAgent ? (string) $userAgent->getUseragentid() : '',
                'mailboxes' => $this->mailboxCounter->myOrFamilyMember($user->getId(), $userAgent ? $userAgent->getId() : null),
                'isAwPlus' => $user->isAwPlus(),
                'prevDiff' => null,
                'earliestYear' => $datesResult->getEarliestYear(),
            ]
        );

        // only current and previous year
        if (in_array($period, [PeriodDatesHelper::YEAR_TO_DATE, PeriodDatesHelper::LAST_YEAR])) {
            $result['prevDiff'] = $this->buildPrevPeriodDiff($user, $userAgent, $period, $result);
        }

        return $this->doFormat($result, $user, $userAgent, $datesResult);
    }

    private function buildPrevPeriodDiff(Usr $user, ?Useragent $userAgent = null, int $period, $periodSummary): array
    {
        $currentYear = (int) date('Y');
        $startDate = new \DateTime(sprintf('%d-01-01', $currentYear - $period));
        $endDate = new \DateTime(sprintf('%d-01-01', $currentYear - ($period - 1)));
        $prevPeriodSummary = $this->travelSummary->buildPeriodSummary($user, $userAgent, $startDate, $endDate)->jsonSerialize();

        $compareKeyValues = function (string $key) use ($periodSummary, $prevPeriodSummary) {
            $keys = explode('.', $key);

            if (count($keys) > 1) {
                return $this->getValueFromSummary($key, $periodSummary) - $this->getValueFromSummary($key, $prevPeriodSummary);
            }

            return $periodSummary[$key] - $prevPeriodSummary[$key];
        };

        $result = [
            'totalFlights' => $compareKeyValues('travelStatistics.flightStats.totalFlights'),
            'longHaulFlights' => $compareKeyValues('travelStatistics.flightStats.longHaulFlights'),
            'shortHaulFlights' => $compareKeyValues('travelStatistics.flightStats.shortHaulFlights'),
            'hotelNights' => $compareKeyValues('travelStatistics.hotelNights'),
            'rentalCarDays' => $compareKeyValues('travelStatistics.rentalCarDays'),
            'parkingDays' => $compareKeyValues('travelStatistics.parkingDays'),
            'eventsAttended' => $compareKeyValues('travelStatistics.eventsAttended'),
            'restaurantReservations' => $compareKeyValues('travelStatistics.restaurantReservations'),
            'cruisesDays' => $compareKeyValues('travelStatistics.cruisesDays'),
            'totalBuses' => $compareKeyValues('travelStatistics.totalBuses'),
            'totalFerries' => $compareKeyValues('travelStatistics.totalFerries'),
            'totalTrains' => $compareKeyValues('travelStatistics.totalTrains'),

            'countries' => $compareKeyValues('locationStat.countries'),
            'cities' => $compareKeyValues('locationStat.cities'),
            'continents' => $compareKeyValues('locationStat.continents'),
            'distance' => $compareKeyValues('distance.data.distance'),
            'aroundTheWorld' => $compareKeyValues('distance.data.aroundTheWorld'),
        ];

        return $result;
    }

    /**
     * Get the value of the property from `Summary`.
     *
     * @param string $path path to the object property as a dot notation
     * @param array $periodSummary `Data\Summary` object converted to an array
     */
    private function getValueFromSummary($path, $periodSummary)
    {
        $properties = explode('.', $path);

        return (int) array_reduce($properties, function ($carry, $item) {
            $method = 'get' . ucfirst($item);

            return is_array($carry) ? $carry[$item] : $carry->{$method}();
        }, $periodSummary);
    }

    private function doFormat(array $result, Usr $user, ?Useragent $userAgent, PeriodDatesResult $datesResult)
    {
        /** @var DistanceResult $distanceResult */
        $distanceResult = $result['distance']->getData();
        $tipUser = new User($user, $userAgent);

        $result['distance'] = $this->localizer->formatNumber($distanceResult->getDistance());
        $result['aroundTheWorld'] = $this->localizer->formatNumber($distanceResult->getAroundTheWorld());

        if (isset($result['prevDiff'])) {
            $result['prevDiff']['distanceFormatted'] = isset($result['prevDiff']['distance']) ? $this->localizer->formatNumber($result['prevDiff']['distance']) : null;
            $result['prevDiff']['aroundTheWorldFormatted'] = isset($result['prevDiff']['aroundTheWorld']) ? $this->localizer->formatNumber($result['prevDiff']['aroundTheWorld']) : null;
        }

        $result['airports'] =
            it($result['airports'])
            ->map(function (Item $item) use ($tipUser, $datesResult): array {
                $item = $item->jsonSerialize();
                $item['value'] = (int) $item['value'];
                /** @var Airport $payload */
                $payload = $item['payload'];
                $item['payload'] = [
                    'code' => $payload->getCode(),
                    'lat' => (string) $payload->getPoint()->getLat(),
                    'lng' => (string) $payload->getPoint()->getLng(),
                    'airportTitle' => $payload->getTitle(),
                    'category' => 'air',
                    'segments' => it($payload->getSegments())
                        ->map(function (Segment $segment): array {
                            return [
                                'depDate' => $this->localizer->formatDate($segment->getDepDate()),
                                'depCode' => $segment->getDepCode(),
                                'arrCode' => $segment->getArrCode(),
                                'travelPlan' => $segment->getTravelPlan(),
                                'timelineLink' => $this->urlGenerator->generate('aw_timeline_show', ['segmentId' => 'T.' . $segment->getTripSegmentId()]),
                            ];
                        })
                        ->toArray(),
                ];
                $item['tip'] = $this->tipFormatter->formatAirportTip($tipUser, $datesResult, $payload->getCode(), $item['value']);

                return $item;
            })
            ->toArray();

        $result['reservations'] = it($result['reservations'])
            ->map(function (Item $item): array {
                $item = $item->jsonSerialize();
                $item['value'] = (int) $item['value'];
                /** @var \AwardWallet\MainBundle\Service\TravelSummary\Data\Marker $payload */
                $payload = $item['payload'];
                $item['payload'] = [
                    'code' => $payload->getCity(),
                    'lat' => (string) $payload->getLatitude(),
                    'lng' => (string) $payload->getLongitude(),
                    'address' => $payload->getAddress(),
                    'reservationTitle' => $payload->getLocationName(),
                    'category' => $payload->getCategory(),
                    'differentTitles' => $payload->isDifferentTitles(),
                    'segments' => it($payload->getSegments())
                        ->map(function (TripSegment $segment): array {
                            return [
                                'date' => $this->localizer->formatDate($segment->getDate()),
                                'duration' => $segment->getDuration(),
                                'details' => $segment->getDetails(),
                                'timelineLink' => $this->urlGenerator->generate('aw_timeline_show', [
                                    'segmentId' => $segment->getPrefix() . '.' . $segment->getId(),
                                ]),
                                'prefix' => $segment->getPrefix(),
                                'travelPlan' => $segment->getTravelPlan(),
                            ];
                        })
                        ->toArray(),
                    'directions' => it($payload->getDirections())
                        ->map(function (ServiceRoute $route): array {
                            $dep = $route->getDep();
                            $arr = $route->getArr();

                            $direction = [
                                'dep' => ['lat' => (string) $dep->getLat(), 'lng' => (string) $dep->getLng()],
                                'arr' => ['lat' => (string) $arr->getLat(), 'lng' => (string) $arr->getLng()],
                            ];
                            $direction['waypoints'] = it($route->getWaypoints())
                                ->map(function (Point $point): array {
                                    return ['lat' => (string) $point->getLat(), 'lng' => (string) $point->getLng()];
                                })
                                ->toArray();

                            return $direction;
                        })
                        ->toArray(),
                ];

                return $item;
            })
            ->toArray();

        $result['routes'] =
            it($result['routes'])
            ->map(function (ServiceRoute $route) {
                $dep = $route->getDep();
                $arr = $route->getArr();

                return [
                    'arr' => [
                        'lat' => (string) $arr->getLat(),
                        'lng' => (string) $arr->getLng(),
                    ],
                    'dep' => [
                        'lat' => (string) $dep->getLat(),
                        'lng' => (string) $dep->getLng(),
                    ],
                ];
            })
            ->toArray();

        foreach (['airlines', 'countries'] as $listName) {
            $result[$listName] =
                it($result[$listName])
                ->map(function (Item $item) use ($listName, $tipUser, $datesResult) {
                    $item = $item->jsonSerialize();
                    $item['value'] = (int) $item['value'];

                    if ($listName === 'airlines') {
                        $item['tip'] = $this->tipFormatter->formatAirlineTip($tipUser, $datesResult, $item['title'], $item['value']);
                    } else {
                        $item['tip'] = $this->tipFormatter->formatCountryTip($tipUser, $datesResult, $item['title'], $item['value']);
                    }

                    return $item;
                })
                ->toArray();
        }

        /** @var FlightStat $flightStats */
        $flightStats = $result['travelStatistics']->getFlightStats();
        $result['tips'] = [
            'totalFlights' => $this->tipFormatter->formatFlightsTakenTip($tipUser, $datesResult, $flightStats->getTotalFlights(), $result['prevDiff']['totalFlights'] ?? null),
            'longHaulFlights' => $this->tipFormatter->formatFlightsTakenTip($tipUser, $datesResult, $flightStats->getLongHaulFlights(), $result['prevDiff']['longHaulFlights'] ?? null),
            'shortHaulFlights' => $this->tipFormatter->formatFlightsTakenTip($tipUser, $datesResult, $flightStats->getShortHaulFlights(), $result['prevDiff']['shortHaulFlights'] ?? null),

            'hotelNights' => $this->tipFormatter->formatHotelsTip($tipUser, $datesResult, $result['travelStatistics']->getHotelNights(), $result['prevDiff']['hotelNights'] ?? null),
            'rentalCarDays' => $this->tipFormatter->formatRentalCarsTip($tipUser, $datesResult, $result['travelStatistics']->getRentalCarDays(), $result['prevDiff']['rentalCarDays'] ?? null),
            'parkingDays' => $this->tipFormatter->formatParkingDaysTip($tipUser, $datesResult, $result['travelStatistics']->getParkingDays(), $result['prevDiff']['parkingDays'] ?? null),
            'cruisesDays' => $this->tipFormatter->formatCruisesTip($tipUser, $datesResult, $result['travelStatistics']->getCruisesDays(), $result['prevDiff']['cruisesDays'] ?? null),
            'totalFerries' => $this->tipFormatter->formatFerriesTakenTip($tipUser, $datesResult, $result['travelStatistics']->getTotalFerries(), $result['prevDiff']['totalFerries'] ?? null),
            'totalBuses' => $this->tipFormatter->formatBusRidesTip($tipUser, $datesResult, $result['travelStatistics']->getTotalBuses(), $result['prevDiff']['totalBuses'] ?? null),
            'restaurantReservations' => $this->tipFormatter->formatRestaurantReservationsTip($tipUser, $datesResult, $result['travelStatistics']->getRestaurantReservations(), $result['prevDiff']['restaurantReservations'] ?? null),
            'totalTrains' => $this->tipFormatter->formatTrainRidesTip($tipUser, $datesResult, $result['travelStatistics']->getTotalTrains(), $result['prevDiff']['totalTrains'] ?? null),
            'eventsAttended' => $this->tipFormatter->formatEventsTip($tipUser, $datesResult, $result['travelStatistics']->getEventsAttended(), $result['prevDiff']['eventsAttended'] ?? null),

            'countries' => $this->tipFormatter->formatCountriesTip($tipUser, $datesResult, (int) $result['locationStat']->getCountries(), $result['prevDiff'] ? (int) $result['prevDiff']['countries'] : null),
            'cities' => $this->tipFormatter->formatCitiesTip($tipUser, $datesResult, (int) $result['locationStat']->getCities(), $result['prevDiff'] ? (int) $result['prevDiff']['cities'] : null),
            'continents' => $this->tipFormatter->formatContinentsTip($tipUser, $datesResult, (int) $result['locationStat']->getContinents(), $result['prevDiff'] ? (int) $result['prevDiff']['continents'] : null),
            'distance' => $this->tipFormatter->formatDistanceTip(
                $tipUser,
                $datesResult,
                $distanceResult->getDistance(),
                $result['prevDiff'] ? (int) $result['prevDiff']['distance'] : null,
                $distanceResult->getAroundTheWorld(),
            ),
        ];

        $result['travelStatistics'] = $result['travelStatistics']->jsonSerialize();

        return $result;
    }
}
