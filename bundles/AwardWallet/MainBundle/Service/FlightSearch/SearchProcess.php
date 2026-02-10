<?php

namespace AwardWallet\MainBundle\Service\FlightSearch;

use AwardWallet\MainBundle\Entity\Region;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\FlightSearch\Place\PlaceFactory;
use AwardWallet\MainBundle\Service\FlightSearch\Place\PlaceItem;
use AwardWallet\MainBundle\Service\LocationFormatter;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Service\MileValue\MileValueCalculator;
use AwardWallet\MainBundle\Service\MileValue\MileValueHandler;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\TripAnalyzer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class SearchProcess implements TranslationContainerInterface
{
    private const POSITION_DEP = 0;
    private const POSITION_ARR = 1;

    private const CACHE_DATA_LIFETIME = 1; // 86400 * 7;

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private CacheManager $cacheManager;
    private PlaceFactory $placeFactory;
    private LocationFormatter $locationFormatter;
    private TripAnalyzer $tripAnalyzer;
    private PlaceQuery $placeQuery;
    private RouterInterface $router;
    private MileValueHandler $mileValueHandler;
    private MileValueService $mileValueService;
    private LocalizeService $localizeService;

    private FormData $formData;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CacheManager $cacheManager,
        PlaceFactory $placeFactory,
        LocationFormatter $locationFormatter,
        TripAnalyzer $tripAnalyzer,
        PlaceQuery $placeQuery,
        RouterInterface $router,
        MileValueHandler $mileValueHandler,
        MileValueService $mileValueService,
        LocalizeService $localizeService
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->cacheManager = $cacheManager;
        $this->placeFactory = $placeFactory;
        $this->locationFormatter = $locationFormatter;
        $this->tripAnalyzer = $tripAnalyzer;
        $this->placeQuery = $placeQuery;
        $this->router = $router;
        $this->mileValueHandler = $mileValueHandler;
        $this->mileValueService = $mileValueService;
        $this->localizeService = $localizeService;
    }

    public function process(ParamsProcess $paramsProcess): array
    {
        $this->formData = new FormData(
            $this->mileValueHandler->getTypes(),
            $this->mileValueHandler->getClasses(),
            $this->mileValueHandler->getType($paramsProcess->getQueryType()),
            $this->mileValueHandler->getClass($paramsProcess->getQueryClass())
        );

        $isFinded = $this->findRoutes($paramsProcess);

        if ($isFinded) {
            $dataCacheRef = (new CacheItemReference(
                $this->getDataCacheKey(),
                [Tags::TAG_MILE_VALUE],
                function () {
                    $mileValueData = $this->fetchMileValueData(
                        $this->formData->getFrom(),
                        $this->formData->getTo(),
                        $this->formData->getTypeId(),
                        $this->formData->getClassList()
                    );

                    return $this->dataProcessing($mileValueData, $this->getAirCodesByRoutes($mileValueData));
                }
            ))->setExpiration(self::CACHE_DATA_LIFETIME);

            $groups = $this->cacheManager->load($dataCacheRef);
            $expandRoutes = $this->fetchExpandRoutes();
        }

        return [
            'form' => $this->formData,
            'primaryList' => $groups ?? [],
            'expandRoutes' => $expandRoutes ?? [],
        ];
    }

    public function fetchMileValueData(
        PlaceItem $placeFrom,
        PlaceItem $placeTo,
        string $type,
        array $classes,
        int $limit = 10000
    ): array {
        $depCondition = $this->getAirCondition('acDep', $placeFrom);
        $arrCondition = $this->getAirCondition('acArr', $placeTo);

        $query = $this->entityManager->getConnection()->executeQuery("
            SELECT
                    mv.MileValueID, mv.TripID, mv.ProviderID, mv.TotalMilesSpent, mv.TotalTaxesSpent, mv.AlternativeCost, mv.MileValue, mv.Route, mv.CashRoute, mv.MileRoute, mv.TravelersCount,
                    p.DisplayName AS ProviderName
            FROM MileValue mv
            JOIN AirCode acDep ON (" . $depCondition . " AND mv.MileRoute LIKE CONCAT(acDep.AirCode, '-%'))
            JOIN AirCode acArr ON (" . $arrCondition . " AND mv.MileRoute LIKE CONCAT('%-', acArr.AirCode))
            JOIN Provider p ON (p.ProviderID = mv.ProviderID)
            WHERE
                    mv.Status NOT IN (:excludedStatuses)
                AND mv.RouteType LIKE :routeType
                AND mv.ClassOfService IN (:class)
            LIMIT " . $limit,
            [
                'excludedStatuses' => CalcMileValueCommand::EXCLUDED_STATUSES,
                'routeType' => $type,
                'class' => $classes,
            ],
            [
                'excludedStatuses' => Connection::PARAM_STR_ARRAY,
                'routeType' => \PDO::PARAM_STR,
                'class' => Connection::PARAM_STR_ARRAY,
            ]
        );

        return $query->fetchAllAssociative();
    }

    public function createParamsProcess($from, $to, $type, $class): ParamsProcess
    {
        $replacements = [];

        foreach (['from' => $from, 'to' => $to] as $key => $query) {
            if (3 === strlen($query) && !empty($airports = $this->placeQuery->byAirport($query, 1))) {
                $replacements[$key] = PlaceQuery::TYPE_AIRPORT . '-' . $airports[0]->getId();
            } elseif (2 === strlen($query) && !empty($countrys = $this->placeQuery->byCountry($query, 1))) {
                $replacements[$key] = PlaceQuery::TYPE_COUNTRY . '-' . $countrys[0]->getId();
            }
        }

        return new ParamsProcess(
            $replacements['from'] ?? $from,
            $replacements['to'] ?? $to,
            $type,
            $class
        );
    }

    public function generateSearchLink(
        PlaceItem $placeFrom,
        PlaceItem $placeTo,
        $type,
        $class,
        array $options = []
    ): string {
        $arrType = $options['arrType'] ?? $placeTo->getType();

        return $this->router->generate('aw_flight_search', [
            'from' => $placeFrom->getType() . '-' . $placeFrom->getId(),
            'to' => $arrType . '-' . $placeTo->getId(),
            'type' => $type,
            'class' => $class,
        ]);
    }

    public function expandPlaceFrom(PlaceItem $placeFrom, PlaceItem $placeTo, $type, $class): ?PlaceItem
    {
        return $this->expandPlace(self::POSITION_DEP, $placeFrom, $placeTo, $type, $class);
    }

    public function expandPlaceTo(PlaceItem $placeFrom, PlaceItem $placeTo, $type, $class): ?PlaceItem
    {
        return $this->expandPlace(self::POSITION_ARR, $placeFrom, $placeTo, $type, $class);
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('expand-to'))->setDesc('Expand to %name%'),
            (new Message('region'))->setDesc('Region'),
            (new Message('taxes'))->setDesc('Taxes'),
            (new Message('we-not-find-any-result'))->setDesc('We did not find any results for this search. %break%Try broadening your search.'),
        ];
    }

    private function fetchExpandRoutes(): array
    {
        $expand = [
            'from' => [
                'dep' => $this->expandPlaceFrom(
                    $this->formData->getFrom(),
                    $this->formData->getTo(),
                    $this->formData->getTypeId(),
                    $this->formData->getClassList()
                ),
                'arr' => $this->formData->getTo(),
            ],
            'to' => [
                'dep' => $this->formData->getFrom(),
                'arr' => $this->expandPlaceTo(
                    $this->formData->getFrom(),
                    $this->formData->getTo(),
                    $this->formData->getTypeId(),
                    $this->formData->getClassList()
                ),
            ],
        ];

        $expand['linkFrom'] = null === $expand['from']['dep']
            ? null
            : $this->generateSearchLink(
                $expand['from']['dep'],
                $this->formData->getTo(),
                $this->formData->getTypeId(),
                $this->formData->getClassId()
            );

        $expand['linkTo'] = null === $expand['to']['arr']
            ? null
            : $this->generateSearchLink(
                $this->formData->getFrom(),
                $expand['to']['arr'],
                $this->formData->getTypeId(),
                $this->formData->getClassId()
            );

        return $expand;
    }

    private function fetchTripSegments(array $tripIds): array
    {
        $tripIds = array_unique($tripIds);

        $query = $this->entityManager->getConnection()->executeQuery('
            SELECT TripID, AirlineName, OperatingAirlineName
            FROM TripSegment
            WHERE TripID IN (?)',
            [$tripIds],
            [Connection::PARAM_INT_ARRAY]
        );

        $result = $query->fetchAllAssociative();
        $data = [];

        foreach ($result as $item) {
            $tripId = $item['TripID'];

            if (!array_key_exists($tripId, $data)) {
                $data[$tripId] = [];
            }
            $data[$tripId][] = $item;
        }

        return $data;
    }

    private function calculateAverage(array $data): array
    {
        $tripSegements = $this->fetchTripSegments(array_column($data, 'TripID'));

        $groupRoutes = [];

        foreach ($data as $item) {
            $tripId = (int) $item['TripID'];

            // Calculation by Travelers count
            $travelersCount = (int) $item['TravelersCount'];

            if ($travelersCount > 1) {
                $item['TotalMilesSpent'] /= $travelersCount;
                $item['TotalTaxesSpent'] /= $travelersCount;
                $item['AlternativeCost'] /= $travelersCount;
            }

            // Group uniq routes
            $routeCodes = explode('-', $item['MileRoute']);
            $depCode = $routeCodes[0];
            $arrCode = array_pop($routeCodes);

            if (array_key_exists($tripId, $tripSegements)) {
                $airlineName = array_filter(array_column($tripSegements[$tripId], 'AirlineName'));
                $operatingAirline = array_filter(array_column($tripSegements[$tripId], 'OperatingAirlineName'));
                $item['AirlineName'] = $airlineName[0] ?? '';
                $item['OperatingAirlineName'] = $operatingAirline[0] ?? '';
            } else {
                $item['AirlineName'] =
                $item['OperatingAirlineName'] = '';
            }

            $uniqRouteKey = $depCode . '-' . $arrCode;
            $item['_route'] = $uniqRouteKey;
            $groupRoutes[$uniqRouteKey][] = $item;
        }

        $avgAltCostRoutes = [];

        foreach ($groupRoutes as $routeKey => $routes) {
            $countValues = count($routes);
            $sumAltCost = array_sum(array_column($routes, 'AlternativeCost'));
            $avgAltCostRoutes[$routeKey] = [
                'value' => round($sumAltCost / $countValues),
                'items' => $routes,
            ];
        }

        $groupRoutesProvider = [];

        foreach ($groupRoutes as $routeKey => $routes) {
            foreach ($routes as $route) {
                $groupRoutesProvider[(int) $route['ProviderID']][] = $route;
            }
        }

        $groupProvidersAirlines = [];

        foreach ($groupRoutesProvider as $providerId => $routes) {
            foreach ($routes as $route) {
                $airline = empty($route['OperatingAirlineName']) ? $route['AirlineName'] : $route['OperatingAirlineName'];
                $uniqRouteKey = $route['_route'] . '-' . $airline;
                $groupProvidersAirlines[$providerId][$uniqRouteKey][] = $route;
            }
        }

        $avgData = [];

        foreach ($groupProvidersAirlines as $providerId => $routesAirlines) {
            foreach ($routesAirlines as $routeKey => $routes) {
                $milesSpent = 0;
                $taxesSpent = 0;

                $routeCodes = explode('-', $routeKey);
                $depCode = $routeCodes[0];
                $arrCode = $routeCodes[1];
                $altCost = $avgAltCostRoutes[$depCode . '-' . $arrCode]['value'];

                $routesCount = count($routes);
                $names = [];

                $_debug = ['MileValueID' => [], 'TripID' => []];

                foreach ($routes as $item) {
                    $_debug['MileValueID'][] = $item['MileValueID'];
                    $_debug['TripID'][] = $item['TripID'];

                    $milesSpent += $item['TotalMilesSpent'];
                    $taxesSpent += $item['TotalTaxesSpent'];

                    foreach (['DisplayName', 'AirlineName', 'OperatingAirlineName'] as $nameKey) {
                        if (empty($names[$nameKey]) && !empty($item[$nameKey])) {
                            $names[$nameKey] = $item[$nameKey];
                        }
                    }
                }

                $dataset = [
                    '_debug' => $_debug,
                    'ProviderID' => $item['ProviderID'],
                    'ProviderName' => $item['ProviderName'],
                    'MileRoute' => $depCode . '-' . $arrCode,
                    'dep' => $depCode,
                    'arr' => $arrCode,
                    'TotalMilesSpent' => round($milesSpent / $routesCount),
                    'TotalTaxesSpent' => round($taxesSpent / $routesCount, 2),
                    'AlternativeCost' => round($altCost), // round($altCost / $routesCount),
                    // 'DisplayName' => $names['DisplayName'] ?? '',
                    'AirlineName' => $names['AirlineName'] ?? '',
                    'OperatingAirlineName' => $names['OperatingAirlineName'] ?? '',
                ];
                $dataset['MileValue'] = $this->calculateMileValue(
                    $dataset['AlternativeCost'],
                    $dataset['TotalTaxesSpent'],
                    $dataset['TotalMilesSpent']
                );

                $avgData[] = $dataset;
            }
        }

        return [
            'data' => $data,
            'avgData' => $avgData,
            'avgAltCost' => $avgAltCostRoutes,
        ];
    }

    private function calculateMileValue(float $alternativeCost, float $taxes, $milesSpent): array
    {
        $value = MileValueCalculator::calc($alternativeCost, $taxes, $milesSpent);

        if ($value >= 0.0099) {
            $value = round($value, 2);
        } else {
            $value = round($value, 4);
        }

        return [
            'raw' => $value,
            'formatted' => $this->localizeService->formatCurrency($value, 'USD'),
        ];
    }

    private function dataProcessing(array $data, array $placeByCodes): array
    {
        if (empty($data)) {
            return [];
        }

        $result = $this->calculateAverage($data);
        $data = $result['avgData'];

        $groups = [];

        foreach ($data as $item) {
            $providerId = (int) $item['ProviderID'];

            if (!array_key_exists($providerId, $groups)) {
                $groups[$providerId] = [
                    'providerId' => $providerId,
                    'name' => html_entity_decode($item['ProviderName']),
                    'items' => [],
                ];
            }

            $depCode = $item['dep'];
            $arrCode = $item['arr'];
            $stops = $this->tripAnalyzer->fetchStops($item['MileRoute']);

            $item['dep'] = [
                'code' => $depCode,
                'location' => $this->locationFormatter->formatLocationName(
                    $placeByCodes[$depCode]['CityName'],
                    $placeByCodes[$depCode]['CountryCode'],
                    $placeByCodes[$depCode]['CountryName'],
                    $placeByCodes[$depCode]['StateName'],
                    $placeByCodes[$depCode]['State'],
                ),
            ];

            if (!empty($stops)) {
                foreach ($stops as &$stop) {
                    $stop['location'] = $this->locationFormatter->formatLocationName(
                        $placeByCodes[$stop['code']]['CityName'],
                        $placeByCodes[$stop['code']]['CountryCode'],
                        $placeByCodes[$stop['code']]['CountryName'],
                        $placeByCodes[$stop['code']]['StateName'],
                        $placeByCodes[$stop['code']]['State'],
                    );
                }
            }
            $item['stops'] = $stops;

            $item['arr'] = [
                'code' => $arrCode,
                'location' => $this->locationFormatter->formatLocationName(
                    $placeByCodes[$arrCode]['CityName'],
                    $placeByCodes[$arrCode]['CountryCode'],
                    $placeByCodes[$arrCode]['CountryName'],
                    $placeByCodes[$arrCode]['StateName'],
                    $placeByCodes[$arrCode]['State'],
                ),
            ];

            if (!empty($item['OperatingAirlineName'])) {
                $item['airline'] = $item['OperatingAirlineName'] . '<span class="f-airline airline-o"></span>';
            } elseif (!empty($item['AirlineName'])) {
                $item['airline'] = $item['AirlineName'] . '<span class="f-airline airline-a"></span>';
            } else {
                $item['airline'] = '<span class="f-airline airline-p"></span>';
            }

            switch ($this->formData->getTo()->getType()) {
                case PlaceQuery::TYPE_REGION:
                    $country = $this->placeQuery->byCountry($placeByCodes[$arrCode]['CountryName'], 1);

                    if (!empty($country)) {
                        $item['arr']['reduce'] = [
                            'location' => $placeByCodes[$arrCode]['CountryName'],
                            'link' => $this->generateSearchLink(
                                $this->formData->getFrom(),
                                $country[0],
                                $this->formData->getTypeId(),
                                $this->formData->getClassId()
                            ),
                        ];
                    }

                    break;

                case PlaceQuery::TYPE_COUNTRY:
                    if (!empty($placeByCodes[$arrCode]['State']) && !empty($placeByCodes[$arrCode]['StateName'])) {
                        $state = $this->placeQuery->byState($placeByCodes[$arrCode]['StateName'], 1);

                        if (!empty($state)) {
                            $item['arr']['reduce'] = [
                                'location' => $placeByCodes[$arrCode]['StateName'],
                                'link' => $this->generateSearchLink(
                                    $this->formData->getFrom(),
                                    $state[0],
                                    $this->formData->getTypeId(),
                                    $this->formData->getClassId()
                                ),
                            ];
                        }
                    } else {
                        $city = $this->placeQuery->byState($placeByCodes[$arrCode]['CityName'], 1);

                        if (!empty($city)) {
                            $item['arr']['reduce'] = [
                                'location' => $placeByCodes[$arrCode]['CityName'],
                                'link' => $this->generateSearchLink(
                                    $this->formData->getFrom(),
                                    $city[0],
                                    $this->formData->getTypeId(),
                                    $this->formData->getClassId()
                                ),
                            ];
                        }
                    }

                    break;
            }

            $item['cost'] = $this->calculateCost(
                $item['TotalMilesSpent'],
                $item['MileValue']['raw'],
                $item['TotalTaxesSpent']
            );

            $groups[$providerId]['items'][] = $item;
        }

        foreach ($groups as &$group) {
            $sumTotalMilesSpent = array_sum(array_column($group['items'], 'TotalMilesSpent'));
            $sumAlternativeCost = array_sum(array_column($group['items'], 'AlternativeCost'));
            $sumTotalTaxesSpent = array_sum(array_column($group['items'], 'TotalTaxesSpent'));
            $sumMileValue = array_sum(
                array_column(array_column($group['items'], 'MileValue'), 'raw')
            );

            $count = count($group['items']);
            $group['avg'] = [
                'TotalMilesSpent' => round($sumTotalMilesSpent / $count),
                'AlternativeCost' => round($sumAlternativeCost / $count),
                'TotalTaxesSpent' => round($sumTotalTaxesSpent / $count, 2),
                'MileValue' => round($sumMileValue / $count, 2),
            ];
            $group['cost'] = $this->calculateCost(
                $group['avg']['TotalMilesSpent'],
                $group['avg']['MileValue'],
                $group['avg']['TotalTaxesSpent']
            );
        }

        /*
        uasort($groups, fn($a, $b) => $a['cost'] <=> $b['cost']);
        foreach ($groups as &$group) {
            uasort($group['items'], fn($a, $b) => $a['cost'] <=> $b['cost']);
        }
        */

        return $groups;
    }

    private function calculateCost($miles, $mileValue, $taxes): float
    {
        return round($miles * $mileValue + $taxes);
    }

    private function getAirCondition(string $alias, PlaceItem $place): string
    {
        switch ($place->getType()) {
            case PlaceQuery::TYPE_AIRPORT:
                return sprintf("$alias.AirCode = %s",
                    $this->entityManager->getConnection()->quote($place->getAirCode())
                );

            case PlaceQuery::TYPE_CITY:
                return sprintf("$alias.CityName = %s AND $alias.CountryCode = %s",
                    $this->entityManager->getConnection()->quote($place->getCityName()),
                    $this->entityManager->getConnection()->quote($place->getCountryCode())
                );

            case PlaceQuery::TYPE_STATE:
                return sprintf("$alias.State = %s AND $alias.CountryCode = %s",
                    $this->entityManager->getConnection()->quote($place->getStateCode()),
                    $this->entityManager->getConnection()->quote($place->getCountryCode())
                );

            case PlaceQuery::TYPE_COUNTRY:
                return sprintf("$alias.CountryCode = %s",
                    $this->entityManager->getConnection()->quote($place->getCountryCode())
                );

            case PlaceQuery::TYPE_REGION:
                $countryCodes = $this->entityManager->getRepository(Region::class)->getCountryCodes($place->getId());

                return sprintf("$alias.CountryCode IN ('%s')",
                    implode("','", $countryCodes)
                );
        }

        throw new \Exception('Uknown type: ', $place->getType());
    }

    private function findRoutes(ParamsProcess $paramsProcess): bool
    {
        [$fromType, $fromId] = $paramsProcess->getQueryFromParts();
        [$toType, $toId] = $paramsProcess->getQueryToParts();

        if (empty($fromId) || empty($toId)) {
            return false;
        }

        $placeFrom = $this->placeFactory->build($fromType, $fromId);
        $placeTo = $this->placeFactory->build($toType, $toId);

        if (null === $placeFrom || null === $placeTo) {
            return false;
        }

        $this->formData
            ->setFrom($placeFrom)
            ->setTo($placeTo);

        return true;
    }

    private function getAirCodesByRoutes(array $data): array
    {
        $routes = [];

        foreach ($data as $item) {
            $routes[] = $item['Route'];
            $routes[] = $item['MileRoute'];
        }

        $routeCodes = [];

        foreach ($routes as &$route) {
            if (false !== strpos($route, ',')) {
                // extract route codes with layovers
                $route = explode(',', $route);

                foreach ($route as $routeParts) {
                    if (false === strpos($routeParts, '-')) {
                        continue;
                    }
                    $route = explode('-', $routeParts);
                    $routeCodes[] = $route[0];
                    $routeCodes[] = array_pop($route);
                }
            } else {
                $route = explode('-', $route);
                $routeCodes[] = $route[0];
                $routeCodes[] = array_pop($route);
            }
        }
        $routeCodes = array_unique($routeCodes);

        if (empty($routeCodes)) {
            return [];
        }

        $aircodes = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    AirCode, CityCode, CityName, State, StateName, CountryCode, CountryName, AirName
            FROM AirCode
            WHERE
                    AirCode IN (:codes)
                OR  (AirCode <> CityCode AND CityCode IN (:codes))',
            ['codes' => $routeCodes],
            ['codes' => Connection::PARAM_STR_ARRAY]
        );

        return array_combine(array_column($aircodes, 'AirCode'), $aircodes);
    }

    private function expandPlace(int $position, PlaceItem $placeFrom, PlaceItem $placeTo, $type, $class): ?PlaceItem
    {
        $place = self::POSITION_DEP === $position ? $placeFrom : $placeTo;

        if (PlaceQuery::TYPE_AIRPORT === $place->getType()) {
            $countCityAirports = (int) $this->entityManager->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM AirCode WHERE CountryCode LIKE :countryCode AND CityName LIKE :cityName',
                ['countryCode' => $place->getCountryCode(), 'cityName' => $place->getCityName()],
                ['countryCode' => \PDO::PARAM_STR, 'cityName' => \PDO::PARAM_STR]
            );

            $city = $this->placeQuery->byCity($place->getCityName(), $place->getCountryCode(), 1);

            if (!empty($city)) {
                if ($countCityAirports > 1) {
                    return $city[0];
                }

                $place = $city[0];
            }
        }

        if (PlaceQuery::TYPE_CITY === $place->getType()) {
            if (!empty($place->getStateName())) {
                $state = $this->placeQuery->byState($place->getStateName(), 1);

                if (!empty($state)) {
                    return $state[0];
                }
            } else {
                $country = $this->placeQuery->byCountry($place->getCountryCode(), 1);

                if (empty($country)) {
                    $country = $this->placeQuery->byCountry($place->getCountryCode(), 1);
                }

                if (!empty($country)) {
                    return $country[0];
                }
            }
        }

        if (PlaceQuery::TYPE_STATE === $place->getType()) {
            $country = $this->placeQuery->byCountry($place->getCountryName(), 1);

            if (!empty($country)) {
                return $country[0];
            }
        }

        if (PlaceQuery::TYPE_COUNTRY === $place->getType()) {
            $regions = $this->placeQuery->getRegionByCountryId($place->getId());

            if (!empty($regions)) {
                return $regions[0];
            }
        }

        return null;
    }

    private function getDataCacheKey(): string
    {
        $key = implode('-', [
            $this->formData->getFrom()->getType(),
            $this->formData->getFrom()->getId(),
            $this->formData->getTo()->getType(),
            $this->formData->getTo()->getId(),
            $this->formData->getTypeId(),
            $this->formData->getClassId(),
        ]);

        return sha1($key);
    }
}
