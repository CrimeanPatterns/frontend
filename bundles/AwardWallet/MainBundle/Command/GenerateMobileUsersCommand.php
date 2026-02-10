<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbSegment;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountbalance;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\AwDataGenerator;
use AwardWallet\MainBundle\Globals\DateUtils;
use AwardWallet\MainBundle\Globals\ProcessUtils;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\SubscriberMock;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmt;

class GenerateMobileUsersCommand extends Command
{
    private const PASSWORD = 'awtestpassword32he0sdfush083h';
    protected static $defaultName = 'aw:mobile:generate-users';
    /**
     * @var AwDataGenerator
     */
    private $awDataGenerator;
    /**
     * @var string
     */
    private $ip;
    /**
     * @var EntityManager
     */
    private $entityManager;
    private BookingRequestManager $bookingRequestManager;

    public function __construct(
        AwDataGenerator $awDataGenerator,
        EntityManagerInterface $entityManager,
        BookingRequestManager $bookingRequestManager
    ) {
        parent::__construct();

        $this->awDataGenerator = $awDataGenerator;
        $this->entityManager = $entityManager;
        $this->bookingRequestManager = $bookingRequestManager;
    }

    public function createChineeseUser(string $loginSuffix, string $preset, array $mixin)
    {
        // Xiao Ming Wang
        $user = $this->createAwUser('XiaoMingWang' . $loginSuffix, \array_merge(
            [
                'FirstName' => 'Xiao',
                'MidName' => 'Ming',
                'LastName' => 'Wang',
                'Language' => 'zh_TW',
                'Region' => 'TW',
            ],
            $this->ipfyUser($this->ip),
            $mixin
        ));

        $this->awDataGenerator->createAwAccount($user, 'chinaeastern', '987654321001', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 475938,
            'TotalBalance' => 475938,
            'ExpirationDate' => $this->date('+14 month 4 day 0 seconds'),
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Gold',
            ],
        ]);

        $this->awDataGenerator->createAwAccount($user, 'chinasouthern', '680012345678', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 688815,
            'TotalBalance' => 688815,
            'ExpirationDate' => $this->date('+14 month 4 day 0 seconds'),
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Base',
            ],
        ]);

        $returnAccount = $timelineAccount = $this->awDataGenerator->createAwAccount($user, 'airchina', 'CA001234567890', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 782704 - 1530,
            'TotalBalance' => 782704 - 1530,
            'UpdateDate' => $this->date('-15 day 0 seconds'),
        ]);

        $itinerariesData = [
            [
                'Kind' => 'T',
                'RecordLocator' => 'CD1295',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'CA0185',
                        'DepCode' => 'SFO',
                        'DepDate' => $startDate = $this->removeSeconds(new \DateTimeImmutable('-3 days 0 seconds')),
                        'ArrCode' => 'LAX',
                        'ArrDate' => $endDate = $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'CA0189',
                        'DepCode' => 'LAX',
                        'DepDate' => $startDate = $endDate->modify('+2 hour +57 minute 0 seconds'),
                        'ArrCode' => 'SYD',
                        'ArrDate' => $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'CD1296',
                'Passengers' => ['Xiao Ming Wang'],
                'AccountNumbers' => 'XXXX1640',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'CA745',
                        'DepCode' => 'PVG',
                        'DepDate' => $currentTripStartDate = $this->createDateWithRelativeFormat('-1 hour -20 minutes 0 seconds', 'Asia/Shanghai'),
                        'ArrCode' => 'CAN',
                        'ArrDate' => $currentTripStartDate->modify('+2 hours 40 minutes 0 seconds'),
                        'Duration' => '2h 40min',
                        'Seats' => '16D',
                        'Cabin' => 'Economy',
                        'BookingClass' => 'P',
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'CD1299',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AC0185',
                        'DepCode' => 'PVG',
                        'DepDate' => $planStart = $startDate = $this->removeSeconds(new \DateTimeImmutable('+1 month 10:40')),
                        'ArrCode' => 'CAN',
                        'ArrDate' => $endDate = $startDate->modify('13:20'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'CD1297',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AC0285',
                        'DepCode' => 'CAN',
                        'DepDate' => $startDate = $endDate->modify('13:20'),
                        'ArrCode' => 'HKG',
                        'ArrDate' => $endDate = $startDate->modify('14:40'),
                    ],
                    [
                        'FlightNumber' => 'AC0389',
                        'DepCode' => 'HKG',
                        'DepDate' => $startDate = $endDate->modify('+50 minute 0 seconds'),
                        'ArrCode' => 'SHA',
                        'ArrDate' => $planEnd = $endDate = $startDate->modify('+2 hours'),
                    ],
                ],
            ],
        ];

        $hotelData = [
            [
                'Kind' => 'R',
                'ConfirmationNumber' => 'TS1500',
                'HotelName' => 'Shangri-La Hotel, Guangzhou ',
                'CheckInDate' => $startDate = $endDate->modify('16:00'),
                'CheckOutDate' => $endDate = $startDate->modify('+5 days 10:20'),
                'Address' => '1 Hui Zhan Dong Road, Hai Zhu District, Guangzhou 510308 China',
            ],
        ];

        $this->runInForkWithReconnect(function () use ($timelineAccount, $itinerariesData) {
            $this->awDataGenerator->mockAwProvider('Airchina', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(782704);

                    $this->setExpirationDate((new \DateTime('+3 month +1 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Status', 'Gold');
                    $this->SetProperty('Name', 'Xiao Ming Wang');
                    $this->SetProperty('ClubMiles', '114482');
                    $this->SetProperty('Segments', '49');
                    $this->SetProperty('ExpiringBalance', '0');
                    $this->SetProperty('NextEliteLevel', 'Platinum');
                    $this->SetProperty('CardNumber', 'CA001234567890');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($itinerariesData),
            ]);

            $this->awDataGenerator->checkAccount($timelineAccount->getAccountid(), true, true);
        });

        $returnSegment = $this->findTripsegment($timelineAccount, 'CD1296', 'PVG', 'CAN');

        $lucy = $this->awDataGenerator->createAwFamilyMember($user, 'Lucy', 'Liu');

        $ghaAccount = $this->awDataGenerator->createAwAccount($user, 'gha', '8070605040', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 45,
            'TotalBalance' => 45,
            'LastChangeDate' => $this->date('-12 days 0 seconds'),
            'ExpirationDate' => $this->date('+3 month 29 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Black',
            ],
        ]);

        $this->runInForkWithReconnect(function () use ($ghaAccount, $hotelData) {
            $this->awDataGenerator->mockAwProvider('Gha', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(45 + 9);
                    $this->SetExpirationDate((new \DateTime('+3 month 29 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Status', 'Black');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($hotelData),
            ]);

            $this->awDataGenerator->checkAccount($ghaAccount->getAccountid(), true, true);
        });

        $this->awDataGenerator->createAwAccount($user, 'shangrila', '', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 50299,
            'TotalBalance' => 50299,
            'ExpirationDate' => $this->date('+10 month 4 day 0 seconds'),
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Diamond',
            ],
        ]);

        $user->setDefaultBooker($this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneByLogin('TravelCompanyABC'));

        $this->createBookingStuff($user, [
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-5 minutes 0 seconds'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_BOOKED_OPENED,
                'Segments' => [
                    [
                        'Dep' => 'PVG',
                        'Arr' => 'TPE',
                        'DepDate' => $planStart,
                    ],
                    [
                        'Dep' => 'TPE',
                        'Arr' => 'CAN',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -10 days 13:45'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_PROCESSING,
                'Segments' => [
                    [
                        'Dep' => 'HKG',
                        'Arr' => 'PEK',
                        'DepDate' => new \DateTime('-1 month'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -20 days 11:14'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_CANCELED,
                'Segments' => [
                    [
                        'Dep' => 'CAN',
                        'Arr' => 'TPE',
                        'DepDate' => new \DateTime('-1 month -8 days 0 seconds'),
                    ],
                ],
            ],
        ]);

        return [$preset, $user, $returnAccount, $returnSegment];
    }

    protected function configure()
    {
        $this
            ->addOption('cleanup', 'c', InputOption::VALUE_NONE, 'remove only mode, cleans up previous users')
            ->addArgument('ip', InputArgument::OPTIONAL, 'ip address', '107.158.178.6');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ip = $input->getArgument('ip');

        // TODO: what is it?
        // $this->getContainer()->set('fs.trip_alerts.subscriber', new SubscriberMock());

        $output->writeln('Cleanup users...');
        $stmt = $this->entityManager->getConnection()->executeQuery("
            select UserID, Login from Usr where 
                (
                    Login like 'test-mobile-%' or
                    Login like 'mobile-%' or
                    Login like 'mobile+%'
                ) and
                 ItineraryCalendarCode = md5(concat(Login, ?))
        ", [self::class]);

        $schemaManager = new \TSchemaManager();

        foreach (stmt($stmt) as [$userId, $login]) {
            $output->writeln("Removing {$login} ($userId)...");
            $schemaManager->DeleteRow("Usr", $userId, true);
        }

        if ($input->getOption('cleanup')) {
            return 0;
        }

        $output->writeln('Generating users...');

        $usersData = [
            $this->createEnglishUser(),
            $this->createSpanishUser(),
            $this->createPortugeseUser(),
            $this->createFrenchUser(),
            $this->createDeutschUser(),
            $this->createRussianUser(),
            $this->createChineeseUser('1', 'zh_CN', ['Language' => 'zh_CN', 'Region' => 'CN']),
            $this->createChineeseUser('2', 'zh_TW', ['Language' => 'zh_TW', 'Region' => 'TW']),
        ];

        $output->writeln("\nPresets:\n");
        $output->writeln(
            it($usersData)
            ->flatMap(function (array $userData) {
                [$preset, $user, $account, $tripsegment] = $userData;

                return [
                    $preset => [
                        'preset' => $preset,
                        'login' => $user->getLogin(),
                        'userid' => $user->getUserid(),
                        'password' => self::PASSWORD,
                        'account' => $account->getAccountid(),
                        'tripsegment' => 'T.' . $tripsegment->getId(),
                    ],
                ];
            })
            ->toJSONWithKeys(\JSON_PRETTY_PRINT)
        );

        return 0;
    }

    protected function createBookingStuff(Usr $user, array $requestsData): array
    {
        $booker = $user->getDefaultBooker();
        $requests = [];

        foreach ($requestsData as $requestData) {
            $abRequest = $this->bookingRequestManager->getEmptyBookingRequest(['user' => $user, 'for_booker' => false]);
            $abRequest->setBooker($booker);
            $abRequest->setStatus($requestData['Status']);
            $abRequest->setContactPhone('+100500');
            $abRequest->getPassengers()->clear();
            $abRequest->getSegments()->clear();
            $this->entityManager->persist($abRequest);
            $abRequest->setLastUpdateDate(DateUtils::toMutable($requestData['LastUpdateDate']));
            $abRequest->setCreateDate(DateUtils::toMutable($requestData['CreateDate']));
            $this->entityManager->flush();
            $this->entityManager->refresh($abRequest);

            $segments = $abRequest->getSegments();

            foreach ($requestData['Segments'] ?? [] as $segment) {
                $segment = (new AbSegment())
                    ->setDep($segment['Dep'])
                    ->setArr($segment['Arr'])
                    ->setDepDateFrom(DateUtils::toMutable($segment['DepDate']))
                    ->setRequest($abRequest)
                    ->setDepDateFlex(false)
                    ->setReturnDateFlex(false)
                    ->setRoundTripDaysFlex(false);

                $segments->add($segment);
                $this->entityManager->persist($segment);
            }

            $this->entityManager->persist($abRequest);
            $this->entityManager->flush();
            $requests[] = $abRequest;
        }

        return $requests;
    }

    private function createEnglishUser(): array
    {
        $user = $this->createAwUser('JohnSmith', \array_merge(['FirstName' => 'John', 'LastName' => 'Smith'], $this->ipfyUser($this->ip)));

        $this->awDataGenerator->createAwAccount($user, 'alaskaair', '1234567', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 311079,
            'TotalBalance' => 311079,
            'LastBalance' => 311079 - 1000,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+8 month 12 day 0 seconds'),
            'BalanceData' => [
                (new Accountbalance())
                    ->setBalance(311079 - 1000)
                    ->setUpdatedate($this->date('-1 day 0 seconds')),
            ],
            'Properties' => [PROPERTY_KIND_STATUS => 'MVP Gold'],
            'SubAccounts' => [
                [
                    'Code' => 'AlaskaairDiscountCodesmobiletestremoveme',
                    'DisplayName' => 'Bank of America Companion Fare',
                    'Properties' => [
                        'Discount' => 'Special Fare',
                    ],
                ],
            ],
        ]);

        $timelineAccount = $this->awDataGenerator->createAwAccount($user, 'aa', 'H01234', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 1079,
            'TotalBalance' => 1079,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+9 month 14 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Member'],
        ]);

        $currentTripStartDate = $this->createDateWithRelativeFormat('-1 hour -40 minutes 0 seconds', 'America/New_York');
        $itinerariesData = [
            [
                'Kind' => 'T',
                'RecordLocator' => 'AB1123',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AA9185',
                        'DepCode' => 'SFO',
                        'DepDate' => $startDate = $this->removeSeconds(new \DateTimeImmutable('-3 days 0 seconds')),
                        'ArrCode' => 'LAX',
                        'ArrDate' => $endDate = $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'AA9189',
                        'DepCode' => 'LAX',
                        'DepDate' => $startDate = $endDate->modify('+2 hour +57 minute 0 seconds'),
                        'ArrCode' => 'SYD',
                        'ArrDate' => $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'AB1124',
                'Passengers' => ['John Smith'],
                'AccountNumbers' => 'XXXX9012',
                'TotalCharge' => '320',
                'Currency' => 'USD',
                'Tax' => '40',
                'BaseFare' => '280',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AA9185',
                        'DepCode' => 'JFK',
                        'DepDate' => $currentTripStartDate,
                        'ArrCode' => 'LAX',
                        'ArrDate' => $currentTripStartDate->modify('+3 hour +21 minute 0 seconds'),
                        'Duration' => '6h 20m',
                        'Seats' => '3C',
                        'Cabin' => 'Economy',
                        'Aircraft' => 'Boeing 737-800',
                        'Meal' => 'Yes',
                        'BookingClass' => 'U',
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'AB1125',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AA9185',
                        'DepCode' => 'SFO',
                        'DepDate' => $planStart = $startDate = $this->removeSeconds(new \DateTimeImmutable('+1 month +12 days 18:50')),
                        'ArrCode' => 'LAX',
                        'ArrDate' => $endDate = $startDate->modify('+1 hour +35 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'AA9189',
                        'DepCode' => 'LAX',
                        'DepDate' => $startDate = $endDate->modify('+2 hour +57 minute 0 seconds'),
                        'ArrCode' => 'SYD',
                        'ArrDate' => $endDate = $startDate->modify('+9 hour +43 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'AB1126',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AA9285',
                        'DepCode' => 'SYD',
                        'DepDate' => $startDate = $endDate->modify('9:00'),
                        'ArrCode' => 'ICN',
                        'ArrDate' => $endDate = $startDate->modify('+8 hour +40 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'AA9389',
                        'DepCode' => 'ICN',
                        'DepDate' => $startDate = $endDate->modify('+1 hour +50 minute 0 seconds'),
                        'ArrCode' => 'JFK',
                        'ArrDate' => $planEnd = $endDate = $startDate->modify('+14 hour'),
                    ],
                ],
            ],
        ];

        $hotelData = [
            [
                'Kind' => 'R',
                'ConfirmationNumber' => 'FM0501',
                'HotelName' => 'Park Hyatt, Sydney',
                'CheckInDate' => $startDate = $endDate->modify('+1 day 14:00'),
                'CheckOutDate' => $endDate = $startDate->modify('+6 days 6:00'),
                'Address' => '7 Hickson Road, The Rocks Sydney, New South Wales, Australia, 2000',
            ],
        ];

        ProcessUtils::runInFork(function () use ($timelineAccount, $itinerariesData) {
            $this->awDataGenerator->mockAwProvider('Aa', [
                'Parse' => function () {
                    $this->SetBalance(1079);
                    $this->SetExpirationDate((new \DateTime('+9 month 14 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Status', 'Member');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($itinerariesData),
            ]);

            $this->awDataGenerator->checkAccount($timelineAccount->getAccountid(), true, true);
        });

        $returnSegment = $this->findTripsegment($timelineAccount, 'AB1124', 'JFK', 'LAX');

        $this->awDataGenerator->createAwAccount($user, 'british', '543210', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 165022,
            'TotalBalance' => 165022,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+6 month 14 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Gold'],
        ]);

        $this->awDataGenerator->createAwAccount($user, 'jetblue', 'JSmith@gmail.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 52300,
            'TotalBalance' => 52300,
            'LastBalance' => 52300 - 8300,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+6 month 14 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Gold'],
            'SubAccounts' => [
                [
                    'Code' => 'JetBlueTravelBanktestmobileremoveme',
                    'DisplayName' => 'Jet Blue (Travel Bank)',
                    'Balance' => 1200,
                    'Properties' => [
                        'Currency' => 'USD',
                    ],
                ],
            ],
        ]);

        $hhonorsAccount = $this->awDataGenerator->createAwAccount($user, 'hhonors', 'JSmith', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 187063,
            'TotalBalance' => 187063,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+24 month 14 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Gold'],
        ]);

        $this->runInForkWithReconnect(function () use ($hhonorsAccount, $hotelData) {
            $this->awDataGenerator->mockAwProvider('Hhonors', [
                'Parse' => function () {
                    $this->SetBalance(187063 - 2300);
                    $this->SetExpirationDate((new \DateTime('+9 month 14 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Status', 'Gold');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($hotelData),
            ]);
            $this->awDataGenerator->checkAccount($hhonorsAccount->getAccountid(), true, true);
        });

        $this->awDataGenerator->createAwAccount($user, 'ichotelsgroup', 'JSmith@yahoo.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 33326,
            'TotalBalance' => 33326,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+3 month 4 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Club'],
        ]);

        $this->awDataGenerator->createAwAccount($user, 'marriott', 'JSmith@yahoo.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 81640,
            'TotalBalance' => 81640,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+11 month 4 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Gold'],
            'SubAccounts' => [
                [
                    'DisplayName' => 'Premier Visa Anniv. Free Night, 1-5 Hotels',
                ],
            ],
        ]);

        $jennifer = $this->awDataGenerator->createAwFamilyMember($user, 'Jennifer', 'Smith');

        $returnAccount = $this->awDataGenerator->createAwAccount($user, 'bestbuy', 'JenSmith@yahoo.com', '', [
            'BackgroundCheck' => 0,
            'UserAgentID' => $jennifer,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 224,
            'LastBalance' => 224 - 59,
            'TotalBalance' => 224,
            'ExpirationDate' => $this->date('+9 month'),
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_NUMBER => '012345678901234567890',
                PROPERTY_KIND_NAME => 'Jennifer Smith',
                'Number' => '1020304050',
                'StatusExpire' => (new \DateTime('+1 year 9 month'))->format('m/d/Y'),
                'Pending' => 0,
                'Spent' => '$0',
                PROPERTY_KIND_LAST_ACTIVITY => (new \DateTime('-1 month'))->format('Y-m-d'),
            ],
        ]);

        $plan = (new Plan())
            ->setStartDate(DateUtils::toMutable($planStart))
            ->setEndDate(DateUtils::toMutable($planEnd))
            ->setUser($user)
            ->setName('Trip to Sydney');
        $this->entityManager->persist($plan);
        $this->entityManager->flush($plan);

        $user->setDefaultBooker($this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneByLogin('TravelCompanyABC'));

        $this->createBookingStuff($user, [
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-5 minutes 0 seconds'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_BOOKED_OPENED,
                'Segments' => [
                    [
                        'Dep' => 'SFO',
                        'Arr' => 'LAX',
                        'DepDate' => $planStart,
                    ],
                    [
                        'Dep' => 'LAX',
                        'Arr' => 'SYD',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                    [
                        'Dep' => 'SYD',
                        'Arr' => 'SFO',
                        'DepDate' => $planStart->modify('+2 day 0 seconds'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -10 days 13:45'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_PROCESSING,
                'Segments' => [
                    [
                        'Dep' => 'JFK',
                        'Arr' => 'LAX',
                        'DepDate' => new \DateTime('-1 month'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -20 days 11:14'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_CANCELED,
                'Segments' => [
                    [
                        'Dep' => 'AUS',
                        'Arr' => 'LAS',
                        'DepDate' => new \DateTime('-1 month -8 days 0 seconds'),
                    ],
                ],
            ],
        ]);

        return ['en', $user, $returnAccount, $returnSegment];
    }

    private function createSpanishUser(): array
    {
        // Miguel Javier Pérez
        $user = $this->createAwUser('MiguelPerez', \array_merge(
            [
                'FirstName' => 'Miguel',
                'LastName' => 'Pérez',
                'MidName' => 'Javier',
                'Language' => 'es',
                'Region' => 'ES',
            ],
            $this->ipfyUser($this->ip)));

        $returnAccount = $this->awDataGenerator->createAwAccount($user, 'aerolineas', '12345123', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 106531,
            'TotalBalance' => 106531,
            'LastBalance' => 106531 - 1000,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+8 month 12 day 0 seconds'),
            'BalanceData' => [
                (new Accountbalance())
                    ->setBalance(311079 - 1000)
                    ->setUpdatedate($this->date('-1 day 0 seconds')),
            ],
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Classic',
                PROPERTY_KIND_NEXT_ELITE_LEVEL => 'Gold',
                PROPERTY_KIND_NAME => 'Miguel Javier Pérez',
            ],
        ]);

        $timelineAccount = $this->awDataGenerator->createAwAccount($user, 'aeromexico', '5040302010', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 79311,
            'TotalBalance' => 79311,
            'LastChangeDate' => $this->date('-12 days 0 seconds'),
            'ExpirationDate' => $this->date('+6 month 14 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Clasico'],
        ]);

        $currentTripStartDate = $this->createDateWithRelativeFormat('-37 minutes 0 seconds', 'Europe/Madrid');
        $itinerariesData = [
            [
                'Kind' => 'T',
                'RecordLocator' => 'AB1131',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AM0185',
                        'DepCode' => 'SFO',
                        'DepDate' => $startDate = $this->removeSeconds(new \DateTimeImmutable('-3 days 0 seconds')),
                        'ArrCode' => 'LAX',
                        'ArrDate' => $endDate = $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'AM0189',
                        'DepCode' => 'LAX',
                        'DepDate' => $startDate = $endDate->modify('+2 hour +57 minute 0 seconds'),
                        'ArrCode' => 'SYD',
                        'ArrDate' => $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'AB1132',
                'Passengers' => ['Miguel Javier Pérez'],
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AM0185',
                        'DepCode' => 'MAD',
                        'DepDate' => $currentTripStartDate,
                        'ArrCode' => 'BCN',
                        'ArrDate' => $currentTripStartDate->modify('+1 hour 15 minute 0 seconds'),
                        'Seats' => '18F',
                        'Cabin' => 'Económica',
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'AB1133',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AM0185',
                        'DepCode' => 'MAD',
                        'DepDate' => $planStart = $startDate = $this->removeSeconds(new \DateTimeImmutable('+1 month +21 days 11:00')),
                        'ArrCode' => 'BCN',
                        'ArrDate' => $endDate = $startDate->modify('+1 hour +15 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'AB1134',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AM0285',
                        'DepCode' => 'BCN',
                        'DepDate' => $startDate = $endDate->modify('13:20'),
                        'ArrCode' => 'MAD',
                        'ArrDate' => $endDate = $startDate->modify('+1 hour +15 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'AM0389',
                        'DepCode' => 'MAD',
                        'DepDate' => $startDate = $endDate->modify('+1 hour +40 minute 0 seconds'),
                        'ArrCode' => 'SVQ',
                        'ArrDate' => $planEnd = $endDate = $startDate->modify('17:20'),
                    ],
                ],
            ],
        ];

        $hotelData = [
            [
                'Kind' => 'R',
                'ConfirmationNumber' => 'BB1040',
                'HotelName' => 'Park Hyatt, Sydney',
                'CheckInDate' => $startDate = $endDate->modify('+1 day 14:00'),
                'CheckOutDate' => $endDate = $startDate->modify('+6 days 6:00'),
                'Address' => '7 Hickson Road, The Rocks Sydney, New South Wales, Australia, 2000',
            ],
        ];

        $this->runInForkWithReconnect(function () use ($timelineAccount, $itinerariesData) {
            $this->awDataGenerator->mockAwProvider('Aeromexico', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(79311);
                    $this->SetProperty('Level', 'Clasico');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($itinerariesData),
            ]);

            $this->awDataGenerator->checkAccount($timelineAccount->getAccountid(), true, true);
        });

        $returnSegment = $this->findTripsegment($timelineAccount, 'AB1132', 'MAD', 'BCN');

        $this->awDataGenerator->createAwAccount($user, 'aviancataca', '8337123456', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 148500,
            'TotalBalance' => 148500,
            'ExpirationDate' => $this->date('+11 month 29 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'LifeMiles'],
        ]);

        $salma = $this->awDataGenerator->createAwFamilyMember($user, 'Salma', 'Pérez');

        $goldcrownAccount = $this->awDataGenerator->createAwAccount($user, 'goldcrown', '7140345678901234', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 28320,
            'ExpirationDate' => $this->date('+8 month 4 day 0 seconds'),
            'TotalBalance' => 28320,
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Platinum',
            ],
        ]);
        $this->runInForkWithReconnect(function () use ($goldcrownAccount, $hotelData) {
            $this->awDataGenerator->mockAwProvider('Goldcrown', [
                'Parse' => function () {
                    $this->SetBalance(28320);
                    $this->SetExpirationDate((new \DateTime('+8 month 4 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Level', 'Platinum');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($hotelData),
            ]);
            $this->awDataGenerator->checkAccount($goldcrownAccount->getAccountid(), true, true);
        });

        $this->awDataGenerator->createAwAccount($user, 'ichotelsgroup', 'MPerez@gmail.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 21566,
            'TotalBalance' => 21566,
            'ExpirationDate' => $this->date('+1 year 3 month 4 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Club',
            ],
        ]);

        $this->awDataGenerator->createAwAccount($user, 'aplus', 'MPerez@gmail.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 12250,
            'TotalBalance' => 12250,
            'ExpirationDate' => $this->date('+1 year 4 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Gold',
            ],
        ]);

        $user->setDefaultBooker($this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneByLogin('TravelCompanyABC'));

        $this->createBookingStuff($user, [
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-5 minutes 0 seconds'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_BOOKED_OPENED,
                'Segments' => [
                    [
                        'Dep' => 'MAD',
                        'Arr' => 'BCN',
                        'DepDate' => $planStart,
                    ],
                    [
                        'Dep' => 'BCN',
                        'Arr' => 'SVQ',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -10 days 13:45'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_PROCESSING,
                'Segments' => [
                    [
                        'Dep' => 'SVQ',
                        'Arr' => 'MAD',
                        'DepDate' => new \DateTime('-1 month'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -20 days 11:14'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_CANCELED,
                'Segments' => [
                    [
                        'Dep' => 'BCN',
                        'Arr' => 'MAD',
                        'DepDate' => new \DateTime('-1 month -8 days 0 seconds'),
                    ],
                ],
            ],
        ]);

        return ['es', $user, $returnAccount, $returnSegment];
    }

    private function createPortugeseUser(): array
    {
        // Pedro Henrique Santos
        $user = $this->createAwUser('PedroSantos', \array_merge(
            [
                'FirstName' => 'Pedro',
                'LastName' => 'Santos',
                'MidName' => 'Henrique',
                'Language' => 'pt',
                'Region' => 'PT',
            ],
            $this->ipfyUser($this->ip)));

        $this->awDataGenerator->createAwAccount($user, 'aviancaamigo', '5060708090', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 32500,
            'TotalBalance' => 32500,
            'LastBalance' => 32500 - 1000,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+8 month 12 day 0 seconds'),
            'BalanceData' => [
                (new Accountbalance())
                    ->setBalance(32500 - 1000)
                    ->setUpdatedate($this->date('-1 day 0 seconds')),
            ],
            'Properties' => [PROPERTY_KIND_STATUS => 'Amigo'],
        ]);

        $this->awDataGenerator->createAwAccount($user, 'azul', '776803456789', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 24824,
            'TotalBalance' => 24824,
            'LastChangeDate' => $this->date('-12 days 0 seconds'),
            'ExpirationDate' => $this->date('+6 month 14 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Sapphire'],
        ]);

        $timelineAccount = $returnAccount = $this->awDataGenerator->createAwAccount($user, 'golair', '25123456');

        $currentTripStartDate = $this->createDateWithRelativeFormat('-1 hour 40 minutes 0 seconds', 'Brazil/West');
        $itinerariesData = [
            [
                'Kind' => 'T',
                'RecordLocator' => 'CM2112',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'G30185',
                        'DepCode' => 'SFO',
                        'DepDate' => $startDate = $this->removeSeconds(new \DateTimeImmutable('-3 days 0 seconds')),
                        'ArrCode' => 'LAX',
                        'ArrDate' => $endDate = $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'G30189',
                        'DepCode' => 'LAX',
                        'DepDate' => $startDate = $endDate->modify('+2 hour +57 minute 0 seconds'),
                        'ArrCode' => 'SYD',
                        'ArrDate' => $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'CM2113',
                'Passengers' => ['Pedro Henrique Santos'],
                'TripSegments' => [
                    [
                        'FlightNumber' => 'G30185',
                        'DepCode' => 'GRU',
                        'DepDate' => $currentTripStartDate,
                        'ArrCode' => 'FOR',
                        'ArrDate' => $currentTripStartDate->modify('+3 hour 20 minute 0 seconds'),
                        'Seats' => '18A',
                        'Cabin' => 'Económica',
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'CM2114',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'G30185',
                        'DepCode' => 'BSB',
                        'DepDate' => $planStart = $startDate = $this->removeSeconds(new \DateTimeImmutable('+1 month +21 days 10:00')),
                        'ArrCode' => 'GRU',
                        'ArrDate' => $endDate = $startDate->modify('+1 hour +40 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'CM2115',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'G30285',
                        'DepCode' => 'GRU',
                        'DepDate' => $startDate = $endDate->modify('13:20'),
                        'ArrCode' => 'GYN',
                        'ArrDate' => $endDate = $startDate->modify('+2 hour'),
                    ],
                    [
                        'FlightNumber' => 'G30389',
                        'DepCode' => 'GYN',
                        'DepDate' => $startDate = $endDate->modify('+1 hour +15 minute 0 seconds'),
                        'ArrCode' => 'BSB',
                        'ArrDate' => $planEnd = $endDate = $startDate->modify('17:20'),
                    ],
                ],
            ],
        ];

        $hotelData = [
            [
                'Kind' => 'R',
                'ConfirmationNumber' => 'VM1040',
                'HotelName' => 'Mercure Sao Paulo Central Towers Hotel',
                'CheckInDate' => $startDate = $endDate->modify('12:00'),
                'CheckOutDate' => $endDate = $startDate->modify('+3 days 12:00'),
                'Address' => ' Rua Maestro Cardim 407, Paraiso, 01323000, SÃO PAULO, BRAZIL',
            ],
        ];

        $this->runInForkWithReconnect(function () use ($timelineAccount, $itinerariesData) {
            $this->awDataGenerator->mockAwProvider('Golair', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(8802);
                    $this->setExpirationDate((new \DateTime('+8 month +23 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Category', 'Smiles');
                    $this->SetProperty('MemberSince', '21/01/2010');
                    $this->SetProperty('MilesToExpire', 38);
                    $this->SetProperty('MilesToNextLevel', '10.000');
                    $this->SetProperty('QualifyingMiles', '3.226');
                    $this->SetProperty('NextEliteLevel', 'Silver');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($itinerariesData),
            ]);

            $this->awDataGenerator->checkAccount($timelineAccount->getAccountid(), true, true);
        });

        $returnSegment = $this->findTripsegment($timelineAccount, 'CM2113', 'GRU', 'FOR');

        $salma = $this->awDataGenerator->createAwFamilyMember($user, 'Salma', 'Santos');

        $choiceAccount = $this->awDataGenerator->createAwAccount($user, 'choice', 'PSantos', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 15098,
            'ExpirationDate' => $this->date('+8 month 4 day 0 seconds'),
            'TotalBalance' => 15098,
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Gold',
            ],
        ]);
        $this->runInForkWithReconnect(function () use ($choiceAccount, $hotelData) {
            $this->awDataGenerator->mockAwProvider('Choice', [
                'Parse' => function () {
                    $this->SetBalance(15098);
                    $this->SetExpirationDate((new \DateTime('-15 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('ChoicePrivileges', 'Gold');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($hotelData),
            ]);
            $this->awDataGenerator->checkAccount($choiceAccount->getAccountid(), true, true);
        });

        $this->awDataGenerator->createAwAccount($user, 'aplus', 'PSantos@gmail.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 21173,
            'TotalBalance' => 21173,
            'ExpirationDate' => $this->date('+1 year 4 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Platinum',
            ],
        ]);

        $user->setDefaultBooker($this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneByLogin('TravelCompanyABC'));

        $this->createBookingStuff($user, [
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-5 minutes 0 seconds'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_BOOKED_OPENED,
                'Segments' => [
                    [
                        'Dep' => 'BSB',
                        'Arr' => 'GRU',
                        'DepDate' => $planStart,
                    ],
                    [
                        'Dep' => 'GRU',
                        'Arr' => 'GYN',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -10 days 13:45'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_PROCESSING,
                'Segments' => [
                    [
                        'Dep' => 'GRU',
                        'Arr' => 'BSB',
                        'DepDate' => new \DateTime('-1 month'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -20 days 11:14'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_CANCELED,
                'Segments' => [
                    [
                        'Dep' => 'GYN',
                        'Arr' => 'BSB',
                        'DepDate' => new \DateTime('-1 month -8 days 0 seconds'),
                    ],
                ],
            ],
        ]);

        return ['pt', $user, $returnAccount, $returnSegment];
    }

    private function createFrenchUser(): array
    {
        // Nicolas Durand
        $user = $this->createAwUser('NicolasDurand', \array_merge(
            [
                'FirstName' => 'Nicolas',
                'LastName' => 'Durand',
                'Language' => 'fr',
                'Region' => 'FR',
            ],
            $this->ipfyUser($this->ip)));

        $returnAccount = $timelineAccount = $this->awDataGenerator->createAwAccount($user, 'airfrance', 'NDurand@hotmail.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 180383 - 6550,
            'TotalBalance' => 180383 - 6550,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+8 month 12 day 0 seconds'),
            'BalanceData' => [
                (new Accountbalance())
                    ->setBalance(180383 - 6550)
                    ->setUpdatedate($this->date('-1 day 0 seconds')),
            ],
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Gold',
                PROPERTY_KIND_NAME => 'Nicolas Durand',
                PROPERTY_KIND_EXPIRING_BALANCE => '180383',
                'ExpiringBalance' => 210,
                PROPERTY_KIND_NEXT_ELITE_LEVEL => 'Platinum',
            ],
        ]);

        $this->awDataGenerator->createAwAccount($user, 'aplus', 'NDurand@gmail.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 3547,
            'TotalBalance' => 3547,
            'LastChangeDate' => $this->date('-12 days 0 seconds'),
            'ExpirationDate' => $this->date('+6 month 14 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Silver'],
        ]);

        $currentTripStartDate = $this->createDateWithRelativeFormat('-48 minutes 0 seconds', 'Europe/Paris');
        $itinerariesData = [
            [
                'Kind' => 'T',
                'RecordLocator' => 'YU2112',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AF0185',
                        'DepCode' => 'SFO',
                        'DepDate' => $startDate = $this->removeSeconds(new \DateTimeImmutable('-3 days 0 seconds')),
                        'ArrCode' => 'LAX',
                        'ArrDate' => $endDate = $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'AF0189',
                        'DepCode' => 'LAX',
                        'DepDate' => $startDate = $endDate->modify('+2 hour +57 minute 0 seconds'),
                        'ArrCode' => 'SYD',
                        'ArrDate' => $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'YU2113',
                'Passengers' => ['Nicolas Durand'],
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AF0185',
                        'DepCode' => 'NCE',
                        'DepDate' => $currentTripStartDate,
                        'ArrCode' => 'CDG',
                        'ArrDate' => $currentTripStartDate->modify('+98 minutes 0 seconds'),
                        'ArrivalTerminal' => '2A',
                        'Duration' => '1 h 35 min',
                        'Seats' => '18A',
                        'Cabin' => 'Business',
                        'Aircraft' => 'Airbus A321',
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'YU2114',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AF0185',
                        'DepCode' => 'NCE',
                        'DepDate' => $planStart = $startDate = $this->removeSeconds(new \DateTimeImmutable('+1 month +21 days 12:15')),
                        'ArrCode' => 'CDG',
                        'ArrDate' => $endDate = $startDate->modify('+1 hour +45 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'YU2115',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'AF0285',
                        'DepCode' => 'CDG',
                        'DepDate' => $startDate = $endDate->modify('08:55'),
                        'ArrCode' => 'FRA',
                        'ArrDate' => $endDate = $startDate->modify('10:10'),
                    ],
                    [
                        'FlightNumber' => 'AF0389',
                        'DepCode' => 'FRA',
                        'DepDate' => $startDate = $endDate->modify('+45 minute 0 seconds'),
                        'ArrCode' => 'BOS',
                        'ArrDate' => $planEnd = $endDate = $startDate->modify('+1 day 01:20'),
                    ],
                ],
            ],
        ];

        $hotelData = [
            [
                'Kind' => 'R',
                'ConfirmationNumber' => 'TS1499',
                'HotelName' => 'Hôtel Mercure Paris Bercy Bibliothèque',
                'CheckInDate' => $startDate = $endDate->modify('15:50'),
                'CheckOutDate' => $endDate = $startDate->modify('+11 days 7:55'),
                'Address' => '6, boulevard Vincent Auriol, 75013, PARIS, FRANCE',
            ],
        ];

        $marriottAccount = $this->awDataGenerator->createAwAccount($user, 'marriott', '50123456789', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 338131,
            'TotalBalance' => 338131,
            'LastBalance' => 338131 - 4350,
            'LastChangeDate' => $this->date('-3 hours'),
            'ExpirationDate' => $this->date('+3 month 27 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Platinum Elite'],
        ]);

        $this->runInForkWithReconnect(function () use ($marriottAccount, $hotelData) {
            $this->awDataGenerator->mockAwProvider('Marriott', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(338131 - 4350);
                    $this->SetExpirationDate((new \DateTime('+3 month 27 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Level', 'Platinum Elite');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($hotelData),
            ]);

            $this->awDataGenerator->checkAccount($marriottAccount->getAccountid(), true, true);
        });

        $this->runInForkWithReconnect(function () use ($timelineAccount, $itinerariesData) {
            $this->awDataGenerator->mockAwProvider('Airfrance', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(180383);

                    $this->SetExpirationDate((new \DateTime('+8 month +23 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Status', 'Gold');
                    $this->SetProperty('Name', 'Nicolas Durand');
                    $this->SetProperty('ExpiringBalance', '180383');
                    $this->SetProperty('ExperiencePoints', '210');
                    $this->SetProperty('NextEliteLevel', 'Platinum');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($itinerariesData),
            ]);

            $this->awDataGenerator->checkAccount($timelineAccount->getAccountid(), true, true);
        });

        $returnSegment = $this->findTripsegment($timelineAccount, 'YU2113', 'NCE', 'CDG');

        $salma = $this->awDataGenerator->createAwFamilyMember($user, 'Eva', 'Green');

        $this->awDataGenerator->createAwAccount($user, 'sncf', '29010203040506070', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 22748,
            'ExpirationDate' => $this->date('+8 month 4 day 0 seconds'),
            'TotalBalance' => 22748,
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Grand Voyageur',
            ],
        ]);

        $user->setDefaultBooker($this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneByLogin('TravelCompanyABC'));

        $this->createBookingStuff($user, [
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-5 minutes 0 seconds'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_BOOKED_OPENED,
                'Segments' => [
                    [
                        'Dep' => 'NCE',
                        'Arr' => 'CDG',
                        'DepDate' => $planStart,
                    ],
                    [
                        'Dep' => 'CDG',
                        'Arr' => 'FRA',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                    [
                        'Dep' => 'FRA',
                        'Arr' => 'BOS',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -10 days 13:45'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_PROCESSING,
                'Segments' => [
                    [
                        'Dep' => 'CDG',
                        'Arr' => 'NCE',
                        'DepDate' => new \DateTime('-1 month'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -20 days 11:14'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_CANCELED,
                'Segments' => [
                    [
                        'Dep' => 'BOS',
                        'Arr' => 'FRA',
                        'DepDate' => new \DateTime('-1 month -8 days 0 seconds'),
                    ],
                ],
            ],
        ]);

        return ['fr', $user, $returnAccount, $returnSegment];
    }

    private function createDeutschUser(): array
    {
        // Linnéa Schwartz
        $user = $this->createAwUser('LSchwartz', \array_merge(
            [
                'FirstName' => 'Linnéa',
                'LastName' => 'Schwartz',
                'Language' => 'de',
                'Region' => 'DE',
            ],
            $this->ipfyUser($this->ip)));

        $returnAccount = $timelineAccount = $this->awDataGenerator->createAwAccount($user, 'lufthansa', '222012345678900', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 3952373 - 6550,
            'TotalBalance' => 3952373 - 6550,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+8 month 12 day 0 seconds'),
            'BalanceData' => [
                (new Accountbalance())
                    ->setBalance(3952373 - 6550)
                    ->setUpdatedate($this->date('-1 day 0 seconds')),
            ],
        ]);

        $this->awDataGenerator->createAwAccount($user, 'booking', 'LSchwartz@hotmail.com', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 2,
            'TotalBalance' => 2,
            'LastChangeDate' => $this->date('-12 days 0 seconds'),
            'ExpirationDate' => $this->date('+6 month 14 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Genius'],
        ]);

        $marriottAccount = $this->awDataGenerator->createAwAccount($user, 'marriott', '50912345678', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 338131,
            'TotalBalance' => 338131,
            'LastBalance' => 338131 - 2450,
            'LastChangeDate' => $this->date('-3 hours'),
            'ExpirationDate' => $this->date('+3 month 27 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Platinum Elite'],
        ]);

        $currentTripStartDate = $this->createDateWithRelativeFormat('-35 minutes 0 seconds', 'Europe/Berlin');
        $itinerariesData = [
            [
                'Kind' => 'T',
                'RecordLocator' => 'GP1354',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'LH0185',
                        'DepCode' => 'SFO',
                        'DepDate' => $startDate = $this->removeSeconds(new \DateTimeImmutable('-3 days 0 seconds')),
                        'ArrCode' => 'LAX',
                        'ArrDate' => $endDate = $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'LH0189',
                        'DepCode' => 'LAX',
                        'DepDate' => $startDate = $endDate->modify('+2 hour +57 minute 0 seconds'),
                        'ArrCode' => 'SYD',
                        'ArrDate' => $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'GP1355',
                'Passengers' => ['Linnéa Schwartz'],
                'TripSegments' => [
                    [
                        'FlightNumber' => 'LH0185',
                        'DepCode' => 'TXL',
                        'DepDate' => $currentTripStartDate,
                        'ArrCode' => 'MUC',
                        'ArrDate' => $currentTripStartDate->modify('+70 minutes 0 seconds'),
                        'ArrivalTerminal' => '2',
                        'Duration' => '1 h 10 min',
                        'Seats' => '18A',
                        'Cabin' => 'Business',
                        'Aircraft' => 'Airbus A321',
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'GP1356',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'LH0185',
                        'DepCode' => 'TXL',
                        'DepDate' => $planStart = $startDate = $this->removeSeconds(new \DateTimeImmutable('+2 month 06:30')),
                        'ArrCode' => 'MUC',
                        'ArrDate' => $endDate = $startDate->modify('+1 hour +10 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'GP1357',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'LH0285',
                        'DepCode' => 'MUC',
                        'DepDate' => $startDate = $endDate->modify('10:40'),
                        'ArrCode' => 'LHR',
                        'ArrDate' => $endDate = $startDate->modify('11:55'),
                    ],
                    [
                        'FlightNumber' => 'LH0389',
                        'DepCode' => 'LHR',
                        'DepDate' => $startDate = $endDate->modify('+1 hour'),
                        'ArrCode' => 'JFK',
                        'ArrDate' => $planEnd = $endDate = $startDate->modify('16:10'),
                    ],
                ],
            ],
        ];

        $hotelData = [
            [
                'Kind' => 'R',
                'ConfirmationNumber' => 'IO1499',
                'HotelName' => 'Munich Marriott Hotel',
                'CheckInDate' => $startDate = $endDate->modify('12:00'),
                'CheckOutDate' => $endDate = $startDate->modify('+3 days 07:40'),
                'Address' => 'Berliner Strasse 93, Munich, 80805 Germany',
            ],
        ];

        $this->runInForkWithReconnect(function () use ($marriottAccount, $hotelData) {
            $this->awDataGenerator->mockAwProvider('Marriott', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(338131 - 2450);
                    $this->SetExpirationDate((new \DateTime('+3 month 27 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Level', 'Platinum Elite');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($hotelData),
            ]);

            $this->awDataGenerator->checkAccount($marriottAccount->getAccountid(), true, true);
        });

        $this->runInForkWithReconnect(function () use ($timelineAccount, $itinerariesData) {
            $this->awDataGenerator->mockAwProvider('Lufthansa', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(3952373);

                    $this->setExpirationDate((new \DateTime('+8 month +23 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Status', 'Senator');
                    $this->SetProperty('Name', 'Linnéa Schwartz');
                    $this->SetProperty('Statusvalidityuntil', 'February 2019');
                    $this->SetProperty('StatusMiles', '98063');
                    $this->SetProperty('SelectMiles', '65051');
                    $this->SetProperty('EVouchers', '0');
                    $this->SetProperty('CustomerNumber', '550123455');
                    $this->SetProperty('NextEliteLevel', 'HON Circle');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($itinerariesData),
            ]);

            $this->awDataGenerator->checkAccount($timelineAccount->getAccountid(), true, true);
        });

        $returnSegment = $this->findTripsegment($timelineAccount, 'GP1355', 'TXL', 'MUC');

        $salma = $this->awDataGenerator->createAwFamilyMember($user, 'Diane', 'Kruger');

        $this->awDataGenerator->createAwAccount($user, 'bahn', 'LSchwartz', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 5104,
            'ExpirationDate' => $this->date('+8 month 4 day 0 seconds'),
            'TotalBalance' => 5104,
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'SubAccounts' => [
                [
                    'Code' => 'bahnStatusPoints',
                    'DisplayName' => 'Status points',
                    'Balance' => 1436,
                    'ExpirationDate' => $this->date('+5 month'),
                ],
            ],
        ]);

        $user->setDefaultBooker($this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneByLogin('TravelCompanyABC'));

        $this->createBookingStuff($user, [
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-5 minutes 0 seconds'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_BOOKED_OPENED,
                'Segments' => [
                    [
                        'Dep' => 'TXL',
                        'Arr' => 'MUC',
                        'DepDate' => $planStart,
                    ],
                    [
                        'Dep' => 'MUC',
                        'Arr' => 'LHR',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                    [
                        'Dep' => 'LHR',
                        'Arr' => 'JFK',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -10 days 13:45'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_PROCESSING,
                'Segments' => [
                    [
                        'Dep' => 'MUC',
                        'Arr' => 'TXL',
                        'DepDate' => new \DateTime('-1 month'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -20 days 11:14'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_CANCELED,
                'Segments' => [
                    [
                        'Dep' => 'CDG',
                        'Arr' => 'MUC',
                        'DepDate' => new \DateTime('-1 month -8 days 0 seconds'),
                    ],
                ],
            ],
        ]);

        return ['de', $user, $returnAccount, $returnSegment];
    }

    private function createRussianUser(): array
    {
        // Сергей Иванов
        $user = $this->createAwUser('SIvanov', \array_merge(
            [
                'FirstName' => 'Сергей',
                'LastName' => 'Иванов',
                'Language' => 'ru',
                'Region' => 'RU',
            ],
            $this->ipfyUser($this->ip)));

        $returnAccount = $this->awDataGenerator->createAwAccount($user, 'aeroflot', '19123456', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 4314,
            'TotalBalance' => 4314,
            'LastBalance' => 4314 - 1000,
            'LastChangeDate' => $this->date('-12 hour'),
            'ExpirationDate' => $this->date('+8 month 12 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Basic',
                PROPERTY_KIND_NAME => 'Сергей Иванов',
                PROPERTY_KIND_STATUS_MILES => 0,
                PROPERTY_KIND_YTD_SEGMENTS => 0,
                PROPERTY_KIND_MEMBER_SINCE => '23.01.2008',
                PROPERTY_KIND_NEXT_ELITE_LEVEL => 'Silver',
            ],
        ]);

        $timelineAccount = $this->awDataGenerator->createAwAccount($user, 's7', '5076543', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_UNCHECKED,
        ]);

        $currentTripStartDate = $this->createDateWithRelativeFormat('-1 hour -7 minutes 0 seconds', 'Asia/Yekaterinburg');
        $itinerariesData = [
            [
                'Kind' => 'T',
                'RecordLocator' => 'DF1214',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'S70185',
                        'DepCode' => 'SFO',
                        'DepDate' => $startDate = $this->removeSeconds(new \DateTimeImmutable('-3 days 0 seconds')),
                        'ArrCode' => 'LAX',
                        'ArrDate' => $endDate = $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                    [
                        'FlightNumber' => 'S70189',
                        'DepCode' => 'LAX',
                        'DepDate' => $startDate = $endDate->modify('+2 hour +57 minute 0 seconds'),
                        'ArrCode' => 'SYD',
                        'ArrDate' => $startDate->modify('+9 hour +10 minute 0 seconds'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'DF1215',
                'Passengers' => ['Сергей Иванов'],
                'AccountNumbers' => '8501785017',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'S7045',
                        'DepCode' => 'PEE',
                        'DepDate' => $currentTripStartDate,
                        'ArrCode' => 'LED',
                        'ArrDate' => $currentTripStartDate->modify('+15 minutes 0 seconds'),
                        'ArrivalTerminal' => '1',
                        'Duration' => '2 h. 15 min.',
                        'Seats' => '18A',
                        'TotalCharge' => 2700,
                        'Tax' => 1500,
                        'Status' => 'Confirmed',
                        'Aircraft' => 'Airbus Industries A319',
                        'Meal' => 'Main food',
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'DF1216',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'S70185',
                        'DepCode' => 'SVO',
                        'DepDate' => $planStart = $startDate = $this->removeSeconds(new \DateTimeImmutable('+1 month 09:25')),
                        'ArrCode' => 'RIX',
                        'ArrDate' => $endDate = $startDate->modify('11:05'),
                    ],
                    [
                        'FlightNumber' => 'S70189',
                        'DepCode' => 'RIX',
                        'DepDate' => $startDate = $endDate->modify('+1 hour +30 minute 0 seconds'),
                        'ArrCode' => 'CDG',
                        'ArrDate' => $endDate = $startDate->modify('15:30'),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'DF1217',
                'TripSegments' => [
                    [
                        'FlightNumber' => 'S70285',
                        'DepCode' => 'CDG',
                        'DepDate' => $startDate = $endDate->modify('10:10'),
                        'ArrCode' => 'BEG',
                        'ArrDate' => $endDate = $startDate->modify('12:30'),
                    ],
                    [
                        'FlightNumber' => 'S70389',
                        'DepCode' => 'BEG',
                        'DepDate' => $startDate = $endDate->modify('+25 minute 0 seconds'),
                        'ArrCode' => 'SVO',
                        'ArrDate' => $planEnd = $endDate = $startDate->modify('15:45'),
                    ],
                ],
            ],
        ];

        $marriottAccount = $this->awDataGenerator->createAwAccount($user, 'marriott', '50912345679', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 338131,
            'TotalBalance' => 338131,
            'LastBalance' => 338131 - 2450,
            'LastChangeDate' => $this->date('-3 hours'),
            'ExpirationDate' => $this->date('+3 month 27 day 0 seconds'),
            'Properties' => [PROPERTY_KIND_STATUS => 'Platinum Elite'],
        ]);

        $hotelData = [
            [
                'Kind' => 'R',
                'ConfirmationNumber' => 'WE1040',
                'HotelName' => 'Hôtel de Sèze',
                'CheckInDate' => $startDate = $endDate->modify('12:00'),
                'CheckOutDate' => $endDate = $startDate->modify('+5 days 12:00'),
                'Address' => 'Hôtel de Sèze, 16 rue de Sèze - 75009 Paris',
            ],
        ];

        $this->runInForkWithReconnect(function () use ($marriottAccount, $hotelData) {
            $this->awDataGenerator->mockAwProvider('Marriott', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(338131 - 2450);
                    $this->SetExpirationDate((new \DateTime('+3 month 27 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Level', 'Platinum Elite');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($hotelData),
            ]);

            $this->awDataGenerator->checkAccount($marriottAccount->getAccountid(), true, true);
        });

        $this->runInForkWithReconnect(function () use ($timelineAccount, $itinerariesData) {
            $this->awDataGenerator->mockAwProvider('S7', [
                'Parse' => function () {
                    /** @var \TAccountChecker $this */
                    $this->SetBalance(74696);

                    $this->setExpirationDate((new \DateTime('+8 month +23 day 0 seconds'))->getTimestamp());
                    $this->SetProperty('Status', 'Classic');

                    return true;
                },
                'ParseItineraries' => $this->awDataGenerator->createParseItinerariesMethod($itinerariesData),
            ]);

            $this->awDataGenerator->checkAccount($timelineAccount->getAccountid(), true, true);
        });

        $returnSegment = $this->findTripsegment($timelineAccount, 'DF1215', 'PEE', 'LED');

        $salma = $this->awDataGenerator->createAwFamilyMember($user, 'Светлана', 'Ходченкова');

        $this->awDataGenerator->createAwAccount($user, 'booking', 'SIvanov@yandex.ru', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 2394,
            'TotalBalance' => 2394,
            'LastChangeDate' => $this->date('-12 days 0 seconds'),
            'ExpirationDate' => $this->date('+6 month 14 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Genius',
            ],
        ]);

        $this->awDataGenerator->createAwAccount($user, 'lukoil', '80000123456789012345', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 4144,
            'ExpirationDate' => $this->date('+8 month 4 day 0 seconds'),
            'TotalBalance' => 4144,
            'UpdateDate' => $this->date('-15 day 0 seconds'),
        ]);

        $this->awDataGenerator->createAwAccount($user, 'rzd', 'SIvanov@yandex.ru', '', [
            'BackgroundCheck' => 0,
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 22916,
            'TotalBalance' => 22916,
            'ExpirationDate' => $this->date('+14 month 4 day 0 seconds'),
            'UpdateDate' => $this->date('-15 day 0 seconds'),
            'Properties' => [
                PROPERTY_KIND_STATUS => 'Basic',
            ],
        ]);

        $user->setDefaultBooker($this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneByLogin('TravelCompanyABC'));

        $this->createBookingStuff($user, [
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-5 minutes 0 seconds'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_BOOKED_OPENED,
                'Segments' => [
                    [
                        'Dep' => 'SVO',
                        'Arr' => 'CDG',
                        'DepDate' => $planStart,
                    ],
                    [
                        'Dep' => 'CDG',
                        'Arr' => 'MUC',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                    [
                        'Dep' => 'MUC',
                        'Arr' => 'SVO',
                        'DepDate' => $planStart->modify('+1 day 0 seconds'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -10 days 13:45'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_PROCESSING,
                'Segments' => [
                    [
                        'Dep' => 'SVO',
                        'Arr' => 'LHR',
                        'DepDate' => new \DateTime('-1 month'),
                    ],
                ],
            ],
            [
                'LastUpdateDate' => $lastUpdateDate = new \DateTime('-1 month -20 days 11:14'),
                'CreateDate' => $lastUpdateDate,
                'Status' => AbRequest::BOOKING_STATUS_CANCELED,
                'Segments' => [
                    [
                        'Dep' => 'DME',
                        'Arr' => 'LED',
                        'DepDate' => new \DateTime('-1 month -8 days 0 seconds'),
                    ],
                ],
            ],
        ]);

        return ['ru', $user, $returnAccount, $returnSegment];
    }

    private function createAwUser(string $suffix, array $userFields = [])
    {
        $login = "mobile+{$suffix}";

        return $this->awDataGenerator->createAwUser($login, self::PASSWORD, array_merge([
            'Groups' => [
                'Skip AuthKey check',
                'Bypass security code',
            ],
            'ItineraryCalendarCode' => \md5($login . self::class),
            'ListAdsDisabled' => true,
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
        ], $userFields));
    }

    private function findTripsegment(Account $account, string $locator, string $dep, string $arr): Tripsegment
    {
        $tripRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Trip::class);
        $tripSegmentRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);

        $trip = $tripRep->findOneBy([
            'account' => $account->getAccountid(),
            'user' => $account->getUser()->getUserid(),
            'userAgent' => null,
            'confirmationNumber' => $locator,
        ]);

        return $tripSegmentRep->findOneBy([
            'tripid' => $trip->getId(),
            'depcode' => $dep,
            'arrcode' => $arr, ]
        );
    }

    private function ipfyUser(string $ip): array
    {
        return [
            'RegistrationIP' => $ip,
            'LastLogonIP' => $ip,
        ];
    }

    private function date(string $date): \DateTime
    {
        return $this->removeSeconds(new \DateTime($date));
    }

    private function createDateWithRelativeFormat(string $relative, string $timezone): \DateTimeImmutable
    {
        $currentTripStartDate = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        $currentTripStartDate = $this->removeSeconds($currentTripStartDate);
        $currentTripStartDate = $currentTripStartDate->modify($relative);

        return new \DateTimeImmutable($currentTripStartDate->format('Y-m-d H:i'));
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     * @return \DateTime|\DateTimeImmutable
     */
    private function removeSeconds(object $dateTime): object
    {
        $timestamp = $dateTime->getTimestamp();
        $newDateTime = clone $dateTime;
        $newDateTime = $newDateTime->setTimestamp(\intval($timestamp / 60) * 60);

        return $newDateTime;
    }

    private function runInForkWithReconnect(callable $worker): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->close();

        ProcessUtils::runInFork(function () use ($worker, $connection) {
            $connection->connect();
            $worker();
        });

        $connection->connect();
    }
}
