<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\MileValue\Constants as MileValueConstants;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary;
use AwardWallet\MainBundle\Timeline\Item\AbstractTrip;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MileValueAlternativeFlights
{
    private EntityManagerInterface $entityManager;

    private LocalizeService $localizeService;

    private DateTimeIntervalFormatter $intervalFormatter;

    private BestPriceSelector $bestPriceSelector;

    private TripAnalyzer $tripAnalyzer;

    private SerializerInterface $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        LocalizeService $localizeService,
        DateTimeIntervalFormatter $intervalFormatter,
        BestPriceSelector $bestPriceSelector,
        TripAnalyzer $tripAnalyzer,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->localizeService = $localizeService;
        $this->intervalFormatter = $intervalFormatter;
        $this->bestPriceSelector = $bestPriceSelector;
        $this->tripAnalyzer = $tripAnalyzer;
        $this->serializer = $serializer;
    }

    /**
     * @param int[] $tripIds
     * @return MileValueAlternativeFlightsItem[] array
     */
    public function formatMileValueDataByTrips(array $tripIds): array
    {
        if (empty($tripIds)) {
            return [];
        }

        $data = $this->entityManager->getConnection()->fetchAll(
            '
            SELECT
                    TripID, AlternativeCost, MileValue, CabinClass, ClassOfService, MileRoute, CashRoute, RouteType, TravelersCount, CustomPick, CustomAlternativeCost, CustomMileValue, FoundPrices
            FROM MileValue
            WHERE
                    TripID IN (?)
                AND Status NOT IN (?)
                AND FoundPrices IS NOT NULL
            ',
            [$tripIds, CalcMileValueCommand::EXCLUDED_TIMELINE_STATUSES],
            [Connection::PARAM_INT_ARRAY, Connection::PARAM_STR_ARRAY]
        );

        if (empty($data)) {
            return [];
        }

        $result = [];

        foreach ($data as $item) {
            $result[$item['TripID']] = $this->fetchMileValue($item);
        }

        return $result;
    }

    /**
     * @param AbstractItinerary[] $segments
     * @return MileValueAlternativeFlightsItem[] array
     */
    public function fetchMileValuesData(array $segments): array
    {
        $tripIds =
            it($segments)
            ->filterIsInstance(AbstractTrip::class)
            ->map(fn (AbstractTrip $trip) => $trip->getItinerary()->getId())
            ->toArray();

        return $this->formatMileValueDataByTrips($tripIds);
    }

    public function fetchMileValue(array $mileValue): ?object
    {
        static $airlines = [];

        if (!$this->isValidChainDepArr($mileValue['MileRoute']) || !$this->isValidChainDepArr($mileValue['CashRoute'])) {
            return null;
        }

        $json = substr($mileValue['FoundPrices'], 3);
        /** @var FoundPrices $foundPrice */
        $foundPrice = $this->serializer->deserialize($json, FoundPrices::class, 'json');
        unset($mileValue['FoundPrices']);

        $fetchAirlines = [];

        if (!empty($foundPrice->cheapest->price->routes ?? null)) {
            $fetchAirlines = array_merge($fetchAirlines, array_column($foundPrice->cheapest->price->routes, 'airline'));
            $fetchAirlines = array_merge($fetchAirlines, array_column($foundPrice->cheapest->price->routes, 'operatingAirline'));
        }

        if (!empty($foundPrice->exactMatch->price->routes ?? null)) {
            $fetchAirlines = array_merge($fetchAirlines, array_column($foundPrice->exactMatch->price->routes, 'airline'));
            $fetchAirlines = array_merge($fetchAirlines, array_column($foundPrice->exactMatch->price->routes, 'operatingAirline'));
        }
        $fetchAirlines = array_filter(array_unique($fetchAirlines));
        $fetchAirlines = array_flip($fetchAirlines);
        $fetchAirCodes = array_diff_key($fetchAirlines, $airlines);

        if (!empty($fetchAirCodes)) {
            $fetchAirlines = $this->entityManager->getConnection()->fetchAll('SELECT Name, Code FROM Airline WHERE Code IN (?)', [array_keys($fetchAirCodes)], [Connection::PARAM_STR_ARRAY]);
            $airlines = array_merge(
                $airlines,
                array_combine(array_column($fetchAirlines, 'Code'), array_column($fetchAirlines, 'Name'))
            );
        }

        return (new MileValueAlternativeFlightsItem())
            ->setMileValueFields($mileValue)
            ->setFoundPrice($foundPrice)
            ->setAirlines($airlines);
    }

    public function getTimelineFields(?MileValueAlternativeFlightsItem $mileValue): ?array
    {
        if (empty($mileValue)) {
            return null;
        }

        $result['alternativeFlights'] = [
            'customPick' => $mileValue->CustomPick,
            'mileValue' => $mileValue->MileValue,
            'alternativeCost' => $mileValue->AlternativeCost,
            'customMileValue' => $mileValue->CustomMileValue,
            'customAlternativeCost' => $mileValue->CustomAlternativeCost,
            'travelersCount' => $mileValue->TravelersCount,
            'flights' => [],
        ];

        foreach (
            [
                FoundPrices::CHEAPEST_KEY,
                FoundPrices::EXACT_MATCH_KEY,
            ] as $foundPriceKey
        ) {
            if (empty($mileValue->foundPrices->{$foundPriceKey})) {
                continue;
            }

            /** @var PriceWithInfo $info */
            $info = $mileValue->foundPrices->{$foundPriceKey};

            if (FoundPrices::EXACT_MATCH_KEY === $foundPriceKey) {
                // skip if route is same
                if ($this->getRouteAsString($mileValue->foundPrices->cheapest->price->routes) === $this->getRouteAsString($mileValue->foundPrices->exactMatch->price->routes)) {
                    continue;
                }
                $stops = $this->getAnalyzedStops($info->price->routes, $mileValue->TripID);
            } else {
                $stops = $this->tripAnalyzer->fetchStops($mileValue->CashRoute);
            }

            $firstRoute = reset($info->price->routes);

            $service = it($info->price->routes)
                ->field('classOfService')
                ->filterNotEmpty()
                ->unique()
                ->joinToString(', ');

            $data = [
                'type' => $foundPriceKey,
                'airline' => $mileValue->airlines[$firstRoute->airline] ?? '',
                'price' => $this->localizeService->formatCurrency($info->price->price, 'USD'),
                'operating' => $mileValue->airlines[$firstRoute->operatingAirline] ?? '',
                'service' => empty($service) ? $mileValue->ClassOfService : $service,
                'routes' => [],
            ];

            if (MileValueConstants::ROUTE_TYPE_ONE_WAY === $mileValue->RouteType) {
                $data['routes'] = $this->fetchOneWayFlight($info->price->routes, $stops, $info->duration);
            } else {
                $data['routes'] = $this->fetchMultipleFlight($info->price->routes, $stops);
            }

            $result['alternativeFlights']['flights'][] = $data;
        }

        return $result;
    }

    private function isValidChainDepArr(string $routes): bool
    {
        $routes = explode(',', $routes);
        $last = null;

        foreach ($routes as $route) {
            if (false !== strpos($route, ':')) {
                continue;
            }
            [$dep, $arr] = explode('-', $route);

            if (!empty($last) && $last !== $dep) {
                return false;
            }

            $last = $arr;
        }

        return true;
    }

    private function getAnalyzedStops(array $priceRoutes, int $tripId): array
    {
        $segmentEmulate = [];

        foreach ($priceRoutes as $route) {
            $segmentEmulate[] = [
                'DepCode' => $route->depCode,
                'DepCityCode' => $route->depCode,
                'DepDate' => $route->depDate,
                'DepDateGmt' => $route->depDate,
                'ArrCode' => $route->arrCode,
                'ArrCityCode' => $route->arrCode,
                'ArrDate' => $route->arrDate,
                'ArrDateGmt' => $route->arrDate,
            ];
        }

        [
            $duration,
            $routeType,
            $routes,
            $mileRoute,
            $classOfService,
            $returnDate,
        ] = $this->tripAnalyzer->analyzeTripSegments($segmentEmulate, $tripId, false);

        return $this->tripAnalyzer->fetchStops($mileRoute);
    }

    private function getRouteAsString(array $routes): string
    {
        $result = [];

        foreach ($routes as $route) {
            $result[] = $route->depCode . '-' . $route->arrCode;
        }

        return implode(',', $result);
    }

    private function fetchOneWayFlight(array $priceRoutes, array $stops, $duration): array
    {
        $firstRoute = reset($priceRoutes);
        $lastRoute = end($priceRoutes);

        $depDate = new \DateTime('@' . $firstRoute->depDate);
        $arrDate = new \DateTime('@' . $lastRoute->arrDate);

        $flight = [
            'date' => $this->localizeService->formatDate($depDate, 'long'),
            'day' => $this->localizeService->getWeekday($depDate),
            'depCode' => $firstRoute->depCode,
            'depTime' => $this->localizeService->formatTime($depDate, 'short'),
            'arrCode' => $lastRoute->arrCode,
            'arrTime' => $this->localizeService->formatTime($arrDate, 'short'),
            'timing' => [
                MileValueConstants::STOP_TYPE_LAYOVER => [],
            ],
        ];

        foreach ($stops as $stop) {
            if (MileValueConstants::STOP_TYPE_LAYOVER === $stop['type']) {
                $flight['timing'][MileValueConstants::STOP_TYPE_LAYOVER][] = [
                    'code' => $stop['code'],
                    'duration' => $this->intervalFormatter->formatDurationViaInterval(\DateInterval::createFromDateString($stop['duration'])),
                ];
            }
        }
        $flight['timing']['totalDuration'] = $this->intervalFormatter->formatDurationViaInterval(\DateInterval::createFromDateString(sprintf('%s seconds', $duration)));

        return [$flight];
    }

    private function fetchMultipleFlight(array $priceRoutes, array $stops): array
    {
        $resultRoutes = [];

        for ($i = 0, $iCount = count($priceRoutes); $i < $iCount; $i++) {
            $route = $priceRoutes[$i];
            $depDate = new \DateTime('@' . $route->depDate);
            $arrDate = new \DateTime('@' . $route->arrDate);
            $duration = $this->bestPriceSelector->calcDuration([$route], []);
            $duration = empty($duration) ? ((int) ($route->arrDate - $route->depDate) / 60) : $duration / 60;

            $totalDuration = new \DateTime();
            $totalDuration->add(\DateInterval::createFromDateString($duration . ' minute'));

            $flight = [
                'date' => $this->localizeService->formatDate($depDate, 'long'),
                'day' => $this->localizeService->getWeekday($depDate),
                'depCode' => $route->depCode,
                'depTime' => $this->localizeService->formatTime($depDate, 'short'),
                'arrCode' => $route->arrCode,
                'arrTime' => $this->localizeService->formatTime($arrDate, 'short'),
                'timing' => [
                    MileValueConstants::STOP_TYPE_LAYOVER => [],
                    'totalDuration' => '',
                ],
            ];

            $routeCodes = $route->depCode . '-' . $route->arrCode;

            if (array_key_exists($routeCodes, $stops)) {
                $dateInterval = \DateInterval::createFromDateString($stops[$routeCodes]['duration']);
                $totalDuration->add($dateInterval);

                $flight['timing'][MileValueConstants::STOP_TYPE_LAYOVER][] = [
                    'code' => $stops[$routeCodes]['code'],
                    'duration' => $this->intervalFormatter->formatDurationViaInterval($dateInterval),
                ];

                for ($j = 1 + $i; $j < $iCount; $j++) {
                    $checkRoute = $priceRoutes[$j];
                    $checkRouteCodes = $checkRoute->depCode . '-' . $checkRoute->arrCode;

                    if (array_key_exists($checkRouteCodes, $stops) && MileValueConstants::STOP_TYPE_LAYOVER === $stops[$checkRouteCodes]['type']) {
                        $dateInterval = \DateInterval::createFromDateString($stops[$checkRouteCodes]['duration']);
                        $totalDuration->add($dateInterval);
                        $flight['timing'][MileValueConstants::STOP_TYPE_LAYOVER][] = [
                            'code' => $stops[$checkRouteCodes]['code'],
                            'duration' => $this->intervalFormatter->formatDurationViaInterval($dateInterval),
                        ];
                    } else {
                        $i = $j;
                        $dep = $priceRoutes[$i];
                        $depDuration = $this->bestPriceSelector->calcDuration([$dep], []);
                        $depDuration = empty($depDuration) ? ((int) ($dep->arrDate - $dep->depDate) / 60) : $depDuration / 60;
                        $totalDuration->add(\DateInterval::createFromDateString($depDuration . ' minute'));
                        $date = new \DateTime('@' . $dep->arrDate);

                        $flight['arrCode'] = $dep->arrCode;
                        $flight['arrTime'] = $this->localizeService->formatTime($date, 'short');

                        $flight['timing']['totalDuration'] = $this->intervalFormatter->formatDurationViaInterval((new \DateTime())->diff($totalDuration));

                        break;
                    }
                }
            }

            $resultRoutes[] = $flight;
        }

        return $resultRoutes;
    }
}
