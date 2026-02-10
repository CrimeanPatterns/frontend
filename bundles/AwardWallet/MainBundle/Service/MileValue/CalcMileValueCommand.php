<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\MileValue\DataSource\DataSourceInterface;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\PriceSourceInterface;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\ResultRoute;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\SearchRoute;
use AwardWallet\MainBundle\Service\RA\Flight\FlightDealSubscriber;
use Doctrine\DBAL\Connection;
use JMS\Serializer\SerializerInterface;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CalcMileValueCommand extends Command
{
    public const STATUS_NEW = 'N';
    public const STATUS_REVIEW = 'R';
    public const STATUS_AUTO_REVIEW = 'A';
    public const STATUS_REPORTED = 'P';
    public const STATUS_ERROR = 'E';
    public const STATUS_GOOD = 'G';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_REVIEW => 'Review',
        self::STATUS_AUTO_REVIEW => 'Auto Review',
        self::STATUS_REPORTED => 'Reported',
        self::STATUS_ERROR => 'Error',
        self::STATUS_GOOD => 'Good',
    ];

    public const EXCLUDED_STATUSES = [self::STATUS_REVIEW, self::STATUS_AUTO_REVIEW, self::STATUS_ERROR, self::STATUS_REPORTED];
    public const EXCLUDED_TIMELINE_STATUSES = [self::STATUS_REVIEW, self::STATUS_ERROR, self::STATUS_REPORTED];

    public static $defaultName = 'aw:calc-mile-value';

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var array
     */
    private $warnings = [];
    /**
     * @var bool
     */
    private $noCache = false;
    /**
     * @var int[]
     */
    private $lowcosterIds;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var bool
     */
    private $debug = false;
    /**
     * @var bool
     */
    private $dryRun = false;
    /**
     * @var DeviationCalculator
     */
    private $deviationCalculator;
    /**
     * @var bool
     */
    private $forceCheckForReview = false;

    /**
     * @var Writer
     */
    private $writer;
    /**
     * @var PriceSourceInterface
     */
    private $priceSource;
    /**
     * @var DataSourceInterface[]
     */
    private $dataSources = [];
    /**
     * @var BestPriceSelector
     */
    private $bestPriceSelector;
    /**
     * @var RouteFormatter
     */
    private $routeFormatter;
    /**
     * @var array
     */
    private $deviationStatuses = [CalcMileValueCommand::STATUS_GOOD, CalcMileValueCommand::STATUS_NEW];
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var CacheManager
     */
    private $cacheManager;

    private FlightDealSubscriber $flightDealSubscriber;

    private TripLoaderFactory $tripLoaderFactory;

    public function __construct(
        Connection $connection,
        Logger $logger,
        DeviationCalculator $deviationCalculator,
        Writer $writer,
        PriceSourceInterface $mileValueCombinedPriceSource,
        BestPriceSelector $bestPriceSelector,
        RouteFormatter $routeFormatter,
        iterable $mileValueDataSources,
        SerializerInterface $serializer,
        CacheManager $cacheManager,
        FlightDealSubscriber $flightDealSubscriber,
        TripLoaderFactory $tripLoaderFactory
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
        $this->deviationCalculator = $deviationCalculator;
        $this->writer = $writer;
        $this->priceSource = $mileValueCombinedPriceSource;
        $this->dataSources = $mileValueDataSources;
        $this->bestPriceSelector = $bestPriceSelector;
        $this->routeFormatter = $routeFormatter;
        $this->serializer = $serializer;
        $this->cacheManager = $cacheManager;
        $this->flightDealSubscriber = $flightDealSubscriber;
        $this->tripLoaderFactory = $tripLoaderFactory;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'past days to scan', 2)
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'process only this provider code')
            ->addOption('tripId', null, InputOption::VALUE_REQUIRED, 'process only this tripId')
            ->addOption('startMileValueId', null, InputOption::VALUE_REQUIRED, 'process records with mileValueId >= this value')
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'force price refresh')
            ->addOption('errors-only', null, InputOption::VALUE_NONE, 'errors only')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'debug mode')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'dry run')
            ->addOption('force-check-for-review', null, InputOption::VALUE_NONE, 'ignore status, and force check for review')
            ->addOption('deviation-from-good-only', null, InputOption::VALUE_NONE, 'calc deviation basing only on good')
            ->addOption('update-from-db', null, InputOption::VALUE_NONE, 'keep price, update only mile-route related params')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many trips to process')
            ->addOption('only-new', null, InputOption::VALUE_NONE, 'process only new trips')
            ->addOption('only-new-status', null, InputOption::VALUE_NONE, 'process only existing mile value rows with new status')
            ->addOption('only-existing', null, InputOption::VALUE_NONE, 'process only existing mile value rows')
            ->addOption('delete-error-record', null, InputOption::VALUE_NONE, 'deleting lines that no longer match the conditions')
            ->addOption('extra-sources', null, InputOption::VALUE_NONE, 'extract data from additional data sources')
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'sql where condition')
        ;
    }

    public static function fetchFoundPrice(array $mileValue, array $priceInfos): FoundPrices
    {
        $cheapest = $priceInfos[0];
        $exactMatch = null;

        $depDate = date('Y-m-d', strtotime($mileValue['DepDate']));
        $cleanMileRoute = array_filter(
            explode(',', $mileValue['MileRoute']),
            static function ($route) {
                $depArr = explode('-', $route);

                return 2 === count($depArr) && strlen($depArr[0]) >= 3;
            }
        );
        $cleanMileRoute = array_values($cleanMileRoute);

        if (!empty($mileValue['Segments'])) {
            $flightNumbers = array_column($mileValue['Segments'], 'FlightNumber');
            $operatingFlightNumbers = array_column($mileValue['Segments'], 'OperatingAirlineFlightNumber');
        } elseif (!empty($mileValue['_flightNumbers'])) {
            $flightNumbers = explode(';', $mileValue['_flightNumbers']);
            $operatingFlightNumbers = explode(';', $mileValue['_operatingFlightNumbers']);
        }

        if (!empty($flightNumbers)) {
            $flightNumbers = array_merge($flightNumbers, $operatingFlightNumbers);
            $flightNumbers = array_unique($flightNumbers);
        }

        $strCleanMileRoute = implode(',', $cleanMileRoute);

        foreach ($priceInfos as $priceInfo) {
            $routes = [];
            $dates = [];

            $routeFlightNumbers = $routeOperatingFlightNumbers = [];

            foreach ($priceInfo->price->routes as $route) {
                $routes[] = $route->depCode . '-' . $route->arrCode;
                $dates[] = date('Y-m-d', $route->depDate);
                $routeFlightNumbers[] = $route->flightNumber;
                $routeOperatingFlightNumbers[] = $route->operatingFlightNumber;
            }
            $routeFlightNumbers = array_merge($routeFlightNumbers, $routeOperatingFlightNumbers);
            $routeFlightNumbers = array_unique($routeFlightNumbers);

            $strRoute = implode(',', $routes);

            if (
                $strRoute === $strCleanMileRoute
                && in_array($depDate, $dates)
                && (!empty($flightNumbers) && !empty(array_intersect($flightNumbers, $routeFlightNumbers)))
            ) {
                $exactMatch = $priceInfo;

                break;
            }
        }

        return new FoundPrices($cheapest, $exactMatch, array_slice($priceInfos, 0, 10));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->pushProcessor(function (array $record) {
            $record['extra']['service'] = 'milevalue';

            return $record;
        });

        try {
            $this->output = $output;
            $this->input = $input;
            $this->debug = $input->getOption('debug');
            $this->dryRun = $input->getOption('dry-run');
            $this->noCache = $input->getOption('no-cache');
            $this->forceCheckForReview = $input->getOption('force-check-for-review');
            $this->lowcosterIds = $this->connection->executeQuery(
                "select ProviderID from Provider where IATACode in (?)",
                [Constants::LOWCOSTERS],
                [Connection::PARAM_STR_ARRAY]
            )->fetchAll(\PDO::FETCH_COLUMN);

            if ($this->input->getOption('delete-error-record')) {
                $this->input->setOption('only-existing', true);
            }
            $this->searchFreshPrices(
                $input->getOption('days'),
                $input->getOption('tripId'),
                $input->getOption('errors-only'),
                $input->getOption('startMileValueId')
            );
            $this->showWarnings();
            $this->logger->info("done");

            if (!$this->dryRun) {
                $this->cacheManager->invalidateTags([Tags::TAG_MILE_VALUE]);
            }
            $output->writeln("done");
        } finally {
            $this->logger->popProcessor();
        }

        return 0;
    }

    private function searchFreshPrices(int $days, ?int $tripId, bool $errorsOnly, ?int $startMileValueId)
    {
        $tripLoader = $this->tripLoaderFactory->createTripLoader(true);

        if (!is_null($tripId)) {
            $tripLoader->filterByTripIds([$tripId]);
        }

        if (!is_null($startMileValueId)) {
            $tripLoader->filterByStartMileValueId($startMileValueId);
        } else {
            $tripLoader->filterByCreatedOrReservatedAfter(date_create("-{$days} day"));
        }

        if ($errorsOnly) {
            $tripLoader->filterWithStatusError();
        }

        if (!$this->input->getOption('update-from-db') && !$this->input->getOption('delete-error-record')) {
            $tripLoader->filterFutured();
        }

        if ($this->input->getOption('only-new')) {
            $tripLoader->filterWithoutMileValue();
        }

        if ($this->input->getOption('only-existing')) {
            $this->logger->info('updating only existing rows');
            $tripLoader->filterWithMileValue();
        }

        if ($this->input->getOption('only-new-status')) {
            $this->logger->info('updating only existing rows with new status');
            $tripLoader->filterWithStatusNew();
        }

        if ($this->input->getOption('provider')) {
            $this->logger->info('limited to provider ' . $this->input->getOption('provider'));
            $tripLoader->filterByProviderCode($this->input->getOption('provider'));
        }

        if ($this->input->getOption('update-from-db')) {
            $tripLoader->filterWithAlternativeCost();
        }

        if (!$this->input->getOption('update-from-db')) {
            $tripLoader->filterParsed();
        }

        if ($where = $this->input->getOption('where')) {
            $tripLoader->addFilter($where);
        }

        if (!empty($limit = $this->input->getOption('limit'))) {
            if (!is_numeric($limit)) {
                throw new \InvalidArgumentException('Limit must be a number');
            }

            if ($limit < 1) {
                throw new \InvalidArgumentException('Limit must be greater than 0');
            }

            $tripLoader->setLimit($limit);
        }

        $tripLoader->setProviderLowcosterList($this->lowcosterIds);
        $tripLoader->setOnHasUndefinedClassOfService(function (array $trip) {
            $this->addWarning(sprintf('TripID = %d. CabinClass not defined for at least one segment.', $trip['TripID']));

            if ($this->input->getOption('delete-error-record')) {
                $this->logger->info(sprintf('deleted trip %d', $trip['TripID']));

                if (!$this->dryRun) {
                    $this->connection->executeStatement(
                        'DELETE FROM MileValue WHERE MileValueID = :MileValueID',
                        ['MileValueID' => $trip['MileValueID']]
                    );
                }
            }
        });
        $trips = it($tripLoader->load())
            ->toArrayWithKeys();

        if (!$this->input->getOption('update-from-db')) {
            $trips = $this->onlyTripsLaterThan($trips, time() + 3600 * 2);
        }
        $trips = $this->discardDoubleTrips($trips);

        if ($this->input->getOption('update-from-db')) {
            $prices = $this->updateFromDb($trips);
        } elseif ($this->input->getOption('delete-error-record')) {
            $prices = [];
        } else {
            $prices = $this->searchTripPrices($trips);
            $this->showMatches($prices);
        }
        $this->markForReview($prices);
    }

    private function discardDoubleTrips(array $trips): array
    {
        $result = [];

        foreach ($trips as $trip) {
            $key = "{$trip['TripID']}-{$trip['Route']}-{$trip['DepDateGmt']}-{$trip['Duration']}-{$trip['SpentAwards']}-{$trip['Cost']}-{$trip['Discount']}-{$trip['Total']}";

            if (isset($result[$key])) {
                $this->logger->info("skipping double trip {$trip['TripID']}");

                continue;
            }

            $result[$key] = $trip;
        }
        $this->logger->info("trips with discarded doubles: " . count($result));

        return array_values($result);
    }

    private function showMatches(array $matches): void
    {
        if (count($matches) === 0) {
            return;
        }

        $matches = array_map(function (array $match) {
            return array_intersect_key($match, [
                "MileValueID" => false,
                "TripID" => false,
                "Route" => false,
                "RouteType" => false,
                "International" => false,
                "MilesProviderID" => false,
                "TotalMilesSpent" => false,
                "TotalTaxesSpent" => false,
                "AlternativeCost" => false,
                "MileValue" => false,
            ]);
        }, $matches);

        $table = new Table($this->output);
        $table->setRows($matches);
        $table->setHeaders(array_keys($matches[0]));

        $table->render();
    }

    private function searchTripPrices(array $trips): array
    {
        $result = [];

        foreach ($trips as $trip) {
            $this->logger->pushProcessor(function (array $record) use ($trip): array {
                $record['context']['TripID'] = $trip['TripID'];

                return $record;
            });

            try {
                if (!isset($trip['NewHash'])) {
                    continue;
                }

                if (!$this->noCache && $trip['Hash'] === $trip['NewHash']) {
                    $this->logger->info("{$trip['TripID']} {$trip['Route']} on {$trip['DepDate']}, {$trip['ClassOfService']}, {$trip['Passengers']} adults, long haul: " . json_encode($trip['International']) . ": no changes");

                    continue;
                }

                $this->logger->info("searching price for {$trip['TripID']} {$trip['Route']}, " . ($trip['Lowcoster'] ? "lowcoster, " : "") . $trip['RouteType'] . " on {$trip['DepDate']}, {$trip['ClassOfService']}, {$trip['Passengers']} adults, {$trip['BookingClasses']}, {$trip['Duration']}h");

                $searchRoutes = $this->prepareSearchRoutes($trip['Routes']);
                $priceInfos = $this->searchRoutesPrices(
                    $searchRoutes,
                    $trip['ClassOfService'],
                    $trip['Passengers'],
                    $trip['Duration']
                );

                $minKiwiPrice = $this->getMinPriceForSource("kiwi", $priceInfos);
                $minSkyScannerPrice = $this->getMinPriceForSource("skyscanner", $priceInfos);

                if (count($priceInfos) > 0) {
                    $priceInfo = reset($priceInfos);
                    $mileValue = MileValueCalculator::calc($priceInfo->price->price, $trip['Total'], $trip['SpentAwards']);
                    $this->logger->info(", mile value: " . $mileValue, ["TripID" => $trip['TripID']]);
                    $params = array_merge(
                        array_intersect_key($trip, array_flip([
                            "ProviderID",
                            "TripID",
                            "Route",
                            "RouteType",
                            "International",
                            "MileRoute",
                            "CashRoute",
                            "BookingClasses",
                            "ClassOfService",
                            "DepDate",
                            "ReturnDate",
                            "CashDuration",
                            "MileValue",
                            "MilesSource",
                        ])),
                        [
                            "MileValue" => $mileValue,
                            "Hash" => $trip['NewHash'],
                            "CashRoute" => $this->routeFormatter->format(
                                $priceInfo->price->routes,
                                $searchRoutes,
                                $trip['RouteType']
                            ),
                            "MileDuration" => $trip['Duration'],
                            "CashDuration" => round($priceInfo->duration / 3600, 1),
                            "MileAirlines" => $trip["OperatingAirlineCodes"],
                            "CashAirlines" => $priceInfo->price->source . " " . implode(",", array_map(function (ResultRoute $route): string {
                                return $route->airline . " " . $route->flightNumber;
                            }, $priceInfo->price->routes)),
                            "TravelersCount" => $trip["Passengers"],
                            "AlternativeBookingURL" => $priceInfo->price->bookingURL,
                            // "FoundPrices" => 'V1:' . $this->serializer->serialize(array_slice(array_values($priceInfos), 0, 10), 'json'),
                            "FoundPrices" => 'V2:' . $this->serializer->serialize(self::fetchFoundPrice($trip, array_values($priceInfos)), 'json'),
                            "PriceAdjustment" => $priceInfo->price->priceAdjustment,
                            "CabinClass" => implode(",", array_unique(array_map(fn (array $segment) => $segment['CabinClass'], $trip['Segments']))),
                            "KiwiMinPrice" => $minKiwiPrice,
                            "SkyscannerMinPrice" => $minSkyScannerPrice,
                        ]
                    );

                    if ($params['International']) {
                        $params['International'] = 1;
                    } else {
                        $params['International'] = 0;
                    }
                    $params['Status'] = self::STATUS_AUTO_REVIEW;
                    $params['TotalMilesSpent'] = $trip['SpentAwards'];
                    $params['TotalTaxesSpent'] = $trip['Total'];
                    $params['AlternativeCost'] = $priceInfo->price->price;
                    $params['TotalSpentInLocalCurrency'] = $trip['TripTotal'];
                    $params['LocalCurrency'] = $trip['CurrencyCode'];
                    $savedMileValueID = $this->writer->savePrice($params, $this->dryRun);
                    unset($params["Hash"]);

                    if (isset($savedMileValueID) && !$this->dryRun) {
                        $this->flightDealSubscriber->syncByMileValue($savedMileValueID);
                    }

                    if (!isset($trip['MileValueID'])) {
                        if ($this->dryRun) {
                            $params['MileValueID'] = 'new';
                        } else {
                            $params['MileValueID'] = $savedMileValueID;
                        }
                    } else {
                        $params['MileValueID'] = $trip['MileValueID'];
                    }

                    if (empty($params['MileValueID'])) {
                        throw new \Exception("Failed to detect MileValueID");
                    }
                    $params["Status"] = $trip["Status"];
                    $params["DataSourceStates"] = $trip['DataSourceStates'];
                    $this->checkDataSources($params);
                    $result[] = $params;
                }
            } finally {
                $this->logger->popProcessor();
            }
        }
        $this->logger->info("updated " . count($result) . " prices");

        return $result;
    }

    /**
     * @param SearchRoute[] $routes
     * @return PriceWithInfo[]
     */
    private function searchRoutesPrices(array $routes, string $classOfService, int $passengers, float $duration): array
    {
        $prices = $this->priceSource->search($routes, $classOfService, $passengers);

        if (count($prices) === 0) {
            $this->logger->info("no prices found");

            return [];
        }

        return $this->bestPriceSelector->getBestPriceList($prices, $routes, round($duration * 3600), $classOfService);
    }

    private function onlyTripsLaterThan(array $trips, int $depDate): array
    {
        return array_filter($trips, function (array $trip) use ($depDate) {
            return $trip['DepDateGmt'] >= $depDate;
        });
    }

    private function addWarning(string $message)
    {
        $this->logger->warning($message);

        if (!in_array($message, $this->warnings)) {
            $this->warnings[] = $message;
        }
    }

    private function showWarnings()
    {
        if (empty($this->warnings)) {
            return;
        }

        $this->logger->warning("there are " . count($this->warnings) . " warnings:");

        foreach ($this->warnings as $message) {
            $this->logger->warning($message);
        }
    }

    private function updateFromDb(array $trips)
    {
        $updated = 0;
        $result = [];

        foreach ($trips as $trip) {
            $this->checkDataSources($trip);

            if (!empty($trip['TripID'] && !empty($trip['NewHash']))) {
                $intl = $trip["International"] ? 1 : 0;
                $this->logger->info("updating trip {$trip['TripID']}, MileAirlines: {$trip['OperatingAirlineCodes']}, Route: {$trip['Route']}, Intl: {$intl}, SpentAwards: {$trip['SpentAwards']}, TripSpentAwards: {$trip['TripSpentAwards']}, HistoryMiles: {$trip['HistoryMiles']}, TotalTaxes: {$trip['Total']}");

                // logic of recalculation:
                // https://redmine.awardwallet.com/issues/19930#note-8
                $significantChanges = $this->calcSignificantChanges($trip);

                if ($significantChanges !== null && $trip['IgnoredChanges'] !== $significantChanges) {
                    $this->logger->info(
                        "significant changes to trip: $significantChanges",
                        ["TripID" => $trip['TripID']]
                    );

                    $this->connection->executeUpdate(
                        "update MileValue set IgnoredChanges = :ignoredChanges where TripID = :tripId",
                        [
                            "ignoredChanges" => $significantChanges,
                            "tripId" => $trip["TripID"],
                        ]
                    );

                    if ($this->isFarFutureTrip($trip)) {
                        $results = $this->searchTripPrices([$trip]);

                        if (count($results) === 1) {
                            $params = $results[0];
                            unset($params['Status']); // force review
                            $result[] = $params;
                        }

                        continue;
                    }

                    $trip['Status'] = self::STATUS_ERROR;
                    $trip['Note'] = 'Marked as error because of changes: ' . $significantChanges;
                    $this->logger->info($trip['Note'], ["TripID" => $trip['TripID']]);
                    $this->connection->executeUpdate("update MileValue set Status = ?, Note = ? where MileValueID = ?",
                        [$trip['Status'], $trip['Note'], $trip['MileValueID']]);
                    $result[] = $trip;

                    continue;
                }

                $extraParams = [];

                if ($trip['TravelersCount'] != $trip['Passengers']) {
                    $oldAltCost = $trip["AlternativeCost"];
                    $trip["AlternativeCost"] = ($trip["AlternativeCost"] / $trip['TravelersCount']) * $trip['Passengers'];
                    $this->logger->info("updating trip {$trip['TripID']} passenger count was changed {$trip['TravelersCount']} -> {$trip['Passengers']}, changing alt cost {$oldAltCost} -> {$trip["AlternativeCost"]}");
                    $extraParams["AlternativeCost"] = $trip["AlternativeCost"];
                }

                $params = array_merge(
                    array_intersect_key($trip, array_flip([
                        "ProviderID",
                        "TripID",
                        "Route",
                        "RouteType",
                        "International",
                        "MileRoute",
                        "CashRoute",
                        "BookingClasses",
                        "CabinClass",
                        "ClassOfService",
                        "DepDate",
                        "ReturnDate",
                        "CashDuration",
                        "MilesSource",
                    ])),
                    [
                        "MileDuration" => $trip['Duration'],
                        "MileAirlines" => $trip["OperatingAirlineCodes"],
                        "TravelersCount" => $trip["Passengers"],
                        "MileValue" => MileValueCalculator::calc($trip["AlternativeCost"], $trip["Total"], $trip["SpentAwards"]),
                        "International" => $intl,
                        'TotalMilesSpent' => $trip['SpentAwards'],
                        'TotalTaxesSpent' => $trip['Total'],
                        'TotalSpentInLocalCurrency' => $trip['TripTotal'],
                        'LocalCurrency' => $trip['CurrencyCode'],
                        'Hash' => $trip['NewHash'],
                    ],
                    $extraParams
                );

                $this->writer->savePrice($params, $this->dryRun);

                if (isset($trip["MileValueID"])) {
                    $this->flightDealSubscriber->syncByMileValue($trip["MileValueID"]);
                }

                $params["MileValueID"] = $trip["MileValueID"];
                $params["Status"] = $trip["Status"];

                if ($params['TotalTaxesSpent'] < 0.005 && !in_array($params['Status'], [self::STATUS_REVIEW, self::STATUS_AUTO_REVIEW, self::STATUS_ERROR, self::STATUS_REPORTED])) {
                    $this->logger->info("force review, zero taxes", ["TripID" => $params['TripID']]);
                    unset($params['Status']); // force review
                }

                $result[] = $params;
            }
        }
        $this->logger->info("updated $updated trips");

        return $result;
    }

    private function calcMileValue(float $alternativeCost, float $spentTaxes, int $spentAwards)
    {
        return round(($alternativeCost - $spentTaxes) / $spentAwards * 100, 2);
    }

    private function markForReview(array $trips)
    {
        $marked = 0;
        $this->logger->info("analyzing for review " . count($trips) . " trips");

        if ($this->input->getOption('deviation-from-good-only')) {
            $this->logger->info("using only good records for deviation");
            $this->deviationStatuses = [CalcMileValueCommand::STATUS_GOOD];
        }

        foreach ($trips as $trip) {
            $isNew = empty($trip['Status']);

            if ($isNew || $this->forceCheckForReview) {
                [$markForReview, $note] = $this->checkTripForReview($trip);
                $this->logger->info("{$trip["TripID"]}: providerId: {$trip["ProviderID"]}, classOfService: {$trip['ClassOfService']}, {$note}, markForReview: " . json_encode($markForReview));

                if ($markForReview) {
                    $this->connection->executeUpdate("update MileValue set Status = ?, Note = ? where MileValueID = ?", [self::STATUS_AUTO_REVIEW, $note, $trip["MileValueID"]]);
                    $marked++;
                } else {
                    if ($isNew) {
                        $this->logger->info("{$trip["TripID"]}: marking as new, $note");
                        $this->connection->executeUpdate("update MileValue set Status = ?, Note = ? where TripID = ?", [self::STATUS_NEW, $note, $trip["TripID"]]);
                    }
                }
            } else {
                $this->logger->info("{$trip["TripID"]}: skip deviation calculation,  status: {$trip["Status"]}");
            }
        }

        $this->logger->info("marked for review: $marked");
    }

    /**
     * @return SearchRoute[]
     */
    private function prepareSearchRoutes(array $tripRoutes): array
    {
        return array_map(
            function (array $route): SearchRoute {
                return new SearchRoute(
                    $route['DepCode'],
                    $route['ArrCode'],
                    strtotime($route['DepDate'])
                );
            },
            $tripRoutes
        );
    }

    private function checkTripForReview(array $trip): array
    {
        if ($trip['TotalTaxesSpent'] < 0.01 && !in_array($trip['ProviderID'], [Provider::CHASE_ID, Provider::CITI_ID, Provider::AMEX_ID])) {
            return [true, "Marked for review, because Taxes is zero"];
        }

        $params = $this->deviationCalculator->calcDeviationParams($trip["ProviderID"], $trip["ClassOfService"], $this->deviationStatuses);

        if (!empty($params["Deviation"]) && !empty($params["Average"])) {
            $delta = round(abs($params["Average"] - $trip["MileValue"]), 2);

            return [$delta > $params["Deviation"], "deviation {$params["Deviation"]},  average: {$params["Average"]}, delta: {$delta}"];
        }

        return [true, "deviation not known yet"];
    }

    private function calcSignificantChanges(array $trip): ?string
    {
        $changes = [];

        foreach (['MileRoute', 'DepDate', 'ClassOfService'] as $field) {
            if ($field === 'DepDate' && abs(strtotime($trip[$field]) - strtotime($trip["Old" . $field])) < (23 * 3600)) {
                continue;
            }

            if ($trip[$field] !== $trip['Old' . $field]) {
                $changes[] = "{$field}: {$trip["Old" . $field]} -> {$trip[$field]}";
            }
        }

        if (count($changes) === 0) {
            return null;
        }

        return implode(", ", $changes);
    }

    private function isFarFutureTrip(array $trip): bool
    {
        $futureBeginsAt = strtotime("+30 day");
        $depDate = min(strtotime($trip['OldDepDate']), strtotime($trip['DepDate']));

        if ($depDate < $futureBeginsAt) {
            $this->logger->info("less than 30 days before trip start, could not recalc", ["TripID" => $trip['TripID']]);

            return false;
        }

        $age = (time() - strtotime($trip['BookingDate'])) / DateTimeUtils::SECONDS_PER_DAY;
        $beforeDeparture = ($depDate - time()) / DateTimeUtils::SECONDS_PER_DAY;

        return ($age / $beforeDeparture) < 0.2;
    }

    private function checkDataSources($trip)
    {
        if (!$this->input->getOption('extra-sources')) {
            return;
        }

        $states = [];

        if ($trip["DataSourceStates"] !== null) {
            $states = json_decode($trip["DataSourceStates"], true);
        }

        $stateChanged = false;

        foreach ($this->dataSources as $dataSource) {
            $oldState = $states[$dataSource->getSourceId()] ?? [];
            $newState = $dataSource->check($trip, $oldState);

            if ($newState !== $oldState) {
                $this->logger->info("trip data source state changed, tripId: {$trip['TripID']}, old: " . json_encode($oldState) . ", new: " . json_encode($newState));
                $states[$dataSource->getSourceId()] = $newState;
                $stateChanged = true;
            }
        }

        if ($stateChanged) {
            $this->connection->update("MileValue", ["DataSourceStates" => json_encode($states)], ["MileValueID" => $trip["MileValueID"]]);
        }
    }

    /**
     * @param PriceWithInfo[] $priceInfos
     */
    private function getMinPriceForSource(string $source, array $priceInfos): ?float
    {
        return it($priceInfos)
            ->filter(fn (PriceWithInfo $priceWithInfo) => $priceWithInfo->price->source === $source)
            ->map(fn (PriceWithInfo $priceWithInfo) => $priceWithInfo->price->price)
            ->min()
        ;
    }
}
