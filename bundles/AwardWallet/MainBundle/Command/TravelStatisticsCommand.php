<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Service\CreditCards\ClickHouseService;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\LocationFormatter;
use AwardWallet\MainBundle\Service\MileValue\LongHaulDetector;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TravelStatisticsCommand extends Command
{
    public const BUCKET = 'aw-frontend-data';
    public const CACHE_KEY = 'aw.travel-statistics_reservations';
    public const TYPE_FLIGHTS = 'flights';
    public const TYPE_HOTELS = 'hotels';
    public const TYPE_RENTED_CARS = 'rentedCars';

    public const TYPE_LONGHAUL = 'longhaul';

    public const PERIOD_DAY = 'days';
    public const PERIOD_MONTH = 'months';

    public const PERIOD_DAY_COUNT = 31;
    public const PERIOD_MONTH_COUNT = 24;
    public const PERIOD_MONTH_FUTURE = 6;

    public const FLIGHTS_OPERATING_AIRLINE_ID = [
        // US
        39 => 'American Airlines',
        243 => 'Delta Air Lines',
        676 => 'Southwest Airlines',
        98 => 'Alaska Airlines',
        404 => 'JetBlue Airways',
        740 => 'United Airlines',
        // GB
        // 129 => 'British Airways',
        // DE
        // 254 => 'Lufthansa',
        // AE
        // 739 => 'Emirates',
    ];

    public const HOTELS_PROVIDER_ID = [
        17 => 'Marriott',
        22 => 'Hilton',
        12 => 'IHG',
        10 => 'Hyatt',
        // 88 => 'Accor',
        32 => 'Radisson',
        15 => 'Wyndham',
        // 19 => 'Best Western',
        // 36 => 'Choice Hotels',
    ];

    public const RENTED_CARS_PROVIDER_ID = [
        21 => 'Hertz',
        42 => 'Avis',
        47 => 'National',
        53 => 'Enterprise',
        14 => 'Budget',
        170 => 'Alamo',
        // 90 => 'Sixt',
        // 46 => 'Thrifty',
        // 148 => 'Dollar Rent A Car',
    ];

    public const TOTALLY_EARNING_MP_DATA_KEY = 'totalEarningsRedemptions';

    public const TYPE_TOTAL_BANKS = 'banks';
    public const TOTALLY_BANKS_PROVIDER_ID = [
        Provider::CHASE_ID => 'Chase',
        Provider::AMEX_ID => 'Amex',
        Provider::CITI_ID => 'Citibank',
        Provider::BANKOFAMERICA_ID => 'Bank Of America',
    ];

    public const TYPE_TOTAL_HOTELS = 'hotels';
    public const TOTALLY_HOTELS_PROVIDER_ID = [
        Provider::MARRIOTT_ID => 'Marriott Bonvoy',
        22 => 'Hilton Honors',
        10 => 'Hyatt',
        12 => 'IHG',
        15 => 'Wyndham Rewards',
    ];

    public const TYPE_TOTAL_AIRLINES = 'airlines';
    public const TOTALLY_AIRLINES_PROVIDER_ID = [
        31 => 'British Airways',
        18 => 'Alaska Airlines',
        13 => 'JetBlue Airways',
        39 => 'Lufthansa',
        48 => 'Emirates',
        2 => 'Air Canada Aeroplan',
    ];

    public const SUFFIX = 'countryus';

    public const TOP_YEAR_COUNT = 5;

    public const TOP_FLIGHT_ROUTES_DATA_KEY = 'topFlightRoutes2';
    public const TOP_HOTELS_DATA_KEY = 'topHotels';
    public const TOP_RENTEDCARS_DATA_KEY = 'topRentedCars';
    public const CONTINENT_COUNTRY_DATA_KEY = 'continentCountry';
    public const COUNTRY_BY_CODE_DATA_KEY = 'countryCodes';
    public const AIRCODE_DATA_KEY = 'aircodes';
    public const CANCELLED_DATA_KEY = 'cancelled';
    private const CACHE_LIFETIME = '+90 days';
    protected static $defaultName = 'aw:travel-statistics';

    /** @var LoggerInterface */
    private $logger;

    /** @var Connection */
    private $connection;

    /** @var S3Client */
    private $s3Client;

    /** @var EmailScannerApi */
    private $emailScannerApi;

    /** @var LongHaulDetector */
    private $longHaulDetector;

    /** @var array */
    private $cacheData;

    /** @var array */
    private $mailboxUsers;

    /** @var ClickHouseService */
    private $clickHouseService;

    /** @var Connection */
    private $clickHouse;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LocationFormatter */
    private $locationFormatter;

    private $countryIds = [
        'us' => 230,
        'gb' => 229,
        'ae' => 228,
        'de' => 82,
    ];

    private $options = [
        'usersCountry' => null,
        'countryProviders' => [
            'us' => [self::TYPE_FLIGHTS => [39, 243, 676, 98, 404, 740]],
            'gb' => [self::TYPE_FLIGHTS => [129]],
            'de' => [self::TYPE_FLIGHTS => [254]],
            'ae' => [self::TYPE_FLIGHTS => [739]],
        ],
        'isRecalculate' => false,
        'isMailboxEmulate' => false,
    ];

    private $countryList = [];
    private $continetList = [];

    private $sameCountry = [
        'USA' => 'US',
    ];

    public function __construct(
        LoggerInterface $logger,
        Connection $replicaConnection,
        S3Client $s3Client,
        EmailScannerApi $emailScannerApi,
        LongHaulDetector $longHaulDetector,
        ClickHouseService $clickHouseService,
        Connection $clickhouse,
        EntityManagerInterface $entityManager,
        LocationFormatter $locationFormatter
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $replicaConnection;
        $this->s3Client = $s3Client;
        $this->emailScannerApi = $emailScannerApi;
        $this->longHaulDetector = $longHaulDetector;
        $this->clickHouseService = $clickHouseService;
        $this->clickHouse = $clickhouse;
        $this->entityManager = $entityManager;
        $this->locationFormatter = $locationFormatter;
    }

    protected function configure()
    {
        $this->setDescription('Data for /travel-trends page')
            ->addOption('clear-past', null, InputOption::VALUE_NONE, 'Remove past cache')
            ->addOption('recalc-past', null, InputOption::VALUE_NONE, 'Recalculate of past data')
            ->addOption('join-accounts', null, InputOption::VALUE_NONE, 'Fetch Itinerary with join Account table')
            ->addOption('suffix', null, InputOption::VALUE_OPTIONAL, 'Suffix for cache data (url query ?suffix=)')
            ->addOption('mailbox-emulate', null, InputOption::VALUE_NONE, 'Mailbox emulation for local launch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $suffix = $input->getOption('suffix') ?? 'def';

        if ('def' !== $suffix && 0 !== strpos($suffix, 'debug')) {
            $output->writeln(['', "!! Suffix change is disabled, data is collected in '" . self::SUFFIX . "' mode !!", '']);
        }

        $partProcess = null;

        if (0 === strpos($suffix, 'debug')) {
            $parts = substr($suffix, 5);

            if (!empty($parts)) {
                $partProcess = $parts;
            }
            $suffix = 'debug';
        } else {
            $suffix = self::SUFFIX;
        }
        $isJoinAccount = $input->getOption('join-accounts');

        if (0 === strpos($partProcess, 'testdata')) {
            $this->testData(substr($partProcess, 8));

            return 0;
        }

        if (in_array($partProcess, ['earn', 'longhaul'])) {
            $suffix = self::SUFFIX;
        }

        try {
            $this->cacheData = $this->s3Client->getObject(
                [
                    'Bucket' => self::BUCKET,
                    'Key' => self::CACHE_KEY . ':' . $suffix,
                ]
            );
            $this->cacheData = unserialize($this->cacheData['Body']);
        } catch (S3Exception $exception) {
            $this->cacheClear();
        }

        if (0 === strpos($suffix, 'country') && 'all' !== substr($suffix, -3)) {
            foreach ($this->countryIds as $code => $countryId) {
                if (0 === strpos($suffix, 'country' . $code)) {
                    $this->options['usersCountry'] = $this->countryIds[$code];
                }
            }

            if (empty($this->options['usersCountry'])) {
                echo "\r\nUnknown country\r\n\r\n";

                return 0;
            }
        }

        if ('countryall' === $suffix) {
            $this->mergeCountryData();

            return 0;
        }

        if ($input->getOption('clear-past')) {
            $this->cacheClear();
        }

        if ($input->getOption('recalc-past')) {
            $this->options['isRecalculate'] = true;
        }

        if ($input->getOption('mailbox-emulate')) {
            $this->options['isMailboxEmulate'] = true;
        }

        $this->setCountrys();
        $this->fillContinentsByCounry();

        $this->logger->info('Step 1 of 10. Fetch Mailbox Users' . ($this->options['isMailboxEmulate'] ? ' [ EMULATION ]' : ''));
        $this->usersCountCalculate();

        switch ($partProcess) {
            case 'longhaul':
                $this->options['isRecalculate'] = true;
                $this->longHaulDataByPeriod(self::TYPE_FLIGHTS, self::PERIOD_MONTH);

                break;

            case 'earn':
                $this->totalAverageEarningsRedemptions(self::TYPE_TOTAL_BANKS, self::PERIOD_MONTH);

                break;

            case 'top':
                $this->options['isRecalculate'] = true;

                foreach ($this->continetList as $continent) {
                    $this->topHotels($continent);
                    $this->topRentedCars($continent);
                }

                $this->assingCountryByContinent();
                $this->assignAircodes();

                break;

            default:
                if ($this->options['isRecalculate']) {
                    $this->recalculatePastData($isJoinAccount, $suffix);
                } else {
                    $this->logger->info('Step 2 of 10. Obtaining information by time periods');
                    $this->fetchByPeriod(self::TYPE_FLIGHTS, self::PERIOD_DAY);
                    $this->fetchByPeriod(self::TYPE_HOTELS, self::PERIOD_DAY);
                    $this->fetchByPeriod(self::TYPE_RENTED_CARS, self::PERIOD_DAY);

                    $this->fetchByPeriod(self::TYPE_FLIGHTS, self::PERIOD_MONTH);
                    $this->fetchByPeriod(self::TYPE_HOTELS, self::PERIOD_MONTH);
                    $this->fetchByPeriod(self::TYPE_RENTED_CARS, self::PERIOD_MONTH);

                    $this->logger->info('Step 3 of 10. Getting information by providers');

                    if ($isJoinAccount) {
                        $this->fetchByNameWithPeriod(self::TYPE_FLIGHTS, self::PERIOD_DAY);
                        $this->fetchByNameWithPeriod(self::TYPE_HOTELS, self::PERIOD_DAY);
                        $this->fetchByNameWithPeriod(self::TYPE_RENTED_CARS, self::PERIOD_DAY);

                        $this->fetchByNameWithPeriod(self::TYPE_FLIGHTS, self::PERIOD_MONTH);
                        $this->fetchByNameWithPeriod(self::TYPE_HOTELS, self::PERIOD_MONTH);
                        $this->fetchByNameWithPeriod(self::TYPE_RENTED_CARS, self::PERIOD_MONTH);
                    } else {
                        $this->fetchByName(self::TYPE_FLIGHTS, self::PERIOD_DAY);
                        $this->fetchByName(self::TYPE_HOTELS, self::PERIOD_DAY);
                        $this->fetchByName(self::TYPE_RENTED_CARS, self::PERIOD_DAY);

                        $this->fetchByName(self::TYPE_FLIGHTS, self::PERIOD_MONTH);
                        $this->fetchByName(self::TYPE_HOTELS, self::PERIOD_MONTH);
                        $this->fetchByName(self::TYPE_RENTED_CARS, self::PERIOD_MONTH);
                    }
                }

                $this->logger->info('Step 4 of 10. LongHaul detect');
                // $this->longHaulDataByPeriod(self::TYPE_FLIGHTS, self::PERIOD_DAY);
                $this->longHaulDataByPeriod(self::TYPE_FLIGHTS, self::PERIOD_MONTH);

                $this->logger->info('Step 5 of 10. Total Average Monthly Earnings vs. Redemptions');
                $this->totalAverageEarningsRedemptions(self::TYPE_TOTAL_BANKS, self::PERIOD_MONTH);
                $this->totalAverageEarningsRedemptions(self::TYPE_TOTAL_HOTELS, self::PERIOD_MONTH);
                $this->totalAverageEarningsRedemptions(self::TYPE_TOTAL_AIRLINES, self::PERIOD_MONTH);

                $this->logger->info('Step 6 of 10. TOP');
                $this->topFlightRoutes();

                foreach ($this->continetList as $continent) {
                    $this->topHotels($continent);
                    $this->topRentedCars($continent);
                }

                $this->logger->info('Step 7 of 10. Fetch Cancelled');
                $this->fetchCancelled(self::TYPE_FLIGHTS);
                $this->fetchCancelled(self::TYPE_HOTELS);
                $this->fetchCancelled(self::TYPE_RENTED_CARS);

                $this->logger->info('Step 8 of 10. Relationship countries and continents');
                $this->assingCountryByContinent();
                $this->assignAircodes();
        }

        $this->logger->info('Step 9 of 10. Save cache data with suffix = ' . $suffix);
        $this->cacheSave($suffix);

        $this->logger->info('done');

        $this->testAvailableData();

        return 0;
    }

    private function cacheClear(): void
    {
        $this->cacheData = [
            'type' => [
                self::PERIOD_DAY => [
                    self::TYPE_FLIGHTS => [],
                    self::TYPE_HOTELS => [],
                    self::TYPE_RENTED_CARS => [],
                ],
                self::PERIOD_MONTH => [
                    self::TYPE_FLIGHTS => [],
                    self::TYPE_HOTELS => [],
                    self::TYPE_RENTED_CARS => [],
                ],
            ],
            'provider' => [
                self::PERIOD_DAY => [
                    self::TYPE_FLIGHTS => [],
                    self::TYPE_HOTELS => [],
                    self::TYPE_RENTED_CARS => [],
                ],
                self::PERIOD_MONTH => [
                    self::TYPE_FLIGHTS => [],
                    self::TYPE_HOTELS => [],
                    self::TYPE_RENTED_CARS => [],
                ],
            ],
            'usersCount' => [
                'all' => 0,
                self::PERIOD_DAY => [],
                self::PERIOD_MONTH => [],
            ],
        ];
    }

    private function cacheSave(string $suffix): void
    {
        $this->s3Client->putObject(
            [
                'Bucket' => self::BUCKET,
                'Key' => self::CACHE_KEY . ':' . $suffix,
                'Body' => serialize($this->cacheData),
                'Expires' => new \DateTime(self::CACHE_LIFETIME),
            ]
        );
    }

    private function getPeriod(string $period, bool $withFuture = false): array
    {
        if (self::PERIOD_DAY === $period) {
            $dateBegin = new \DateTime(date('Y-m-d 00:00:00', strtotime('-' . self::PERIOD_DAY_COUNT . ' days')));
            $dateEnd = new \DateTime(date('Y-m-d 23:59:00'));
            $sqlDateFormat = '%Y-%m-%d';
        } else {
            $dateBegin = new \DateTime(date('Y-m-01 00:00:00', strtotime('-' . self::PERIOD_MONTH_COUNT . ' months')));
            $dateEnd = new \DateTime(date('Y-m-d 23:59:00'));
            $sqlDateFormat = '%Y-%m';
        }

        if ($withFuture && self::PERIOD_MONTH === $period) {
            $dateEnd->add(new \DateInterval('P' . self::PERIOD_MONTH_FUTURE . 'M'));
        }

        return [
            'begin' => $dateBegin,
            'end' => $dateEnd,
            'sqlDateFormat' => $sqlDateFormat,
            'dateFormat' => str_replace('%', '', $sqlDateFormat),
        ];
    }

    /**
     * @throws \Exception
     */
    private function fetchByPeriod(string $type, string $period, ?array $recalcByDate = null)
    {
        $isDaily = self::PERIOD_DAY === $period;
        $isRecalc = null !== $recalcByDate;
        [$dateBegin, $dateEnd, $sqlDateFormat, $dateFormat] = array_values($this->getPeriod($period, true));

        if ($isRecalc) {
            $dateBegin = $recalcByDate['begin'];
            $dateEnd = $recalcByDate['end'];
        }
        $paramsValue = [
            'dateFormat' => $sqlDateFormat,
        ];
        $paramsType = [
            'dateFormat' => ParameterType::STRING,
            'dateBegin' => ParameterType::STRING,
            'dateEnd' => ParameterType::STRING,
        ];

        $this->logger->info(' - ' . $type . ' by ' . $period);

        switch ($type) {
            case self::TYPE_FLIGHTS:
                $sql = '
                    SELECT
                            DATE_FORMAT(s.ScheduledDepDate, :dateFormat) AS _date,
                            COUNT(DISTINCT t.TripID) AS _count
                    FROM
                            TripSegment s,
                            Trip t
                    JOIN Usr u ON (t.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod)) 
                    WHERE
                            s.TripID = t.TripID
                        AND t.Category = :tripCategory
                        AND (s.ScheduledDepDate >= :dateBegin AND s.ScheduledDepDate < :dateEnd)
                        AND (s.Hidden = 0 AND t.Hidden = 0)
                        AND (s.DepCode IS NOT NULL AND s.ArrCode IS NOT NULL)
                    GROUP BY _date
                ';
                $paramsValue['tripCategory'] = Trip::CATEGORY_AIR;
                $paramsType['tripCategory'] = ParameterType::INTEGER;

                break;

            case self::TYPE_HOTELS:
                $sql = '
                    SELECT
                            DATE_FORMAT(CheckInDate, :dateFormat) AS _date,
                            COUNT(*) AS _count
                    FROM
                            Reservation r
                    JOIN Usr u ON (r.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod))
                    WHERE
                            CheckInDate >= :dateBegin
                        AND CheckInDate < :dateEnd
                        AND Hidden = 0
                    GROUP BY _date
                ';

                break;

            case self::TYPE_RENTED_CARS:
                $sql = '
                    SELECT
                            DATE_FORMAT(PickupDatetime, :dateFormat) AS _date,
                            COUNT(*) AS _count
                    FROM
                            Rental r
                    JOIN Usr u ON (r.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod))
                    WHERE
                            PickupDatetime >= :dateBegin
                        AND PickupDatetime < :dateEnd
                        AND Type = :rentalType
                        AND Hidden = 0
                    GROUP BY _date
                ';
                $paramsValue['rentalType'] = Rental::TYPE_RENTAL;
                $paramsType['rentalType'] = ParameterType::STRING;

                break;

            default:
                throw new \Exception('Unsupported Type');
        }

        if ($isRecalc) {
            $count = count($recalcByDate['list']) - 1;
        } else {
            $count = $isDaily ? self::PERIOD_DAY_COUNT : (self::PERIOD_MONTH_COUNT + self::PERIOD_MONTH_FUTURE);
        }

        $rewratableDate = $isDaily ? date('Y-m-d') : date('Y-m');
        $now = new \DateTime();

        for ($i = 0; $i <= $count; $i++) {
            $step = 0 === $i ? 0 : 1;

            $isFuture = false;

            if ($isDaily) {
                $dateBegin->modify('+' . $step . ' day');
                $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next day')->format('Y-m-d 00:00:00');
            } else {
                $dateBegin->modify('+' . $step . ' month');

                // if (date('Ym') === $dateBegin->format('Ym')) {
                //    $paramsValue['dateEnd'] = (new \DateTime())->modify('+1 day')->format('Y-m-d 00:00:00');
                // } else {
                $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next month')->format('Y-m-01 00:00:00');
                // }
                $isFuture = $dateBegin->getTimestamp() > $now->getTimestamp();
            }
            $keyDate = $dateBegin->format($dateFormat);

            $paramsValue['dateBegin'] = $dateBegin->format('Y-m-d H:i');
            $paramsValue['userIdByPeriod'] = array_keys($this->getFilteredUsersByMinCreationDate($dateBegin));
            $paramsType['userIdByPeriod'] = $this->connection::PARAM_INT_ARRAY;

            $isExistsInCache = array_key_exists($keyDate, $this->cacheData['type'][$period][$type]);
            $this->logger->info('     fetch >= ' . substr($paramsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($paramsValue['dateEnd'], 0, 10));

            if ($isRecalc || !$isExistsInCache || $rewratableDate === $keyDate || $isFuture) {
                $segments = $this->connection
                    ->executeQuery($sql, $paramsValue, $paramsType)
                    ->fetchAll();
                $countByDates = array_combine(array_column($segments, '_date'), array_column($segments, '_count'));

                $this->cacheData['type'][$period][$type][$keyDate] = array_key_exists($keyDate, $countByDates) ? (int) $countByDates[$keyDate] : 0;
            }
        }
        echo PHP_EOL;
    }

    private function fetchByNameWithPeriod(string $type, string $period, ?array $recalcByDate = null)
    {
        $isDaily = self::PERIOD_DAY === $period;
        $isRecalc = null !== $recalcByDate;
        [$dateBegin, $dateEnd, $sqlDateFormat, $dateFormat] = array_values($this->getPeriod($period, true));

        if ($isRecalc) {
            $dateBegin = $recalcByDate['begin'];
            $dateEnd = $recalcByDate['end'];
        }

        $paramsValue = [
            'dateFormat' => $sqlDateFormat,
        ];
        $paramsType = [
            'dateFormat' => ParameterType::STRING,
            'dateBegin' => ParameterType::STRING,
            'dateEnd' => ParameterType::STRING,
            'userIdByPeriod' => $this->connection::PARAM_INT_ARRAY,
            'successCheckDate' => ParameterType::STRING,
        ];

        $this->logger->info(' - ' . $type . ' by ' . $period);

        switch ($type) {
            case self::TYPE_FLIGHTS:
                $sql = '
                    SELECT
                            DATE_FORMAT(s.ScheduledDepDate, :dateFormat) AS _date,
                            COUNT(DISTINCT t.TripID) AS _count,
                            s.AirlineID AS _ID
                    FROM
                            TripSegment s,
                            Trip t
                    JOIN Usr u ON (t.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod))
                    JOIN Account a ON (t.AccountID = a.AccountID AND a.ErrorCode = ' . ACCOUNT_CHECKED . ' AND a.SuccessCheckDate > :successCheckDate)
                    WHERE
                            s.TripID = t.TripID
                        AND s.AirlineID IN (:airlineIds)
                        AND t.Category = :tripCategory
                        AND (s.ScheduledDepDate >= :dateBegin AND s.ScheduledDepDate < :dateEnd)
                        AND (s.Hidden = 0 AND t.Hidden = 0)
                        AND (s.DepCode IS NOT NULL AND s.ArrCode IS NOT NULL)
                    GROUP BY
                             _date, s.AirlineID
                    ORDER BY
                            _count DESC
                ';
                $paramsValue['airlineIds'] = array_keys(self::FLIGHTS_OPERATING_AIRLINE_ID);
                $paramsValue['tripCategory'] = Trip::CATEGORY_AIR;
                $paramsType['tripCategory'] = ParameterType::INTEGER;
                $paramsType['airlineIds'] = $this->connection::PARAM_INT_ARRAY;

                break;

            case self::TYPE_HOTELS:
                $this->logger->info(' - get hotels by ' . $period);
                $sql = '
                    SELECT
                            DATE_FORMAT(CheckInDate, :dateFormat) AS _date,
                            COUNT(*) AS _count,
                            r.ProviderID AS _ID
                    FROM
                            Reservation r
                    JOIN Usr u ON (r.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod))
                    JOIN Account a ON (r.AccountID = a.AccountID AND a.ErrorCode = ' . ACCOUNT_CHECKED . ' AND a.SuccessCheckDate > :successCheckDate)
                    WHERE
                            r.ProviderID IN (:providerIds)
                        AND CheckInDate >= :dateBegin
                        AND CheckInDate < :dateEnd
                        AND Hidden = 0
                    GROUP BY
                            _date, r.ProviderID
                    ORDER BY
                            _count DESC
                ';
                $paramsValue['providerIds'] = array_keys(self::HOTELS_PROVIDER_ID);
                $paramsType['providerIds'] = $this->connection::PARAM_INT_ARRAY;

                break;

            case self::TYPE_RENTED_CARS:
                $this->logger->info(' - get rented cars by ' . $period);
                $sql = '
                    SELECT
                            DATE_FORMAT(PickupDatetime, :dateFormat) AS _date,
                            COUNT(*) AS _count,
                            r.ProviderID AS _ID
                    FROM
                            Rental r
                    JOIN Usr u ON (r.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod))
                    JOIN Account a ON (r.AccountID = a.AccountID AND a.ErrorCode = ' . ACCOUNT_CHECKED . ' AND a.SuccessCheckDate > :successCheckDate)
                    WHERE
                            r.ProviderID IN (:providerIds)
                        AND PickupDatetime >= :dateBegin
                        AND PickupDatetime < :dateEnd
                        AND Type = :rentalType
                        AND Hidden = 0
                    GROUP BY
                            _date, r.ProviderID
                    ORDER BY
                            _count DESC
                ';
                $paramsValue['providerIds'] = array_keys(self::RENTED_CARS_PROVIDER_ID);
                $paramsValue['rentalType'] = Rental::TYPE_RENTAL;
                $paramsType['rentalType'] = ParameterType::STRING;
                $paramsType['providerIds'] = $this->connection::PARAM_INT_ARRAY;

                break;

            default:
                throw new \Exception('Unsupported Type');
        }

        if ($isRecalc) {
            $count = count($recalcByDate['list']) - 1;
        } else {
            $count = $isDaily ? self::PERIOD_DAY_COUNT : self::PERIOD_MONTH_COUNT;
        }
        $now = new \DateTime();

        for ($i = 0; $i <= $count; $i++) {
            $step = 0 === $i ? 0 : 1;

            $isFuture = false;

            if ($isDaily) {
                $dateBegin->modify('+' . $step . ' day');
                $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next day')->format('Y-m-d 00:00:00');
            } else {
                $dateBegin->modify('+' . $step . ' month');

                if (date('Ym') === $dateBegin->format('Ym')) {
                    $paramsValue['dateEnd'] = (new \DateTime())->modify('+1 day')->format('Y-m-d 00:00:00');
                } else {
                    $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next month')->format('Y-m-01 00:00:00');
                }
                $isFuture = $dateBegin->getTimestamp() > $now->getTimestamp();
            }

            $accountsSuccessCheckDate = clone $dateBegin;

            if ((int) date('Ym') === (int) $accountsSuccessCheckDate->format('Ym') || (new \DateTime())->diff($dateBegin)->days < 7) {
                $accountsSuccessCheckDate = $accountsSuccessCheckDate->modify('-14 days')->format('Y-m-d 00:00:00');
            } else {
                $accountsSuccessCheckDate->modify('next month');
                $accountsSuccessCheckDate = $accountsSuccessCheckDate->format('Y-m-01 00:00:00');
            }

            $paramsValue['dateBegin'] = $dateBegin->format('Y-m-d H:i');
            $paramsValue['userIdByPeriod'] = array_keys($this->getFilteredUsersByMinCreationDate($dateBegin));
            $paramsValue['successCheckDate'] = $accountsSuccessCheckDate;
            $paramsType['userIdByPeriod'] = $this->connection::PARAM_INT_ARRAY;

            $this->logger->info('     fetch >= ' . substr($paramsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($paramsValue['dateEnd'], 0, 10));
            $segments = $this->connection
                ->executeQuery($sql, $paramsValue, $paramsType)
                ->fetchAll();

            foreach ($segments as $segment) {
                $providerId = $segment['_ID'];
                $date = $segment['_date'];
                $isExistsInCache = array_key_exists($providerId, $this->cacheData['provider'][$period][$type]) && array_key_exists($date, $this->cacheData['provider'][$period][$type][$providerId]);

                if ($isRecalc || !$isExistsInCache || $isFuture) {
                    $this->cacheData['provider'][$period][$type][$providerId][$date] = $segment['_count'];
                }
            }
        }
        echo PHP_EOL;
    }

    /**
     * @throws \Exception
     */
    private function fetchByName(string $type, string $period, ?array $recalcByDate = null)
    {
        $isDaily = self::PERIOD_DAY === $period;
        $isRecalc = null !== $recalcByDate;
        [$dateBegin, $dateEnd, $sqlDateFormat, $dateFormat] = array_values($this->getPeriod($period, true));

        if ($isRecalc) {
            $dateBegin = $recalcByDate['begin'];
            // $dateEnd = $recalcByDate['end'];
        }
        $paramsValue = [
            'dateFormat' => $sqlDateFormat,
        ];
        $paramsType = [
            'dateFormat' => ParameterType::STRING,
            'dateBegin' => ParameterType::STRING,
            'dateEnd' => ParameterType::STRING,
        ];

        $this->logger->info(' - ' . $type . ' by ' . $period);

        switch ($type) {
            case self::TYPE_FLIGHTS:
                $sql = '
                    SELECT
                            DATE_FORMAT(s.ScheduledDepDate, :dateFormat) AS _date,
                            COUNT(DISTINCT t.TripID) AS _count,
                            s.AirlineID AS _ID
                    FROM
                            TripSegment s,
                            Trip t
                    JOIN Usr u ON (t.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod))
                    WHERE
                            s.TripID = t.TripID
                        AND s.AirlineID IN (:airlineIds)
                        AND t.Category = :tripCategory
                        AND (s.ScheduledDepDate >= :dateBegin AND s.ScheduledDepDate < :dateEnd)
                        AND (s.Hidden = 0 AND t.Hidden = 0)
                        AND (s.DepCode IS NOT NULL AND s.ArrCode IS NOT NULL)
                    GROUP BY
                             _date, s.AirlineID
                    ORDER BY
                            _count DESC
                ';
                $paramsValue['airlineIds'] = array_keys(self::FLIGHTS_OPERATING_AIRLINE_ID);
                $paramsValue['tripCategory'] = Trip::CATEGORY_AIR;
                $paramsType['tripCategory'] = ParameterType::INTEGER;
                $paramsType['airlineIds'] = $this->connection::PARAM_INT_ARRAY;

                break;

            case self::TYPE_HOTELS:
                $sql = '
                    SELECT
                            DATE_FORMAT(CheckInDate, :dateFormat) AS _date,
                            COUNT(*) AS _count,
                            ProviderID AS _ID
                    FROM
                            Reservation r
                    JOIN Usr u ON (r.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod))
                    WHERE
                            ProviderID IN (:providerIds)
                        AND CheckInDate >= :dateBegin
                        AND CheckInDate < :dateEnd
                        AND Hidden = 0
                    GROUP BY
                            _date, ProviderID
                    ORDER BY
                            _count DESC
                ';
                $paramsValue['providerIds'] = array_keys(self::HOTELS_PROVIDER_ID);
                $paramsType['providerIds'] = $this->connection::PARAM_INT_ARRAY;

                break;

            case self::TYPE_RENTED_CARS:
                $sql = '
                    SELECT
                            DATE_FORMAT(PickupDatetime, :dateFormat) AS _date,
                            COUNT(*) AS _count,
                            ProviderID AS _ID
                    FROM
                            Rental r
                    JOIN Usr u ON (r.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod))
                    WHERE
                            ProviderID IN (:providerIds)
                        AND PickupDatetime >= :dateBegin
                        AND PickupDatetime < :dateEnd
                        AND Type = :rentalType
                        AND Hidden = 0
                    GROUP BY
                            _date, ProviderID
                    ORDER BY
                            _count DESC
                ';
                $paramsValue['providerIds'] = array_keys(self::RENTED_CARS_PROVIDER_ID);
                $paramsValue['rentalType'] = Rental::TYPE_RENTAL;
                $paramsType['rentalType'] = ParameterType::STRING;
                $paramsType['providerIds'] = $this->connection::PARAM_INT_ARRAY;

                break;

            default:
                throw new \Exception('Unsupported Type');
        }

        if ($isRecalc) {
            $count = count($recalcByDate['list']) - 1;
        } else {
            $count = $isDaily ? self::PERIOD_DAY_COUNT : (self::PERIOD_MONTH_COUNT + self::PERIOD_MONTH_FUTURE);
        }

        $rewratableDate = $isDaily ? date('Y-m-d') : date('Y-m');
        $now = new \DateTime();

        for ($i = 0; $i <= $count; $i++) {
            $step = 0 === $i ? 0 : 1;

            $isFuture = false;

            if ($isDaily) {
                $dateBegin->modify('+' . $step . ' day');
                $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next day')->format('Y-m-d 00:00:00');
            } else {
                $dateBegin->modify('+' . $step . ' month');

                // if (date('Ym') === $dateBegin->format('Ym')) {
                //    $paramsValue['dateEnd'] = (new \DateTime())->modify('+1 day')->format('Y-m-d 00:00:00');
                // } else {
                $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next month')->format('Y-m-01 00:00:00');
                // }
                $isFuture = $dateBegin->getTimestamp() > $now->getTimestamp();
            }

            $paramsValue['dateBegin'] = $dateBegin->format('Y-m-d H:i');
            $paramsValue['userIdByPeriod'] = array_keys($this->getFilteredUsersByMinCreationDate($dateBegin));
            $paramsType['userIdByPeriod'] = $this->connection::PARAM_INT_ARRAY;

            $this->logger->info('     fetch >= ' . substr($paramsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($paramsValue['dateEnd'], 0, 10));
            $segments = $this->connection
                ->executeQuery($sql, $paramsValue, $paramsType)
                ->fetchAll();

            foreach ($segments as $segment) {
                $providerId = $segment['_ID'];
                $date = $segment['_date'];
                $isExistsInCache = array_key_exists($providerId, $this->cacheData['provider'][$period][$type]) && array_key_exists($date, $this->cacheData['provider'][$period][$type][$providerId]);

                if ($isRecalc || !$isExistsInCache || $rewratableDate === $date || $isFuture) {
                    $this->cacheData['provider'][$period][$type][$providerId][$date] = $segment['_count'];
                }
            }
        }

        echo PHP_EOL;
    }

    private function fillMailboxUsers()
    {
        if (!$this->options['isMailboxEmulate']) {
            $this->mailboxUsers = $this->fetchMailboxUsers();
        } else {
            $users = $this->connection->fetchAll('SELECT UserID, Email, CreationDateTime FROM Usr');
            $data = [];

            foreach ($users as $user) {
                $data[$user['UserID']] = [
                    'count' => 1,
                    'creationDate' => new \DateTime($user['CreationDateTime']),
                ];
            }
            $this->mailboxUsers = $data;
        }

        if (null !== $this->options['usersCountry']) {
            $filteredByCountry = [];
            $mailboxUsers = $this->connection->executeQuery('SELECT UserID FROM Usr WHERE UserID IN (' . implode(',', array_keys($this->mailboxUsers)) . ') AND CountryID = ' . $this->options['usersCountry'])->fetchFirstColumn();

            foreach ($mailboxUsers as $userId) {
                $filteredByCountry[$userId] = $this->mailboxUsers[$userId];
            }

            $this->mailboxUsers = $filteredByCountry;
        }
    }

    /**
     * @return array[]
     * @throws \Doctrine\DBAL\DBALException
     */
    private function usersCountCalculate(): void
    {
        $dayPeriod = $this->getPeriod(self::PERIOD_DAY);
        $monthPeriod = $this->getPeriod(self::PERIOD_MONTH);

        $this->fillMailboxUsers();
        $this->cacheData['usersCount']['all'] = count($this->mailboxUsers);

        if (!array_key_exists(self::PERIOD_DAY, $this->cacheData['usersCount'])) {
            $this->cacheData['usersCount'][self::PERIOD_DAY] = [];
        }

        if (!array_key_exists(self::PERIOD_MONTH, $this->cacheData['usersCount'])) {
            $this->cacheData['usersCount'][self::PERIOD_MONTH] = [];
        }

        $date = $dayPeriod['begin'];

        for ($i = 0; $i <= self::PERIOD_DAY_COUNT; $i++) {
            $step = 0 === $i ? 0 : 1;
            $date->modify('+' . $step . ' day');
            $keyDate = $date->format($dayPeriod['dateFormat']);

            if (!array_key_exists($keyDate, $this->cacheData['usersCount'][self::PERIOD_DAY])) {
                $this->cacheData['usersCount'][self::PERIOD_DAY][$keyDate] = count($this->getFilteredUsersByMinCreationDate($date));
            }
        }

        $date = $monthPeriod['begin'];

        for ($i = 0; $i <= self::PERIOD_MONTH_COUNT; $i++) {
            $step = 0 === $i ? 0 : 1;
            $date->modify('+' . $step . ' month');
            $keyDate = $date->format($monthPeriod['dateFormat']);

            if (!array_key_exists($keyDate, $this->cacheData['usersCount'][self::PERIOD_MONTH])) {
                $this->cacheData['usersCount'][self::PERIOD_MONTH][$keyDate] = count($this->getFilteredUsersByMinCreationDate($date));
            }
        }
    }

    private function fetchMailboxUsers(?int $limit = null): array
    {
        $pageToken = null;
        $usersWithValidMailboxes = [];

        do {
            $this->logger->info("loading mailbox list from scanner, pageToken: {$pageToken}, loaded: " . count($usersWithValidMailboxes));
            $response = $this->emailScannerApi->scrollMailboxes(null, ['listening'], null, null, $pageToken);

            foreach ($response->getItems() as $mailbox) {
                $data = json_decode($mailbox->getUserData(), true);

                if (!isset($data['user'])) {
                    // we now have non-user mailboxes
                    continue;
                    // throw new \Exception("failed to detect user id, mailbox: {$mailbox->getId()}, tags: " . implode(', ', $mailbox->getTags()));
                }

                $userId = $data['user'];
                $creationDate = new \DateTime($mailbox->getCreationDate());
                $existsCreationDate = array_key_exists($userId, $usersWithValidMailboxes) ? $usersWithValidMailboxes[$userId]['creationDate'] : null;

                $usersWithValidMailboxes[$userId] = [
                    'count' => ($usersWithValidMailboxes[$userId]['count'] ?? 0) + 1,
                    'creationDate' => null === $existsCreationDate || $existsCreationDate->getTimestamp() > $creationDate->getTimestamp() ? $creationDate : $existsCreationDate,
                ];
            }
            $pageToken = $response->getNextPageToken();
        } while ($response->getNextPageToken() !== null && ($limit === null || count($usersWithValidMailboxes) < $limit));

        if ($limit !== null) {
            $usersWithValidMailboxes = array_slice($usersWithValidMailboxes, 0, $limit);
        }

        return $usersWithValidMailboxes;
    }

    private function getFilteredUsersByMinCreationDate(\DateTime $minDateCreation): array
    {
        $filtered = [];

        foreach ((array) $this->mailboxUsers as $userId => $user) {
            if ($minDateCreation->getTimestamp() >= $user['creationDate']->getTimestamp()) {
                $filtered[$userId] = $user['count'];
            }
        }

        return $filtered;
    }

    private function recalculatePastData(bool $isJoinAccount, string $suffix)
    {
        $this->logger->info('Recalc 1 of 4 - fillMailboxUsers()');
        $this->fillMailboxUsers();

        $this->logger->info('Recalc 2 of 4 - fetchByPeriod()');

        foreach ($this->cacheData['type'][self::PERIOD_DAY] as $type => $listDateCount) {
            $keysDates = array_keys($listDateCount);
            $this->fetchByPeriod(
                $type,
                self::PERIOD_DAY,
                [
                    'begin' => new \DateTime(reset($keysDates)),
                    'end' => new \DateTime(end($keysDates)),
                    'list' => $listDateCount,
                ]);
        }

        foreach ($this->cacheData['type'][self::PERIOD_MONTH] as $type => $listDateCount) {
            $keysDates = array_keys($listDateCount);
            $this->fetchByPeriod(
                $type,
                self::PERIOD_MONTH,
                [
                    'begin' => new \DateTime(reset($keysDates)),
                    'end' => new \DateTime(end($keysDates)),
                    'list' => $listDateCount,
                ]);
        }

        $this->logger->info('Recalc 3 of 4 - fetchByName()');

        foreach ($this->cacheData['provider'][self::PERIOD_DAY] as $type => $listByProviders) {
            $listDateCount = reset($listByProviders);
            $keysDates = array_keys($listDateCount);

            if ($isJoinAccount) {
                $this->fetchByNameWithPeriod(
                    $type,
                    self::PERIOD_DAY,
                    [
                        'begin' => new \DateTime(reset($keysDates)),
                        'end' => new \DateTime(end($keysDates)),
                        'list' => $listDateCount,
                    ]);
            } else {
                $this->fetchByName(
                    $type,
                    self::PERIOD_DAY,
                    [
                        'begin' => new \DateTime(reset($keysDates)),
                        'end' => new \DateTime(end($keysDates)),
                        'list' => $listDateCount,
                    ]);
            }
        }

        foreach ($this->cacheData['provider'][self::PERIOD_MONTH] as $type => $listByProviders) {
            $listDateCount = reset($listByProviders);
            // $keysDates = array_keys(reset($listDateCount));
            $keysDates = array_keys($listDateCount);

            if ($isJoinAccount) {
                $this->fetchByNameWithPeriod(
                    $type,
                    self::PERIOD_MONTH,
                    [
                        'begin' => new \DateTime(reset($keysDates)),
                        'end' => new \DateTime(end($keysDates)),
                        'list' => $listDateCount,
                    ]);
            } else {
                $this->fetchByName(
                    $type,
                    self::PERIOD_MONTH,
                    [
                        'begin' => new \DateTime(reset($keysDates)),
                        'end' => new \DateTime(end($keysDates)),
                        'list' => $listDateCount,
                    ]);
            }
        }

        $this->logger->info('Recalc 4 of 4 - cacheSave() with suffix = ' . $suffix);
        $this->cacheSave($suffix);
    }

    private function mergeCountryData()
    {
        $countryCacheData = [];
        $checkCountryData = [];

        foreach (array_merge(['def' => 0], $this->countryIds) as $code => $countryId) {
            $suffix = 'def' === $code ? 'def' : 'country' . $code;

            try {
                $cache = $this->s3Client->getObject(
                    [
                        'Bucket' => self::BUCKET,
                        'Key' => self::CACHE_KEY . ':' . $suffix,
                    ]);
                $countryCacheData[$code] = unserialize($cache['Body']);
            } catch (S3Exception $exception) {
                $checkCountryData[] = $suffix;
            }
        }

        if (!empty($checkCountryData)) {
            foreach ($checkCountryData as $code) {
                echo '! You must execute the command with the argument "--suffix=' . $code . '" to continue executing the current command', "\r\n";
            }

            return;
        }

        $cacheData = $countryCacheData['def'];

        foreach ($this->countryIds as $code => $countryId) {
            foreach ($this->options['countryProviders'][$code] as $type => $mergeProviderIds) {
                foreach ($mergeProviderIds as $id) {
                    $from = array_key_exists($id, $countryCacheData[$code]['provider'][self::PERIOD_DAY][$type]) ? $countryCacheData[$code]['provider'][self::PERIOD_DAY][$type][$id] : [];
                    $to = array_key_exists($id, $cacheData['provider'][self::PERIOD_DAY][$type]) ? $cacheData['provider'][self::PERIOD_DAY][$type][$id] : [];
                    $cacheData['provider'][self::PERIOD_DAY][$type][$id] = array_merge($to, $from);

                    $from = array_key_exists($id, $countryCacheData[$code]['provider'][self::PERIOD_MONTH][$type]) ? $countryCacheData[$code]['provider'][self::PERIOD_MONTH][$type][$id] : [];
                    $to = array_key_exists($id, $cacheData['provider'][self::PERIOD_MONTH][$type]) ? $cacheData['provider'][self::PERIOD_MONTH][$type][$id] : [];
                    $cacheData['provider'][self::PERIOD_MONTH][$type][$id] = array_merge($to, $from);

                    $cacheData['usersCount']['country'][$code] = $countryCacheData[$code]['usersCount'];
                }
            }
        }

        $this->cacheData = $cacheData;
        $this->cacheSave('countryall');
    }

    private function longHaulDataByPeriod($type, $period)
    {
        $isDaily = self::PERIOD_DAY === $period;
        $isRecalc = $this->options['isRecalculate'];
        [$dateBegin, $dateEnd, $sqlDateFormat, $dateFormat] = array_values($this->getPeriod($period));

        $sql = '
            SELECT
                    DATE_FORMAT(s.ScheduledDepDate, :dateFormat) AS _date,
                    t.TripID,
                    s.DepCode, s.ArrCode
            FROM
                    TripSegment s,
                    Trip t
            JOIN Usr u ON (t.UserID = u.UserID AND u.ValidMailboxesCount > 0 AND u.UserID IN (:userIdByPeriod)) 
            WHERE
                    s.TripID = t.TripID
				AND t.Category = :tripCategory
                AND (s.ScheduledDepDate >= :dateBegin AND s.ScheduledDepDate < :dateEnd)
                AND (s.Hidden = 0 AND t.Hidden = 0)
                AND (s.DepCode IS NOT NULL AND s.ArrCode IS NOT NULL)
            GROUP BY _date, t.TripID, s.DepCode, s.ArrCode 
        ';

        $paramsValue = [
            'dateFormat' => $sqlDateFormat,
        ];
        $paramsType = [
            'dateFormat' => ParameterType::STRING,
            'dateBegin' => ParameterType::STRING,
            'dateEnd' => ParameterType::STRING,
        ];

        $paramsValue['airlineIds'] = array_keys(self::FLIGHTS_OPERATING_AIRLINE_ID);
        $paramsValue['tripCategory'] = Trip::CATEGORY_AIR;
        $paramsType['tripCategory'] = ParameterType::INTEGER;
        $paramsType['airlineIds'] = $this->connection::PARAM_INT_ARRAY;

        $trips = [];
        $count = $isDaily ? self::PERIOD_DAY_COUNT : self::PERIOD_MONTH_COUNT;
        $rewratableDate = $isDaily ? date('Y-m-d') : date('Y-m');
        $this->logger->info(' - ' . $type . ' by ' . $period);

        for ($i = 0; $i <= $count; $i++) {
            $step = 0 === $i ? 0 : 1;

            if ($isDaily) {
                $dateBegin->modify('+' . $step . ' day');
                $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next day')->format('Y-m-d 00:00:00');
            } else {
                $dateBegin->modify('+' . $step . ' month');

                if (date('Ym') === $dateBegin->format('Ym')) {
                    $paramsValue['dateEnd'] = (new \DateTime())->modify('+1 day')->format('Y-m-d 00:00:00');
                } else {
                    $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next month')->format('Y-m-01 00:00:00');
                }
            }
            $keyDate = $dateBegin->format($dateFormat);

            $paramsValue['dateBegin'] = $dateBegin->format('Y-m-d H:i');
            $paramsValue['userIdByPeriod'] = array_keys($this->getFilteredUsersByMinCreationDate($dateBegin));
            $paramsType['userIdByPeriod'] = $this->connection::PARAM_INT_ARRAY;

            $this->logger->info('     longHaul fetch >= ' . substr($paramsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($paramsValue['dateEnd'], 0, 10));
            $segments = $this->connection
                ->executeQuery($sql, $paramsValue, $paramsType)
                ->fetchAll();

            if (empty($segments)) {
                continue;
            }

            if (!array_key_exists($keyDate, $trips)) {
                $trips[$keyDate] = [];
            }

            foreach ($segments as $segment) {
                $tripId = $segment['TripID'];

                if (!array_key_exists($tripId, $trips)) {
                    $trips[$keyDate][$tripId] = [
                        '_date' => $segment['_date'],
                        'deparr' => [],
                    ];
                }
                $trips[$keyDate][$tripId]['deparr'][] = [
                    'DepCode' => $segment['DepCode'],
                    'ArrCode' => $segment['ArrCode'],
                ];
            }

            $isExistsInCache = isset($this->cacheData[self::TYPE_LONGHAUL][$period][$type]) && array_key_exists($keyDate, $this->cacheData[self::TYPE_LONGHAUL][$period][$type]);

            if ($isExistsInCache && !$isRecalc && $rewratableDate !== $keyDate) {
                unset($trips[$keyDate]);
            }
        }

        foreach ($trips as $keyDate => $trip) {
            $shortCount = 0;
            $longCount = 0;

            foreach ($trip as $tripId => $items) {
                if ($this->longHaulDetector->isLongHaulRoutes($items['deparr'])) {
                    ++$longCount;
                } else {
                    ++$shortCount;
                }
            }

            $diff = isset($this->cacheData['type'][$period][self::TYPE_FLIGHTS][$keyDate])
                ? $this->cacheData['type'][$period][self::TYPE_FLIGHTS][$keyDate] - $longCount - $shortCount
                : 0;

            $this->cacheData[self::TYPE_LONGHAUL][$period][$type][$keyDate] = [
                'short' => $shortCount,
                'long' => $longCount,
                'diff' => $diff > 0 ? $diff : 0,
            ];
        }

        echo PHP_EOL;
    }

    private function totalAverageEarningsRedemptions(string $type, string $period)
    {
        // $isRecalc = $this->options['isRecalculate'];
        $isRecalc = true;

        if (!array_key_exists(self::TOTALLY_EARNING_MP_DATA_KEY, $this->cacheData)) {
            $this->cacheData[self::TOTALLY_EARNING_MP_DATA_KEY] = [];
        }

        if (!array_key_exists($period, $this->cacheData[self::TOTALLY_EARNING_MP_DATA_KEY])) {
            $this->cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][$period] = [];
        }

        if (!array_key_exists($type, $this->cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][$period])) {
            $this->cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][$period][$type] = [];
        }

        switch ($type) {
            case self::TYPE_TOTAL_BANKS:
                $providers = self::TOTALLY_BANKS_PROVIDER_ID;

                break;

            case self::TYPE_TOTAL_HOTELS:
                $providers = self::TOTALLY_HOTELS_PROVIDER_ID;

                break;

            case self::TYPE_TOTAL_AIRLINES:
                $providers = self::TOTALLY_AIRLINES_PROVIDER_ID;

                break;

            default:
                throw new \Exception('Unsupported Type');
        }

        $sql = "
            SELECT
                    formatDateTime(toDate(ah.PostingDate), :dateFormat) AS _date,
                    SUM(CASE WHEN toDecimal64(Miles, 4) > 0 THEN toDecimal64(Miles, 4) ELSE 0 END) AS _earnings,
                    SUM(CASE WHEN toDecimal64(Miles, 4) < 0 THEN toDecimal64(Miles, 4) ELSE 0 END) AS _redemptions
            FROM {$this->clickHouseService->getActiveDbName()}.AccountHistory ah
            JOIN {$this->clickHouseService->getActiveDbName()}.Account a ON (a.AccountID = ah.AccountID)
            WHERE
                    (toDate(ah.PostingDate) >= :dateBegin AND toDate(ah.PostingDate) < :dateEnd)
                AND a.ProviderID IN (" . implode(',', array_keys($providers)) . ")
                AND (toInt64(ah.Miles) <> 10000000 AND toInt64(ah.Miles) -10000000)
            GROUP BY _date
        ";

        [$dateBegin, $dateEnd, $sqlDateFormat, $dateFormat] = array_values($this->getPeriod($period));
        $paramsValue = ['dateFormat' => $sqlDateFormat];

        $isDaily = self::PERIOD_DAY === $period;
        $count = $isDaily ? self::PERIOD_DAY_COUNT : self::PERIOD_MONTH_COUNT;
        $rewratableDate = $isDaily ? date('Y-m-d') : date('Y-m');

        $this->logger->info(' - ' . $type . ' by ' . $period);

        for ($i = 0; $i <= $count; $i++) {
            $step = 0 === $i ? 0 : 1;

            if ($isDaily) {
                $dateBegin->modify('+' . $step . ' day');
                $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next day')->format('Y-m-d');
            } else {
                $dateBegin->modify('+' . $step . ' month');

                if (date('Ym') === $dateBegin->format('Ym')) {
                    $paramsValue['dateEnd'] = (new \DateTime())->modify('+1 day')->format('Y-m-d');
                } else {
                    $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next month')->format('Y-m-01');
                }
            }
            $keyDate = $dateBegin->format($dateFormat);
            $paramsValue['dateBegin'] = $dateBegin->format('Y-m-d');
            $this->logger->info('     totals earnings/redemptions fetch >= ' . substr($paramsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($paramsValue['dateEnd'], 0, 10));

            $sqlExecute = $sql;

            foreach ($paramsValue as $keyVar => $value) {
                $sqlExecute = str_replace(':' . $keyVar, $this->connection->quote($value), $sqlExecute);
            }
            $sums = $this->clickHouse->fetchAll($sqlExecute);

            if (empty($sums)) {
                $this->cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][$period][$type][$keyDate] = [
                    'earnings' => 0,
                    'redemptions' => 0,
                ];

                continue;
            }

            foreach ($sums as $sum) {
                $date = $sum['_date'];
                $isExistsInCache = array_key_exists($date, $this->cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][$period][$type]);

                if ($isRecalc || !$isExistsInCache || $rewratableDate === $date) {
                    $this->cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][$period][$type][$date] = [
                        'earnings' => round($sum['_earnings'], 2),
                        'redemptions' => round($sum['_redemptions'], 2),
                    ];
                }
            }
        }

        echo PHP_EOL;
    }

    private function topHotels(array $continent): void
    {
        if (!array_key_exists(self::TOP_HOTELS_DATA_KEY, $this->cacheData)) {
            $this->cacheData[self::TOP_HOTELS_DATA_KEY] = [];
        }

        $continentKey = $continent['id'];

        if (!array_key_exists($continentKey, $this->cacheData[self::TOP_HOTELS_DATA_KEY])) {
            $this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey] = [];
        }

        [$startYear, $endYear, $rewritableYear] = $this->getYearParams();
        [$queryParamsValue, $queryParamsType] = $this->getQueryParams();

        $sql = "
            SELECT
                    DATE_FORMAT(r.CheckInDate, '%Y') AS _date,
                    gt.CountryCode, gt.Country, gt.City, gt.State, gt.StateCode,
                    COUNT(*) AS _count
            FROM Reservation r
            JOIN GeoTag gt ON (gt.GeoTagID = r.GeoTagID)
            WHERE
                    (r.CheckInDate >= :dateBegin AND r.CheckInDate < :dateEnd)
                AND (
                        (gt.CountryCode IN (:countryCodes) OR gt.Country IN (:countryNames)) 
                    AND (
                            r.GeoTagID IS NOT NULL
                        OR (
                                gt.Country IS NOT NULL
                            AND gt.Country <> ''
                        )
                    )
                )
                AND gt.City IS NOT NULL
                AND r.Hidden = 0
            GROUP BY _date, gt.GeoTagID
            ORDER BY _count DESC
        ";

        $queryParamsValue['countryCodes'] = array_keys($continent['countryCodes']);
        $queryParamsType['countryCodes'] = $this->connection::PARAM_STR_ARRAY;
        $queryParamsValue['countryNames'] = $continent['countryCodes'];
        $queryParamsType['countryNames'] = $this->connection::PARAM_STR_ARRAY;

        $list = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            if (array_key_exists($year, $this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey])
                && false === $this->options['isRecalculate']
                && $rewritableYear !== $year) {
                continue;
            }

            $queryParamsValue['dateBegin'] = $year . '-01-01 00:00:00';
            $queryParamsValue['dateEnd'] = (1 + $year) . '-01-01 00:00:00';

            $this->logger->info('     top hotels fetch >= ' . substr($queryParamsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($queryParamsValue['dateEnd'], 0, 10));
            $reservations = $this->connection
                ->executeQuery($sql, $queryParamsValue, $queryParamsType)
                ->fetchAll();

            if (empty($reservations)) {
                $this->logger->warning('NO HOTEL RESERVATIONS found for year ' . $year);

                continue;
            }

            if (!array_key_exists($year, $list)) {
                $list[$year] = [];
            }

            foreach ($reservations as $reservation) {
                $countryCode = null;
                $cityName = stripslashes($reservation['City']);
                $stateName = $reservation['State'];
                $stateCode = $reservation['StateCode'];

                if (!empty($reservation['CountryCode'])) {
                    $countryCode = $reservation['CountryCode'];
                } elseif (false !== ($country = $this->findCountryByName($reservation['Country']))) {
                    $countryCode = $country['Code'];
                }

                if (empty($countryCode) || empty($cityName)) {
                    continue;
                }

                if (array_key_exists($countryCode, $this->sameCountry)) {
                    $countryCode = $this->sameCountry[$countryCode];
                }

                if (!array_key_exists($countryCode, $list[$year])) {
                    $list[$year][$countryCode] = [];
                }

                if (!array_key_exists($cityName, $list[$year][$countryCode])) {
                    $list[$year][$countryCode][$cityName] = [
                        'count' => 0,
                        'city' => $cityName,
                        'state' => $stateName,
                        'stateCode' => $stateCode,
                        'formatName' => $this->locationFormatter->formatLocationName($cityName, $countryCode, null, $stateName, $stateCode),
                    ];
                }

                $list[$year][$countryCode][$cityName]['count'] += $reservation['_count'];
            }
        }

        foreach ($list as $year => &$countrys) {
            $this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey][$year]['top'] = [];

            foreach ($countrys as $countryCode => $citys) {
                uasort(
                    $countrys[$countryCode],
                    function ($a, $b) {
                        return $b['count'] - $a['count'];
                    });

                $this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey][$year]['insideCountry'][$countryCode] = array_slice($countrys[$countryCode], 0, 20, true);
                $this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey][$year]['totalsByCountry'][$countryCode] = [
                    'totalReservations' => array_sum(array_column(array_values($countrys[$countryCode]), 'count')),
                ];

                foreach ($this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey][$year]['insideCountry'][$countryCode] as $cityName => $item) {
                    $this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey][$year]['top'][] = [
                        'countryCode' => $countryCode,
                        'city' => $item['city'],
                        'state' => $item['state'],
                        'formatName' => $item['formatName'],
                        'count' => $item['count'],
                    ];
                }
            }
        }

        foreach ($this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey] as $year => &$types) {
            foreach ($types as $type => &$list) {
                if ('totalsByCountry' === $type) {
                    uasort(
                        $list,
                        function ($a, $b) {
                            return $b['totalReservations'] - $a['totalReservations'];
                        });
                } elseif ('top' === $type) {
                    uasort(
                        $list,
                        function ($a, $b) {
                            return $b['count'] - $a['count'];
                        });

                    $this->cacheData[self::TOP_HOTELS_DATA_KEY][$continentKey][$year]['top'] = array_slice($list, 0, 20);
                }
            }
        }

        echo PHP_EOL;
    }

    private function topRentedCars(array $continent): void
    {
        if (!array_key_exists(self::TOP_RENTEDCARS_DATA_KEY, $this->cacheData)) {
            $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY] = [];
        }

        $continentKey = $continent['id'];

        if (!array_key_exists($continentKey, $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY])) {
            $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey] = [];
        }

        [$startYear, $endYear, $rewritableYear] = $this->getYearParams();
        [$queryParamsValue, $queryParamsType] = $this->getQueryParams();

        $sql = "
            SELECT
                    DATE_FORMAT(r.PickupDatetime, '%Y') AS _date,
                    COUNT(*) AS _count, 
                    gt.CountryCode, gt.Country, gt.City, gt.State, gt.StateCode
            FROM Rental r
            JOIN GeoTag gt ON (gt.GeoTagID = r.DropoffGeoTagID)
            WHERE
                    (r.PickupDatetime >= :dateBegin AND r.PickupDatetime < :dateEnd)
                AND (
                        (gt.CountryCode IN (:countryCodes) OR gt.Country IN (:countryNames))
                    AND (
                            r.DropoffGeoTagID IS NOT NULL
                        OR (
                                gt.Country IS NOT NULL
                            AND gt.Country <> ''
                        )
                    )
                )
                AND r.Type = " . $this->connection->quote(Rental::TYPE_RENTAL) . "
                AND r.Hidden = 0
            GROUP BY _date, gt.GeoTagID
            ORDER BY _count DESC
        ";

        $queryParamsValue['countryCodes'] = array_keys($continent['countryCodes']);
        $queryParamsType['countryCodes'] = $this->connection::PARAM_STR_ARRAY;
        $queryParamsValue['countryNames'] = $continent['countryCodes'];
        $queryParamsType['countryNames'] = $this->connection::PARAM_STR_ARRAY;

        $list = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            if (array_key_exists($year, $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey])
                && false === $this->options['isRecalculate']
                && $rewritableYear !== $year) {
                continue;
            }

            $queryParamsValue['dateBegin'] = $year . '-01-01 00:00:00';
            $queryParamsValue['dateEnd'] = (1 + $year) . '-01-01 00:00:00';

            $this->logger->info('     top rented cars fetch >= ' . substr($queryParamsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($queryParamsValue['dateEnd'], 0, 10));
            $rentals = $this->connection
                ->executeQuery($sql, $queryParamsValue, $queryParamsType)
                ->fetchAll();

            if (empty($rentals)) {
                $this->logger->warning('NO RENTAL CARS found for year ' . $year);

                continue;
            }

            if (!array_key_exists($year, $list)) {
                $list[$year] = [];
            }

            foreach ($rentals as $rental) {
                $countryCode = null;
                $cityName = stripslashes($rental['City']);
                $stateName = $rental['State'];
                $stateCode = $rental['StateCode'];

                if (!empty($rental['CountryCode'])) {
                    $countryCode = $rental['CountryCode'];
                } elseif (false !== ($country = $this->findCountryByName($rental['Country']))) {
                    $countryCode = $country['Code'];
                }

                if (empty($countryCode) || empty($cityName)) {
                    continue;
                }

                if (array_key_exists($countryCode, $this->sameCountry)) {
                    $countryCode = $this->sameCountry[$countryCode];
                }

                if (!array_key_exists($countryCode, $list[$year])) {
                    $list[$year][$countryCode] = [];
                }

                if (!array_key_exists($cityName, $list[$year][$countryCode])) {
                    $list[$year][$countryCode][$cityName] = [
                        'count' => 0,
                        'city' => $cityName,
                        'state' => $stateName,
                        'stateCode' => $stateCode,
                        'formatName' => $this->locationFormatter->formatLocationName($cityName, $countryCode, null, $stateName, $stateCode),
                    ];
                }

                $list[$year][$countryCode][$cityName]['count'] += $rental['_count'];
            }
        }

        foreach ($list as $year => &$countrys) {
            $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey][$year]['top'] = [];

            foreach ($countrys as $countryCode => $citys) {
                uasort(
                    $countrys[$countryCode],
                    function ($a, $b) {
                        return $b['count'] - $a['count'];
                    });

                $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey][$year]['insideCountry'][$countryCode] = array_slice($countrys[$countryCode], 0, 20, true);
                $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey][$year]['totalsByCountry'][$countryCode] = [
                    'totalRentals' => array_sum(array_column(array_values($countrys[$countryCode]), 'count')),
                ];

                foreach ($this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey][$year]['insideCountry'][$countryCode] as $cityName => $item) {
                    $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey][$year]['top'][] = [
                        'countryCode' => $countryCode,
                        'city' => $item['city'],
                        'state' => $item['state'],
                        'formatName' => $item['formatName'],
                        'count' => $item['count'],
                    ];
                }
            }
        }

        foreach ($this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey] as $year => &$types) {
            foreach ($types as $type => &$list) {
                if ('totalsByCountry' === $type) {
                    uasort(
                        $list,
                        function ($a, $b) {
                            return $b['totalRentals'] - $a['totalRentals'];
                        });
                } elseif ('top' === $type) {
                    uasort(
                        $list,
                        function ($a, $b) {
                            return $b['count'] - $a['count'];
                        });

                    $this->cacheData[self::TOP_RENTEDCARS_DATA_KEY][$continentKey][$year]['top'] = array_slice($list, 0, 20);
                }
            }
        }

        echo PHP_EOL;
    }

    private function topFlightRoutes(): void
    {
        if (!array_key_exists(self::TOP_FLIGHT_ROUTES_DATA_KEY, $this->cacheData)) {
            $this->cacheData[self::TOP_FLIGHT_ROUTES_DATA_KEY] = [];
        }

        [$startYear, $endYear, $rewritableYear] = $this->getYearParams();
        [$queryParamsValue, $queryParamsType] = $this->getQueryParams();

        $sql = "
            SELECT
                    DATE_FORMAT(s.ScheduledDepDate, '%Y') AS _date,
                    UPPER(CONCAT(s.DepCode, '-', s.ArrCode)) AS _route,
                    COUNT(s.TripSegmentID) AS _count
            FROM TripSegment s
            JOIN Trip t ON (t.TripID = s.TripID)
            WHERE
                    s.TripID = t.TripID
                AND (s.DepCode IS NOT NULL AND s.ArrCode IS NOT NULL)
                AND t.Category = " . Trip::CATEGORY_AIR . " 
                AND (s.ScheduledDepDate >= :dateBegin AND s.ScheduledDepDate < :dateEnd)
                AND (s.Hidden = 0 AND t.Hidden = 0)
            GROUP BY _date, _route
            ORDER BY _count DESC
        ";

        $list = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            if (array_key_exists($year, $this->cacheData[self::TOP_FLIGHT_ROUTES_DATA_KEY])
                && false === $this->options['isRecalculate']
                && $rewritableYear !== $year) {
                continue;
            }

            $queryParamsValue['dateBegin'] = $year . '-01-01 00:00:00';
            $queryParamsValue['dateEnd'] = (1 + $year) . '-01-01 00:00:00';

            $this->logger->info('     top flight routes fetch >= ' . substr($queryParamsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($queryParamsValue['dateEnd'], 0, 10));
            $routes = $this->connection
                ->executeQuery($sql, $queryParamsValue, $queryParamsType)
                ->fetchAll();

            if (empty($routes)) {
                $this->logger->warning('NO FLIGHTS found for year ' . $year);

                continue;
            }

            usort(
                $routes,
                function ($a, $b) {
                    return $b['_count'] - $a['_count'];
                });
            $list[$year] = array_combine(array_column($routes, '_route'), $routes);
        }

        $limit = 20;
        $counter = [];

        foreach ($list as $year => $routes) {
            if (!array_key_exists($year, $counter)) {
                $counter[$year] = [
                    'long' => [],
                    'short' => [],
                ];
            }

            foreach ($routes as $route) {
                $routeCode = $route['_route'];
                [$depCode, $arrCode] = explode('-', $routeCode);

                if (count($counter[$year]['long']) >= $limit && count($counter[$year]['short']) >= $limit) {
                    continue;
                }

                $reverseRoute = $arrCode . '-' . $depCode;

                if ($this->longHaulDetector->isLongHaulRoutes(
                    [
                        ['DepCode' => $depCode, 'ArrCode' => $arrCode],
                    ])) {
                    if (!array_key_exists($reverseRoute, $counter[$year]['long'])) {
                        $counter[$year]['long'][$routeCode] = [
                            'count' => $routes[$routeCode]['_count'],
                            'reverseCount' => array_key_exists($reverseRoute, $routes) ? $routes[$reverseRoute]['_count'] : 0,
                        ];
                    }
                } else {
                    if (!array_key_exists($reverseRoute, $counter[$year]['short'])) {
                        $counter[$year]['short'][$routeCode] = [
                            'count' => $routes[$routeCode]['_count'],
                            'reverseCount' => array_key_exists($reverseRoute, $routes) ? $routes[$reverseRoute]['_count'] : 0,
                        ];
                    }
                }
            }

            $counter[$year]['long'] = array_slice($counter[$year]['long'], 0, $limit);
            $counter[$year]['short'] = array_slice($counter[$year]['short'], 0, $limit);
        }

        foreach ($counter as $year => $routes) {
            $this->cacheData[self::TOP_FLIGHT_ROUTES_DATA_KEY][$year] = $counter[$year];
        }

        echo PHP_EOL;
    }

    private function getYearParams(): array
    {
        $endYear = (int) date('Y');
        $startYear = $endYear - self::TOP_YEAR_COUNT;
        $rewratableYear = (int) date('Y');

        return [$startYear, $endYear, $rewratableYear];
    }

    private function getQueryParams(): array
    {
        $types = [
            'dateBegin' => ParameterType::STRING,
            'dateEnd' => ParameterType::STRING,
        ];

        $values = [
        ];

        return [$types, $values];
    }

    private function setCountrys(): void
    {
        $country = $this->connection->fetchAll('SELECT CountryID, Name, Code FROM Country');
        $this->countryList = array_combine(array_column($country, 'Code'), $country);
    }

    private function fillContinentsByCounry()
    {
        $countrys = $this->connection->executeQuery('SELECT Code, Name FROM Country WHERE Code IS NOT NULL')->fetchAll();
        $countryRegions = $this->connection->fetchAll(
            '
            SELECT
                    r.RegionID, r.CountryID, c.Code, c.Name
            FROM Region r
            JOIN Country c ON (r.CountryID = c.CountryID)
            WHERE
                  c.Code IN (?)
              AND Kind = ' . REGION_KIND_COUNTRY,
            [array_column($countrys, 'Code')],
            [$this->connection::PARAM_STR_ARRAY]
        );

        $regionRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Region::class);
        $continents = $regionRepository->getContinentsArray();

        $countryContinents = [];

        foreach ($continents as $continentId => $continentName) {
            $countryContinents[$continentId] = [
                'id' => $continentId,
                'name' => $continentName,
                'countryCodes' => [],
            ];
        }

        $all = [];

        foreach ($countryRegions as $countryRegion) {
            $parents = [];
            $regionRepository->findParentRegions($countryRegion['RegionID'], $parents);

            foreach ($parents as $continentId => $parent) {
                if (array_key_exists($continentId, $countryContinents)) {
                    $countryContinents[$continentId]['countryCodes'][$countryRegion['Code']] = $countryRegion['Name'];
                    $all[$countryRegion['Code']] = $countryRegion['Name'];
                }
            }
            /*
            $continentId = array_key_last($parents);

            if (array_key_exists($continentId, $countryContinents)) {
                $countryContinents[$continentId]['countryCodes'][$countryRegion['Code']] = $countryRegion['Name'];
                $all[$countryRegion['Code']] = $countryRegion['Name'];
            }
            */
        }
        $countryContinents[0] = [
            'id' => 0,
            'name' => 'All',
            'countryCodes' => $all,
        ];

        ksort($countryContinents);
        $this->continetList = $countryContinents;
    }

    private function findCountryByName($name)
    {
        static $alreadyFound;

        if (null === $alreadyFound) {
            $alreadyFound = [];
        }

        if (array_key_exists($name, $alreadyFound)) {
            return $alreadyFound[$name];
        }

        foreach ($this->countryList as $id => $country) {
            if ($name === $country['Name'] && !empty($country['Code'])) {
                $alreadyFound[$name] = $country;

                return $country;
            }
        }

        $alreadyFound[$name] = false;

        return false;
    }

    private function assingCountryByContinent()
    {
        $regionRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Region::class);
        $continents = $regionRepository->getContinentsArray();

        $countryCodes = [];

        foreach ([self::TOP_HOTELS_DATA_KEY, self::TOP_RENTEDCARS_DATA_KEY] as $cacheKey) {
            foreach ($this->cacheData[$cacheKey] as $continentId => $data) {
                foreach ($data as $types) {
                    $countryCodes = array_merge($countryCodes, array_column($types['top'], 'countryCode'));
                    $countryCodes = array_merge($countryCodes, array_keys($types['insideCountry']));
                    $countryCodes = array_merge($countryCodes, array_keys($types['totalsByCountry']));
                }
            }
        }
        $countryCodes = array_unique($countryCodes);

        $countryRegions = $this->connection->fetchAll(
            '
            SELECT
                    r.RegionID, r.CountryID, c.Code
            FROM Region r
            JOIN Country c ON (r.CountryID = c.CountryID)
            WHERE
                  c.Code IN (?)
              AND Kind = ' . REGION_KIND_COUNTRY,
            [$countryCodes],
            [$this->connection::PARAM_STR_ARRAY]
        );

        $countryContinents = [];

        foreach ($continents as $continentId => $continentName) {
            $countryContinents[$continentId] = [
                'name' => $continentName,
                'countryCodes' => [],
            ];
        }

        foreach ($countryRegions as $countryRegion) {
            $parents = [];
            $regionRepository->findParentRegions($countryRegion['RegionID'], $parents);

            foreach ($parents as $continentId => $parent) {
                if (array_key_exists($continentId, $countryContinents)) {
                    $countryContinents[$continentId]['countryCodes'][] = $countryRegion['Code'];
                }
            }
            // $continentId = array_key_last($parents);

            // if (array_key_exists($continentId, $countryContinents)) {
            //    $countryContinents[$continentId]['countryCodes'][] = $countryRegion['Code'];
            // }
        }

        foreach ($countryContinents as $continentId => $items) {
            if (empty($items['countryCodes'])) {
                unset($countryContinents[$continentId]);
            }
        }

        ksort($countryContinents);
        $this->cacheData[self::CONTINENT_COUNTRY_DATA_KEY] = $countryContinents;

        $countrys = $this->connection->fetchAll(
            '
            SELECT Code, Name
            FROM Country
            WHERE Code IN (?)',
            [$countryCodes],
            [$this->connection::PARAM_STR_ARRAY]
        );

        $this->cacheData[self::COUNTRY_BY_CODE_DATA_KEY] = array_combine(array_column($countrys, 'Code'), $countrys);
    }

    private function assignAircodes()
    {
        $aircodeRepository = $this->entityManager->getRepository(Aircode::class);

        $aircodes = [];

        foreach ($this->cacheData[self::TOP_FLIGHT_ROUTES_DATA_KEY] as $year => $data) {
            $list = array_keys($data['long']);
            $list = array_merge($list, array_keys($data['short']));

            foreach ($list as $deparr) {
                [$dep, $arr] = explode('-', $deparr);
                $aircodes[] = $dep;
                $aircodes[] = $arr;
            }
        }

        $aircodes = $aircodeRepository->findBy(['aircode' => array_unique($aircodes)]);
        $storage = [];

        foreach ($aircodes as $aircode) {
            $storage[$aircode->getAircode()] = [
                'airportName' => $aircode->getAirportName(false),
                // 'cityCode' => $aircode->getCitycode(),
                // 'cityName' => $aircode->getCityname(),
                // 'countryCode' => $aircode->getCountrycode(),
                // 'stateName' => $aircode->getStatename(),
            ];
        }

        $this->cacheData[self::AIRCODE_DATA_KEY] = $storage;
    }

    private function testAvailableData()
    {
        $cacheData = $this->s3Client->getObject(
            [
                'Bucket' => self::BUCKET,
                'Key' => self::CACHE_KEY . ':' . self::SUFFIX,
            ]);
        $cacheData = unserialize($cacheData['Body']);

        $notFound = 'Key not found';

        $keys = [self::TYPE_FLIGHTS, self::TYPE_HOTELS, self::TYPE_RENTED_CARS];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $cacheData['type'][self::PERIOD_DAY])) {
                throw new \Exception($notFound);
            }

            if (!array_key_exists($key, $cacheData['type'][self::PERIOD_MONTH])) {
                throw new \Exception($notFound);
            }

            if (!array_key_exists($key, $cacheData['provider'][self::PERIOD_DAY])) {
                throw new \Exception($notFound);
            }

            if (!array_key_exists($key, $cacheData['provider'][self::PERIOD_MONTH])) {
                throw new \Exception($notFound);
            }
        }

        $days = $this->fetchDatePeriods(true);

        foreach ($days as $day) {
            $dateDay = $day->format('Y-m-d');
            $dateMonth = $day->format('Y-m');

            foreach ($keys as $key) {
                if (!array_key_exists($dateDay, $cacheData['type'][self::PERIOD_DAY][$key])) {
                    throw new \Exception($notFound . ' : ' . $dateDay);
                }

                if (!array_key_exists($dateMonth, $cacheData['type'][self::PERIOD_MONTH][$key])) {
                    throw new \Exception($notFound . ' : ' . $dateMonth);
                }

                if (!array_key_exists($key, $cacheData['provider'][self::PERIOD_DAY])) {
                    throw new \Exception($notFound . ' : ' . $dateDay);
                }

                if (!array_key_exists($key, $cacheData['provider'][self::PERIOD_MONTH])) {
                    throw new \Exception($notFound . ' : ' . $dateMonth);
                }

                if (!array_key_exists($dateMonth, $cacheData[self::TYPE_LONGHAUL][self::PERIOD_MONTH][self::TYPE_FLIGHTS])) {
                    throw new \Exception($notFound . ' : ' . $dateMonth);
                }

                if (!array_key_exists('short', $cacheData[self::TYPE_LONGHAUL][self::PERIOD_MONTH][self::TYPE_FLIGHTS][$dateMonth])) {
                    throw new \Exception($notFound . ' : ' . $dateMonth);
                }

                if (!array_key_exists('long', $cacheData[self::TYPE_LONGHAUL][self::PERIOD_MONTH][self::TYPE_FLIGHTS][$dateMonth])) {
                    throw new \Exception($notFound . ' : ' . $dateMonth);
                }

                if (!array_key_exists($dateMonth, $cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][self::PERIOD_MONTH]['banks'])) {
                    throw new \Exception($notFound . ' : ' . $dateMonth);
                }

                if (!array_key_exists($dateMonth, $cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][self::PERIOD_MONTH]['hotels'])) {
                    throw new \Exception($notFound . ' : ' . $dateMonth);
                }

                if (!array_key_exists($dateMonth, $cacheData[self::TOTALLY_EARNING_MP_DATA_KEY][self::PERIOD_MONTH]['airlines'])) {
                    throw new \Exception($notFound . ' : ' . $dateMonth);
                }
            }
        }

        $keys = ['all', self::PERIOD_DAY, self::PERIOD_MONTH];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $cacheData['usersCount'])) {
                throw new \Exception($notFound);
            }
        }

        foreach ($days as $day) {
            $dateDay = $day->format('Y-m-d');
            $dateMonth = $day->format('Y-m');

            if (!array_key_exists($dateDay, $cacheData['usersCount'][self::PERIOD_DAY])) {
                throw new \Exception($notFound . ' : ' . $dateDay);
            }

            if (!array_key_exists($dateMonth, $cacheData['usersCount'][self::PERIOD_MONTH])) {
                throw new \Exception($notFound . ' : ' . $dateMonth);
            }
        }

        $keys = [
            self::TOP_FLIGHT_ROUTES_DATA_KEY,
            self::TOP_HOTELS_DATA_KEY,
            self::TOP_RENTEDCARS_DATA_KEY,
            self::CONTINENT_COUNTRY_DATA_KEY,
            self::COUNTRY_BY_CODE_DATA_KEY,
            self::AIRCODE_DATA_KEY,
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $cacheData)) {
                throw new \Exception($notFound . ' : ' . $key);
            }
        }
    }

    private function fetchDatePeriods(bool $isDaily): array
    {
        $period = [];
        $date = new \DateTimeImmutable();
        $date->setTime(0, 0, 0);

        if ($isDaily) {
            $count = self::PERIOD_DAY_COUNT;
            $date->modify('-' . $count . ' days');

            for ($i = $count; $i > 0; $i--) {
                $period[] = $date->modify('-' . $i . ' day');
            }

            return $period;
        }

        $count = self::PERIOD_MONTH_COUNT;
        $date->modify('-' . $count . ' months');

        for ($i = $count; $i > 0; $i--) {
            $period[] = new \DateTimeImmutable('@' . strtotime(date('Y-m-01') . " -$i months"));
        }

        return $period;
    }

    private function testData(string $arg)
    {
        $date = str_split($arg, 4);
        $date = new \DateTime($date[0] . '-' . $date[1]);
        $providers = self::TOTALLY_BANKS_PROVIDER_ID;

        $result = $this->connection->fetchAll(
            "
            SELECT
                DATE_FORMAT(ah.PostingDate, '%Y-%m') AS _date,
                SUM(CASE WHEN Miles > 0 THEN Miles ELSE 0 END) AS _earnings,
                SUM(CASE WHEN Miles < 0 THEN Miles ELSE 0 END) AS _redemptions
            FROM AccountHistory ah
            JOIN Account a ON (a.AccountID = ah.AccountID)
            WHERE
                    ah.PostingDate BETWEEN '" . $date->format('Y-m-01') . "' AND '" . $date->format('Y-m-t') . "'
                AND a.ProviderID IN (" . implode(',', array_keys($providers)) . ")
                AND (toInt64(ah.Miles) <> 10000000 AND toInt64(ah.Miles) -10000000)
            GROUP BY _date
        ");

        print_r($result);

        return $result;
    }

    private function fetchCancelled(string $type)
    {
        $period = self::PERIOD_MONTH;

        if (!array_key_exists(self::CANCELLED_DATA_KEY, $this->cacheData)) {
            $this->cacheData[self::CANCELLED_DATA_KEY] = [$period => []];
        }

        $isRecalc = false;
        [$dateBegin, $dateEnd, $sqlDateFormat, $dateFormat] = array_values($this->getPeriod($period, true));

        $paramsValue = [
            'dateFormat' => $sqlDateFormat,
        ];
        $paramsType = [
            'dateFormat' => ParameterType::STRING,
            'dateBegin' => ParameterType::STRING,
            'dateEnd' => ParameterType::STRING,
        ];

        $this->logger->info(' - ' . $type . ' by ' . $period);

        switch ($type) {
            case self::TYPE_FLIGHTS:
                $sql = '
                    SELECT
                            DATE_FORMAT(s.ScheduledDepDate, :dateFormat) AS _date,
                            COUNT(DISTINCT t.TripID) AS _count
                    FROM
                            TripSegment s,
                            Trip t
                    JOIN Usr u ON (t.UserID = u.UserID AND u.ValidMailboxesCount > 0
                        ) 
                    WHERE
                            s.TripID = t.TripID
                        AND t.Category = :tripCategory
                        AND (s.ScheduledDepDate >= :dateBegin AND s.ScheduledDepDate < :dateEnd)
                        -- AND (s.Hidden = 0 AND t.Hidden = 0)
                        AND t.Cancelled = 1
                    GROUP BY _date
                ';
                $paramsValue['tripCategory'] = Trip::CATEGORY_AIR;
                $paramsType['tripCategory'] = ParameterType::INTEGER;

                break;

            case self::TYPE_HOTELS:
                $sql = '
                    SELECT
                            DATE_FORMAT(CheckInDate, :dateFormat) AS _date,
                            COUNT(*) AS _count
                    FROM
                            Reservation r
                    JOIN Usr u ON (r.UserID = u.UserID AND u.ValidMailboxesCount > 0
                        )
                    WHERE
                            CheckInDate >= :dateBegin
                        AND CheckInDate < :dateEnd
                        -- AND Hidden = 0
                        AND Cancelled = 1
                    GROUP BY _date
                ';

                break;

            case self::TYPE_RENTED_CARS:
                $sql = '
                    SELECT
                            DATE_FORMAT(PickupDatetime, :dateFormat) AS _date,
                            COUNT(*) AS _count
                    FROM
                            Rental r
                    JOIN Usr u ON (r.UserID = u.UserID AND u.ValidMailboxesCount > 0
                        )
                    WHERE
                            PickupDatetime >= :dateBegin
                        AND PickupDatetime < :dateEnd
                        AND Type = :rentalType
                        -- AND Hidden = 0
                        AND Cancelled = 1
                    GROUP BY _date
                ';
                $paramsValue['rentalType'] = Rental::TYPE_RENTAL;
                $paramsType['rentalType'] = ParameterType::STRING;

                break;

            default:
                throw new \Exception('Unsupported Type');
        }

        $count = self::PERIOD_MONTH_COUNT + self::PERIOD_MONTH_FUTURE;
        $rewratableDate = date('Y-m');
        $now = new \DateTime();

        for ($i = 0; $i <= $count; $i++) {
            $step = 0 === $i ? 0 : 1;
            $dateBegin->modify('+' . $step . ' month');

            if (date('Ym') === $dateBegin->format('Ym')) {
                $paramsValue['dateEnd'] = (new \DateTime())->modify('+1 day')->format('Y-m-d 00:00:00');
            } else {
                $paramsValue['dateEnd'] = (clone $dateBegin)->modify('next month')->format('Y-m-01 00:00:00');
            }

            $keyDate = $dateBegin->format($dateFormat);
            $paramsValue['dateBegin'] = $dateBegin->format('Y-m-d H:i');
            // $paramsValue['userIdByPeriod'] = array_keys($this->getFilteredUsersByMinCreationDate($dateBegin));
            // $paramsType['userIdByPeriod'] = $this->connection::PARAM_INT_ARRAY;
            $isFuture = $dateBegin->getTimestamp() > $now->getTimestamp();

            $this->logger->info('     fetch >= ' . substr($paramsValue['dateBegin'], 0, 10) . ' ...  < ' . substr($paramsValue['dateEnd'], 0, 10));
            $segments = $this->connection
                ->executeQuery($sql, $paramsValue, $paramsType)
                ->fetchAll();
            $countByDates = array_combine(array_column($segments, '_date'), array_column($segments, '_count'));

            $isExistsInCache = array_key_exists($type, $this->cacheData[self::CANCELLED_DATA_KEY][$period]) && array_key_exists($keyDate, $this->cacheData[self::CANCELLED_DATA_KEY][$period][$type]);

            if ($isRecalc || !$isExistsInCache || $rewratableDate === $keyDate || $isFuture) {
                $this->cacheData[self::CANCELLED_DATA_KEY][$period][$type][$keyDate] = array_key_exists($keyDate, $countByDates) ? (int) $countByDates[$keyDate] : 0;
            }
        }

        echo PHP_EOL;
    }
}
