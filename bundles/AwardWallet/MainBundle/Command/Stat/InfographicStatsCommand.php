<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iter\explodeLazy;
use function AwardWallet\MainBundle\Globals\Utils\iter\sequence;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmt;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtColumn;

class InfographicStatsCommand extends Command
{
    protected const PROVIDERS_WITHOUT_EXPIRATION_HISTORY_ROWS = [22, 12, 87, 10, 13, 18, 88, 364, 34, 92, 50, 78, 75, 186, 130, 97, 30, 85, 142, 561, 184, 138, 96, 348, 327, 127, 636];
    protected const BALANCE_LIMIT = 50 * 1000 * 1000;
    protected static $defaultName = 'aw:stat:infographics';

    protected OutputInterface $output;
    private Connection $connection;
    private Connection $replicaUnbufferedConnection;
    private ParameterRepository $parameterRepository;
    private ProviderRepository $providerRepository;
    private array $sectionsMap = [];
    private array $sectionCallsTrackerMap = [];
    private array $cachedValuesMap = [];

    public function __construct(
        Connection $connection,
        Connection $replicaUnbufferedConnection,
        ParameterRepository $parameterRepository,
        ProviderRepository $providerRepository
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
        $this->parameterRepository = $parameterRepository;
        $this->providerRepository = $providerRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Stats for inforgraphics')
            ->addArgument("startDate", InputArgument::REQUIRED, 'start date')
            ->addOption("endDate", null, InputOption::VALUE_REQUIRED, 'end date')
            ->addOption('sections', null, InputOption::VALUE_REQUIRED,
                "sections(comma separated), available sections: \n" .
                it($this->getSections())->keys()->joinToString("\n") . "\n" .
                "available meta-sections: \n" .
                it($this->getMetaSections())->keys()->joinToString("\n") . "\n"
            );
    }

    protected function getMetaSections(): array
    {
        return [
            "all" => it($this->getSections())->keys()->toArray(),
            'totals' => [
                'totalUsers',
                'totalValueTracked',
                'balances',
                'totalLPs',
                'totalAccountsCount',
                'totalCreditCardsStat',
                'totalFlights',
                'totalNights',
            ],
        ];
    }

    protected function getSections(): array
    {
        return [
            'registrations' => [$this, 'userRegistrations'],
            'totalUsers' => [$this, 'totalUsers'],
            'totalValueTracked' => [$this, 'totalValueTracked'],
            'totalLPs' => [$this, 'totalLPs'],
            'totalAccountsCount' => [$this, 'totalAccountsCount'],
            'totalCreditCardsStat' => [$this, 'totalCreditCardsStat'],
            'totalFlights' => [$this, 'totalFlights'],
            'totalNights' => [$this, 'totalNights'],
            'usersWithTimelinesCount' => [$this, 'usersWithTimelinesCount'],
            'balanceChanges' => [$this, 'balanceChanges'],
            'bigbalancechangesstat' => [$this, 'bigChangesCount'],
            'bigbalancechangeslist' => [$this, 'bigChangesList'],
            'expired' => [$this, 'expired'],
            'expiredexamples' => [$this, 'expiredExamples'],
            'providerswithoutexpiration' => [$this, 'providersWithoutExpirationRows'],
            'flights' => [$this, 'flights'],
            'checkins' => [$this, 'checkins'],
            'detectedcards' => [$this, 'detectedCards'],
            'userlocations' => [$this, 'userLocations'],
            'bookingrequests' => [$this, 'bookingRequests'],
            'topcitytargets' => [$this, 'topCityTargets'],
            'topcountrytargets' => [$this, 'topCountryTargets'],
            'balances' => [$this, 'balances'],
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $startDate = new \DateTime($input->getArgument('startDate'));
        $endDate = ($input->getOption('endDate') !== null) ?
            new \DateTime($input->getOption('endDate')) :
            new \DateTime();

        $this->sectionsMap =
            it($this->getSections())
            ->mapToClosure($this)
            ->mapIndexed(function (\Closure $sectionCallable, string $sectionId) {
                return function (...$args) use ($sectionCallable, $sectionId) {
                    $existing = $this->sectionCallsTrackerMap[$sectionId] ?? null;

                    if ([] === $existing) {
                        throw new \LogicException("Recursive call to section {$sectionId}");
                    } elseif ($existing) {
                        return;
                    }

                    $this->sectionCallsTrackerMap[$sectionId] = [];
                    $this->sectionCallsTrackerMap[$sectionId] = [$sectionCallable(...$args)];
                };
            })
            ->toArrayWithKeys();
        $availableSections =
            it($this->sectionsMap)
            ->chain($this->getMetaSections());

        if (StringUtils::isNotEmpty($input->getOption('sections'))) {
            $cliProvidedSections =
                it(explodeLazy(',', $input->getOption('sections')))
                ->mapByTrim()
                ->filterNotEmptyString()
                ->toArray();

            if (!$cliProvidedSections) {
                throw new \InvalidArgumentException('Invalid sections');
            }

            $availableSections->filterByKeyInArray($cliProvidedSections);
        }

        $this->output->writeln(
            implode("\n", [
                '',
                "##################################################################################",
                "##################################### TOTALS #####################################",
                "##################################################################################",
                '',
                '',
            ]) .
            $availableSections
                ->flatMap(fn ($sectionData) =>
                    $sectionData instanceof \Closure ?
                        [$sectionData] :
                        it($sectionData)
                        ->map(fn (string $sectionId) => $this->sectionsMap[$sectionId])
                )
                ->mapByApply($startDate, $endDate)
                ->joinToString("\n\n")
        );

        return 0;
    }

    protected function totalUsers()
    {
        $timer = $this->startTimer('Total users');
        $userCount = $this->connection->fetchOne('
            select count(*) from Usr
        ');
        $timer->stop();
        $this->log('Total Users Count', ['count' => $userCount]);
    }

    protected function totalLPs(): void
    {
        $timer = $this->startTimer('Total LPs');
        $totalLPs = $this->providerRepository->getLPCount($_SERVER['DOCUMENT_ROOT'] . '/..');
        $timer->stop();
        $this->log('Total LPs', ['count' => $totalLPs]);
    }

    protected function totalFlights(): void
    {
        $flightsCount = $this->flightsCount($this->replicaUnbufferedConnection, null, null);
        $this->sectionsMap['usersWithTimelinesCount']();
        $this->log('Total Flights', [
            'count' => $flightsCount,
            'per_user' => $flightsCount / $this->cachedValuesMap['users_with_timelines'],
        ]);
    }

    protected function totalNights(): void
    {
        $hotelNightsCount = $this->hotelsNightsCount($this->replicaUnbufferedConnection, null, null);
        $this->sectionsMap['usersWithTimelinesCount']();
        $this->log('Total Hotel Nights', [
            'count' => $hotelNightsCount,
            'per_user' => $hotelNightsCount / $this->cachedValuesMap['users_with_timelines'],
        ]);
    }

    protected function usersWithTimelinesCount(): void
    {
        $timer = $this->startTimer('Users with timelines');
        $count = $this->replicaUnbufferedConnection->fetchAllNumeric("
            select count(*) from Usr u
            where exists(
                select 1
                from Trip t
                join TripSegment ts on
                    ts.TripID = t.TripID
                left join Provider p on
                    t.ProviderID = p.ProviderID and
                    p.State = :testKind
                where
                    t.UserID = u.UserID and
                    t.Hidden = 0 and
                    t.Cancelled = 0 and
                    p.ProviderID is null and
                    ts.Hidden = 0
            )
            or exists(
                select 1
                from Reservation r
                left join Provider p on 
                    p.ProviderID = r.ProviderID and 
                    p.State = :testKind
                where
                    r.UserID = u.UserID and
                    p.ProviderID is null and 
                    r.Hidden = 0 and
                    r.Cancelled = 0
            )",
            [':testKind' => PROVIDER_TEST]
        )[0][0];
        $timer->stop();
        $this->log('Users with Timelines', (int) $count, 'users_with_timelines');
    }

    protected function totalAccountsCount(): void
    {
        $timer = $this->startTimer('Total accounts count');
        $totalAccountsCount = $this->replicaUnbufferedConnection->fetchAssociative('
            select 
                SUM(1) as TotalCount,
                SUM(a.IsArchived) as ArchivedCount, 
                count(distinct a.UserID) as TotalUsersWithAccountsCount,
                count(distinct (if(a.IsArchived = 0, a.UserID, null))) as TotalUsersWithNonArchivedAccountsCount
            from Account a
        ');
        $timer->stop();
        $totalAccountsCount['AccountsPerUser'] = round($totalAccountsCount['TotalCount'] / $totalAccountsCount['TotalUsersWithAccountsCount'], 2);
        $totalAccountsCount['NonArchivedAccountsPerUser'] = round(($totalAccountsCount['TotalCount'] - $totalAccountsCount['ArchivedCount']) / $totalAccountsCount['TotalUsersWithNonArchivedAccountsCount'], 2);
        $this->log('Total Accounts Count', $totalAccountsCount);
    }

    protected function totalCreditCardsStat(): void
    {
        $timer = $this->startTimer('Total credit cards stat');
        $totalCreditCards = $this->replicaUnbufferedConnection->fetchAssociative('
            select 
                count(distinct if(!ucc.IsClosed, ucc.UserID, null)) as TotalUsersWithoutClosedCC,
                count(distinct ucc.UserID) as TotalUsersWithCC,
                count(*) as TotalCCCount,
                sum(!ucc.IsClosed) as CCNotClosed,
                sum(ucc.IsClosed) as CCClosed,
                sum(if(!ucc.IsClosed, ucc.SourcePlace = 1, 0)) as SourcePlaceAccount,
                sum(if(!ucc.IsClosed, ucc.SourcePlace = 2, 0)) as SourcePlaceSubAccount,
                sum(if(!ucc.IsClosed, ucc.SourcePlace = 3, 0)) as SourcePlaceAccountHistory,
                sum(if(!ucc.IsClosed, ucc.SourcePlace = 4, 0)) as SourcePlaceDetectedCards,
                sum(if(!ucc.IsClosed, ucc.SourcePlace = 5, 0)) as SourcePlaceQSTransaction,
                sum(if(!ucc.IsClosed, ucc.SourcePlace = 6, 0)) as SourcePlaceEmail
            from UserCreditCard ucc
        ');
        $timer->stop();
        $totalCreditCards['NotClosedCCPerUser'] = round($totalCreditCards['CCNotClosed'] / $totalCreditCards['TotalUsersWithoutClosedCC'], 2);
        $this->log('Total Credit Cards Stat', $totalCreditCards);
    }

    protected function totalValueTracked(): void
    {
        $calcTotal = function (?int $kind = null) {
            $stmt = $this->replicaUnbufferedConnection->executeQuery("
                SELECT 
                SUM(if(a.Balance between - :balanceLimit AND :balanceLimit, a.Balance, 0)) AS Balance
                FROM Account a
                " . (isset($kind) ? "
                    left join Provider p on a.ProviderID = p.ProviderID
                    where coalesce(p.Kind, a.Kind) = :kind" : ""),
                \array_merge(
                    [':balanceLimit' => ParameterRepository::ENORMOUS_BALANCE_LIMIT],
                    isset($kind) ? [':kind' => $kind] : [],
                ),
            );
            $total = $stmt->fetchAllAssociative()[0]['Balance'];
            $stmt = $this->replicaUnbufferedConnection->executeQuery("
                SELECT 
                SUM(if(sa.Balance between - :balanceLimit AND :balanceLimit, sa.Balance, 0)) AS Balance 
                FROM SubAccount sa
                " . (isset($kind) ? "
                    join Account a on a.AccountID = sa.AccountID 
                    join Provider p on p.ProviderID = a.ProviderID
                    where p.Kind = :kind" : ""),
                \array_merge(
                    [':balanceLimit' => ParameterRepository::ENORMOUS_BALANCE_LIMIT],
                    isset($kind) ? [':kind' => $kind] : []
                ),
            );
            $total += $stmt->fetchAllAssociative()[0]['Balance'];
            $total = round($total);

            return $total;
        };
        $data = [];

        foreach ([
            [null, 'Total'],
            [PROVIDER_KIND_CREDITCARD, 'Credit Card'],
            [PROVIDER_KIND_HOTEL, 'Hotel'],
            [PROVIDER_KIND_AIRLINE, 'Airline'],
            [PROVIDER_KIND_CAR_RENTAL, 'Rental'],
            [PROVIDER_KIND_SHOPPING, 'Shopping'],
            [PROVIDER_KIND_DINING, 'Dining'],
        ] as [$kind, $title]) {
            $timer = $this->startTimer("Total value tracked ({$title})");
            $total = $calcTotal($kind);
            $timer->stop();

            $totalBillion = \bcdiv($total, 1000 * 1000 * 1000, 1);
            $moneyBillion = $totalBillion * 20 / 1000;

            $data[$title] = [
                'total_raw' => $total,
                'total_billions' => "{$totalBillion} Billions",
                'money_billions' => "{$moneyBillion} Billions",
                'total_formatted' => self::formatNumber($total),
                'total_money_formatted' => self::formatNumber($total * 20 / 1000),
            ];
        }

        $this->log('Total Value Tracked (Enormous balances filtered)', $data);
    }

    protected static function formatNumber(float $number): string
    {
        $number = \round($number);
        $suffix = '';

        if ($number >= 1_000_000_000) {
            $number = \bcdiv($number, 1_000_000_000, 2);
            $suffix = 'Billion(s)';
        } elseif ($number >= 1_000_000) {
            $number = \bcdiv($number, 1_000_000, 2);
            $suffix = 'Million(s)';
        } elseif ($number >= 1_000) {
            $number = \bcdiv($number, 1_000, 2);
            $suffix = 'Thousand(s)';
        } else {
            return \number_format($number, 2, '.', ' ');
        }

        return \number_format($number, 2, '.', ' ') . ' ' . $suffix;
    }

    protected function userRegistrations(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $regularUsers = $unbufferedConnectionRU
            ->executeQuery(
                'select count(*) as cnt from Usr u where (u.CreationDateTime between :startDate and :endDate) and u.AccountLevel <> 3',
                [':startDate' => $startDate->format('Y-m-d H:i:s'), ':endDate' => $endDate->format('Y-m-d H:i:s')],
                [':startDate' => \PDO::PARAM_STR, ':endDate' => \PDO::PARAM_STR]
            )->fetchColumn();

        $businessUsers = $unbufferedConnectionRU
            ->executeQuery(
                'select count(*) as cnt from Usr u where u.AccountLevel = 3 and (u.CreationDateTime between :startDate and :endDate)',
                [':startDate' => $startDate->format('Y-m-d H:i:s'), ':endDate' => $endDate->format('Y-m-d H:i:s')],
                [':startDate' => \PDO::PARAM_STR, ':endDate' => \PDO::PARAM_STR]
            )->fetchColumn();

        return $this->log('Registrations', ['regular' => $regularUsers, 'business' => $businessUsers]);

        return $total;
    }

    protected function bigChangesList(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        $connection = $this->connection;

        $stopPropertyIds = stmtColumn($connection->executeQuery("select ProviderPropertyID from ProviderProperty where Name = 'currency' or Name = 'currencytype'"))
            ->mapToInt()
            ->toArray();

        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $amexHistoryChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Amex BIG (LIST) balance changes (AccountHistory) query');
            $stmt = $unbufferedConnectionRU->executeQuery(/** @lang MySQL */ "
                    (
                        select
                           ah.AccountID,
                           a.ProviderID,
                           ah.Miles,
                           ah.PostingDate,
                           6 as Kind
                        from Account a
                        left join AccountProperty ap on 
                            ap.ProviderPropertyID in (:stopPropertyIds) and
                            ap.AccountID = a.AccountID and
                            ap.SubAccountID is null
                        join AccountHistory ah on
                            ah.AccountID = a.AccountID and
                            ah.SubAccountID is null and
                            (ah.PostingDate between :startDate and :endDate)
                        where
                            abs(ah.Miles) > 50 * 1000 * 1000 and
                            a.ProviderID = 84 and # only amex
                            a.UpdateDate > :startDate and
                            ap.AccountPropertyID is null
                    )
            ",
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };
        $othersHistoryChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Other BIG (LIST) balance changes (AccountHistory) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select
                    a.AccountID,
                    p.ProviderID,
                    ah.Miles,
                    ah.PostingDate,
                    p.Kind as Kind
                from Provider p
                join Account a on
                    a.ProviderID = p.ProviderID and
                    a.UpdateDate > :startDate
                left join AccountProperty ap on
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                join AccountHistory ah on
                    ah.AccountID = a.AccountID and
                    ah.SubAccountID is null and
                    (ah.PostingDate between :startDate and :endDate)
                where
                    p.Kind in (1, 2, 6) and # airline, hotel, credit card
                    p.State > 0 and
                    p.CanCheckHistory = 1 and
                    a.ProviderID not in (84, 7, 26) and # exclude amex, delta, united
                    ap.AccountPropertyID is null and # exclude rows with stopProperties
                    (
                        abs(ah.Miles) > 50 * 1000 * 1000 and 
                        not(
                            # marriott
                            p.ProviderID = 17 and
                            position(\'%"s:4:"Type";s:15:"Points Transfer"%\' in ah.Info) > 0
                        )
                    )
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        $stats = [
            'air' => [],
            'hotel' => [],
            'credit' => [],
        ];
        $kinds = [1 => 'air', 2 => 'hotel', 6 => 'credit'];

        foreach (\iter\chain($amexHistoryChanges(), $othersHistoryChanges()) as $row) {
            $kind = $kinds[$row['Kind']];
            $stats[$kind][] = $row;
        }

        // delta, united, custom accounts
        $balanceChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Other BIG balance changes (AccountBalance) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                  ab.AccountID,
                  ab.Balance,
                  coalesce(p.Kind, a.Kind) as Kind,
                  p.ProviderID,
                  ab.UpdateDate,
                  null as SubAccountID
                from Account a
                left join Provider p on 
                    a.ProviderID = p.ProviderID and 
                    p.Kind in (1, 2, 6) and # airline, hotel, credit card #
                    p.State > 0 and
                    (
                        p.ProviderID in (7, 26) or # include delta, united
                        p.CanCheckHistory = 0
                    )
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                join AccountBalance ab on 
                    a.AccountID = ab.AccountID and
                    ab.SubAccountID is null and
                    (ab.UpdateDate between :startDate and :endDate)
                where 
                    a.UpdateDate > :startDate and
                    (
                        p.ProviderID is not null or 
                        (
                            a.ProviderID is null and
                            a.Kind in (1, 2, 6)
                        )
                    )
                order by 
                  ab.AccountID,
                  ab.UpdateDate
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        foreach (
            it($balanceChanges())
            ->onNthAndLast(25000, function ($n) {
                $this->output->writeln("Processed {$n} changesets...");
            })
            ->sliding(2) as [$lastBalance, $balance]
        ) {
            if (
                ($balance['AccountID'] !== $lastBalance['AccountID'])
                || ($balance['SubAccountID'] !== $lastBalance['SubAccountID'])
            ) {
                continue;
            }

            $diff = (int) (\round($balance['Balance']) - \round($lastBalance['Balance']));

            if (\abs($diff) >= 50 * 1000 * 1000) {
                $kind = $kinds[$balance['Kind']];
                $stats[$kind][] = $balance;
            }
        }

        $connection->close();
        $unbufferedConnectionRU->close();

        return $this->log('BIG (LIST) Balance changes', $stats);
    }

    protected function bigChangesCount(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        $connection = $this->connection;

        $stopPropertyIds = stmtColumn($connection->executeQuery("select ProviderPropertyID from ProviderProperty where Name = 'currency' or Name = 'currencytype'"))
            ->mapToInt()
            ->toArray();

        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $amexHistoryChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Amex BIG balance changes (AccountHistory) query');
            $stmt = $unbufferedConnectionRU->executeQuery(/** @lang MySQL */ "
                    (
                        select
                           count(*) as Count,
                           6 as Kind
                        from Account a
                        left join AccountProperty ap on 
                            ap.ProviderPropertyID in (:stopPropertyIds) and
                            ap.AccountID = a.AccountID and
                            ap.SubAccountID is null
                        join AccountHistory ah on
                            ah.AccountID = a.AccountID and
                            ah.SubAccountID is null and
                            (ah.PostingDate between :startDate and :endDate)
                        where
                            abs(ah.Miles) > 50 * 1000 * 1000 and
                            a.ProviderID = 84 and # only amex
                            a.UpdateDate > :startDate and
                            ap.AccountPropertyID is null
                    )
            ",
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };
        $othersHistoryChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Other BIG balance changes (AccountHistory) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                    count(*) as Count,
                    p.Kind as Kind
                from Provider p
                join Account a on
                    a.ProviderID = p.ProviderID and
                    a.UpdateDate > :startDate
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                join AccountHistory ah on
                    ah.AccountID = a.AccountID and
                    ah.SubAccountID is null and
                    (ah.PostingDate between :startDate and :endDate)
                where
                    p.Kind in (1, 2, 6) and # airline, hotel, credit card
                    p.State > 0 and
                    p.CanCheckHistory = 1 and
                    a.ProviderID not in (84, 7, 26) and # exclude amex, delta, united 
                    ap.AccountPropertyID is null and # exclude rows with stopProperties
                    (
                        abs(ah.Miles) > 50 * 1000 * 1000 and 
                        not(
                            # marriott
                            p.ProviderID = 17 and 
                            position(\'%"s:4:"Type";s:15:"Points Transfer"%\' in ah.Info) > 0
                        )
                    )
                group by p.Kind
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        $stats = [
            'air' => 0,
            'hotel' => 0,
            'credit' => 0,
        ];
        $kinds = [1 => 'air', 2 => 'hotel', 6 => 'credit'];

        foreach (\iter\chain($amexHistoryChanges(), $othersHistoryChanges()) as $count) {
            $kind = $kinds[$count['Kind']];
            $stats[$kind] += $count['Count'];
        }

        // delta, united, custom accounts
        $balanceChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Other BIG balance changes (AccountBalance) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                  ab.AccountID,
                  ab.Balance,
                  coalesce(p.Kind, a.Kind) as Kind,
                  null as SubAccountID
                from Account a
                left join Provider p on 
                    a.ProviderID = p.ProviderID and 
                    p.Kind in (1, 2, 6) and # airline, hotel, credit card #
                    p.State > 0 and
                    (
                        p.ProviderID in (7, 26) or # include delta, united
                        p.CanCheckHistory = 0
                    )
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                join AccountBalance ab on 
                    a.AccountID = ab.AccountID and
                    ab.SubAccountID is null and
                    (ab.UpdateDate between :startDate and :endDate)
                where 
                    a.UpdateDate > :startDate and
                    (
                        p.ProviderID is not null or 
                        (
                            a.ProviderID is null and
                            a.Kind in (1, 2, 6)
                        )
                    )
                order by 
                  ab.AccountID,
                  ab.UpdateDate
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        foreach (
            it($balanceChanges())
            ->onNthAndLast(25000, function ($n) {
                $this->output->writeln("Processed {$n} changesets...");
            })
            ->sliding(2) as [$lastBalance, $balance]
        ) {
            if (
                ($balance['AccountID'] !== $lastBalance['AccountID'])
                || ($balance['SubAccountID'] !== $lastBalance['SubAccountID'])
            ) {
                continue;
            }

            $diff = (int) (\round($balance['Balance']) - \round($lastBalance['Balance']));

            if (\abs($diff) >= 50 * 1000 * 1000) {
                $kind = $kinds[$balance['Kind']];
                ++$stats[$kind];
            }
        }

        $connection->close();
        $unbufferedConnectionRU->close();

        return $this->log('BIG Balance changes', $stats);
    }

    protected function balanceChanges(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        $statsByProvider = [];
        $connection = $this->connection;

        $stopPropertyIds = stmtColumn($connection->executeQuery("select ProviderPropertyID from ProviderProperty where Name = 'currency' or Name = 'currencytype'"))
            ->mapToInt()
            ->toArray();

        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $creditCardsHistorySubAccounts = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Credit cards balance (Subaccount) changes (AccountHistory) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                    sum(if(ah.Miles > 0 and ah.Miles < :balanceLimit, cast(ah.Miles as unsigned), 0)) as Earned,
                    sum(if(ah.Miles < 0 and ah.Miles > - :balanceLimit, cast(abs(ah.Miles) as unsigned), 0)) as Redeemed,
                    p.Code,
                    6 as Kind
                from Provider p
                join Account a on
                    a.ProviderID = p.ProviderID and
                    a.UpdateDate > :startDate
                join SubAccount sa on 
                    a.AccountID = sa.AccountID
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID = sa.SubAccountID and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                join AccountHistory ah on
                    ah.AccountID = sa.AccountID and
                    ah.SubAccountID = sa.SubAccountID and
                    (ah.PostingDate between :startDate and :endDate)
                where 
                    p.Kind = 6 and # airline, hotel, credit card
                    p.State > 0 and
                    ap.AccountPropertyID is null # exclude rows with stopProperties
                group by p.Code
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                    ':balanceLimit' => ParameterRepository::ENORMOUS_BALANCE_LIMIT,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                    ':balanceLimit' => \PDO::PARAM_INT,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        $creditCardsHistoryMainAccounts = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Credit cards balance changes (Main) (AccountHistory) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                    sum(if(ah.Miles > 0 and ah.Miles < :balanceLimit, cast(ah.Miles as unsigned), 0)) as Earned,
                    sum(if(ah.Miles < 0 and ah.Miles > - :balanceLimit, cast(abs(ah.Miles) as unsigned), 0)) as Redeemed,
                    p.Code,
                    6 as Kind
                from Provider p
                join Account a on
                    a.ProviderID = p.ProviderID and
                    a.UpdateDate > :startDate
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                join AccountHistory ah on
                    ah.AccountID = a.AccountID and
                    ah.SubAccountID is null and
                    (ah.PostingDate between :startDate and :endDate)
                where 
                    p.Kind = 6 and # airline, hotel, credit card
                    p.State > 0 and
                    ap.AccountPropertyID is null # exclude rows with stopProperties
                group by p.Code
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                    ':balanceLimit' => ParameterRepository::ENORMOUS_BALANCE_LIMIT,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                    ':balanceLimit' => \PDO::PARAM_INT,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        $othersHistoryChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Other balance changes (AccountHistory) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                    sum(
                        if(
                            ah.Miles > 0 and ah.Miles < :balanceLimit and
                            not(
                                # marriott
                                p.ProviderID = 17 and 
                                position(\'%"s:4:"Type";s:15:"Points Transfer"%\' in ah.Info) > 0
                            ), 
                            cast(ah.Miles as unsigned), 
                            0
                        )
                    ) as Earned,
                    sum(
                        if(
                            ah.Miles < 0 and ah.Miles > - :balanceLimit and 
                            not(
                                # marriott
                                p.ProviderID = 17 and 
                                position(\'%"s:4:"Type";s:15:"Points Transfer"%\' in ah.Info) > 0
                            ),
                            cast(abs(ah.Miles) as unsigned), 
                            0
                        )
                    ) as Redeemed,
                    p.Code,
                    p.Kind
                from Provider p
                join Account a on
                    a.ProviderID = p.ProviderID and
                    a.UpdateDate > :startDate
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                join AccountHistory ah on
                    ah.AccountID = a.AccountID and
                    ah.SubAccountID is null and
                    (ah.PostingDate between :startDate and :endDate)
                where 
                    p.Kind in (1, 2) and # airline, hotel
                    p.State > 0 and
                    ap.AccountPropertyID is null # exclude rows with stopProperties
                group by p.Kind, p.Code
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                    ':balanceLimit' => ParameterRepository::ENORMOUS_BALANCE_LIMIT,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                    ':balanceLimit' => \PDO::PARAM_INT,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        $kinds = [1 => 'air', 2 => 'hotel', 6 => 'credit'];

        foreach (\iter\chain(
            $creditCardsHistorySubAccounts(),
            $creditCardsHistoryMainAccounts(),
            $othersHistoryChanges()
        ) as $change) {
            $kindName = $kinds[$change['Kind']];
            $providerCode = $change['Code'];

            $statsByProvider['history'][$kindName][$providerCode]['earned'] =
                ($statsByProvider['history'][$kindName][$providerCode]['earned'] ?? 0) + $change['Earned'];

            $statsByProvider['total'][$kindName][$providerCode]['earned'] =
                ($statsByProvider['total'][$kindName][$providerCode]['earned'] ?? 0) + $change['Earned'];

            $statsByProvider['history'][$kindName][$providerCode]['redeemed'] =
                ($statsByProvider['history'][$kindName][$providerCode]['redeemed'] ?? 0) + $change['Redeemed'];

            $statsByProvider['total'][$kindName][$providerCode]['redeemed'] =
                ($statsByProvider['total'][$kindName][$providerCode]['redeemed'] ?? 0) + $change['Redeemed'];
        }

        $balanceCreditSubAccountChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Credit cards balance (SubAccount) changes (AccountBalance) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                  concat(\'sa.\', ab.SubAccountID) as AccountID,
                  ab.Balance,
                  6 as Kind,
                  p.Code
                from Account a
                join Provider p on 
                    a.ProviderID = p.ProviderID and 
                    p.Kind = 6 and
                    p.State > 0
                join SubAccount sa on
                    a.AccountID = sa.AccountID
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID = sa.SubAccountID and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                left join AccountHistory ah on
                    ah.AccountID = a.AccountID and
                    ah.SubAccountID = sa.SubAccountID
                join AccountBalance ab on 
                    a.AccountID = ab.AccountID and
                    ab.SubAccountID = sa.SubAccountID and
                    (ab.UpdateDate between :startDate and :endDate)
                where 
                    a.UpdateDate > :startDate and
                    ap.AccountPropertyID is null and
                    ah.AccountID is null
                order by 
                  ab.SubAccountID,
                  ab.UpdateDate
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        $balanceCreditMainAccountChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Credit cards balance (Main) changes (AccountBalance) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                  concat(\'a.\', a.AccountID) as AccountID,
                  ab.Balance,
                  6 as Kind,
                  p.Code
                from Account a
                join Provider p on 
                    a.ProviderID = p.ProviderID and 
                    p.Kind = 6 and
                    p.State > 0
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                left join AccountHistory ah on
                    ah.AccountID = a.AccountID and
                    ah.SubAccountID is null
                join AccountBalance ab on 
                    a.AccountID = ab.AccountID and
                    ab.SubAccountID is null and
                    (ab.UpdateDate between :startDate and :endDate)
                where 
                    a.UpdateDate > :startDate and
                    ap.AccountPropertyID is null and
                    ah.AccountID is null
                order by 
                  ab.AccountID,
                  ab.UpdateDate
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        // delta, united, custom accounts
        $balanceOtherChanges = function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Other balance changes (AccountBalance) query');
            $stmt = $unbufferedConnectionRU->executeQuery('
                select 
                  ab.AccountID,
                  ab.Balance,
                  coalesce(p.Kind, a.Kind) as Kind,
                  p.Code
                from Account a
                left join Provider p on 
                    a.ProviderID = p.ProviderID and 
                    p.Kind in (1, 2) and # airline, hotel, credit card #
                    p.State > 0
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                left join AccountHistory ah on
                    ah.AccountID = a.AccountID and
                    ah.SubAccountID is null
                join AccountBalance ab on 
                    a.AccountID = ab.AccountID and
                    ab.SubAccountID is null and
                    (ab.UpdateDate between :startDate and :endDate)
                where 
                    a.UpdateDate > :startDate and
                    (
                        p.ProviderID is not null or 
                        (
                            a.ProviderID is null and
                            a.Kind in (1, 2)
                        )
                    ) and
                    ap.AccountPropertyID is null and # exclude rows with stopProperties
                    ah.AccountID is null
                order by 
                  ab.AccountID,
                  ab.UpdateDate
        ',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        foreach (
            it(\iter\chain(
                $balanceCreditSubAccountChanges(),
                $balanceCreditMainAccountChanges(),
                $balanceOtherChanges()
            ))
            ->onNthAndLast(25000, function ($n) {
                $this->output->writeln("Processed {$n} changesets...");
            })
            ->groupAdjacentByLazy(function (array $row1, array $row2) { return $row1['AccountID'] <=> $row2['AccountID']; })
            ->flatMap(function (IteratorFluent $group) { return $group->sliding(2); }) as [$lastChange, $change]
        ) {
            $diff = (int) (\round($change['Balance']) - \round($lastChange['Balance']));

            if (\abs($diff) >= ParameterRepository::ENORMOUS_BALANCE_LIMIT) {
                continue;
            }

            $kindName = $kinds[$change['Kind']];
            $providerCode = $change['Code'] ?? 'CUSTOM';

            if ($diff > 0) {
                $statsByProvider['balance'][$kindName][$providerCode]['earned'] =
                    ($statsByProvider['balance'][$kindName][$providerCode]['earned'] ?? 0) + $diff;

                $statsByProvider['total'][$kindName][$providerCode]['earned'] =
                    ($statsByProvider['total'][$kindName][$providerCode]['earned'] ?? 0) + $diff;
            } elseif ($diff < 0) {
                $statsByProvider['balance'][$kindName][$providerCode]['redeemed'] =
                    ($statsByProvider['balance'][$kindName][$providerCode]['redeemed'] ?? 0) + \abs($diff);

                $statsByProvider['total'][$kindName][$providerCode]['redeemed'] =
                    ($statsByProvider['total'][$kindName][$providerCode]['redeemed'] ?? 0) + \abs($diff);
            }
        }

        $connection->close();
        $unbufferedConnectionRU->close();

        foreach (['total', 'history', 'balance'] as $type) {
            foreach ($statsByProvider[$type] as $kind => $providers) {
                $statsByProvider[$type][$kind] =
                    it($providers)
                    ->uasort(function (array $p1, array $p2) {
                        return ($p2['earned'] ?? 0) <=> ($p1['earned'] ?? 0);
                    })
                    ->take(100)
                    ->toArrayWithKeys();
            }
        }

        $finalStats = [
            'byProvider' => $statsByProvider,
        ];

        foreach (['total', 'history', 'balance'] as $type) {
            foreach ($statsByProvider[$type] as $kind => $providers) {
                foreach (['earned', 'redeemed'] as $changeName) {
                    $finalStats['byKind'][$type][$kind][$changeName] =
                        ($finalStats['byKind'][$type][$kind][$changeName] ?? 0)
                        +
                        it($providers)
                        ->map(function (array $provider) use ($changeName) { return $provider[$changeName] ?? 0; })
                        ->sum();
                }
            }
        }

        return $this->log('Balance changes', $finalStats);
    }

    protected function expired(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $stopPropertyIds = stmtColumn($unbufferedConnectionRU->executeQuery("select ProviderPropertyID from ProviderProperty where Name = 'currency' or Name = 'currencytype'"))
            ->mapToInt()
            ->toArray();

        $accountsToKindMap = call(function () use ($stopPropertyIds, $unbufferedConnectionRU, $startDate, $endDate) {
            $timer = $this->startTimer('Other expired miles (AccountHistory) query');
            $stmt = $unbufferedConnectionRU->executeQuery(/** @lang MySQL */ '
                select 
                    a.AccountID,
                    p.Kind
                from Provider p
                join Account a on
                    a.ProviderID = p.ProviderID and
                    a.UpdateDate >= :startDate
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                where 
                    p.Kind in (1, 2, 6) and # airline, hotel, credit card
                    p.State > 0 and
                    p.CanCheckHistory = 1 and
                    a.ProviderID not in (7, 26) and # exclude delta, united 
                    ap.AccountPropertyID is null # exclude rows with stopProperties',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        });

        $accountHistoryRowsProvider = function (\DateTimeInterface $startDate, \DateTimeInterface $endDate) use ($unbufferedConnectionRU) {
            $timer = $this->startTimer(
                'Other expired miles (AccountHistory) query from ' .
                $startDate->format('Y-m-d') .
                ' to ' .
                $endDate->format('Y-m-d')
            );

            $stmt = $unbufferedConnectionRU->executeQuery(/** @lang MySQL */ '                
                select
                    ah.AccountID,
                    ah.SubAccountID,
                    ah.Miles,
                    ah.Description
                from AccountHistory ah
                where 
                    ah.PostingDate >= :startDate and
                    ah.PostingDate < :endDate',
                [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                ],
                [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                ]);
            $timer->stop();

            yield from stmt($stmt);
        };

        $stats = new ExpiredStats();

        foreach (
            it(sequence(
                \DateTimeImmutable::createFromMutable($startDate),
                function (\DateTimeImmutable $date) { return $date->modify('+5 day'); }
            ))
            ->takeWhile(function (\DateTimeImmutable $curDate) use ($endDate) { return $curDate < $endDate; })
            ->append($endDate)
            ->sliding(2)
            ->flatMapVariadic($accountHistoryRowsProvider)
            ->onNthMillisAndLast(10 * 1000, function ($millis, $n) {
                $this->output->writeln("{$n} row(s) processed [" . (int) ($millis / 1000) . "s]");
            }) as [$accountId, $subAccountId, $miles, $description]
        ) {
            if (
                !isset($accountsToKindMap[$accountId])
                || isset($subAccountId)
                || ($miles >= 0)
                || (\stripos($description, 'expir') === false)
            ) {
                continue;
            }

            switch ($accountsToKindMap[$accountId]) {
                case 1: $stats->air += (int) $miles;

                    break;

                case 2: $stats->hotel += (int) $miles;

                    break;

                case 6: $stats->credit += (int) $miles;

                    break;
            }
        }

        $unbufferedConnectionRU->close();

        return $this->log('Expired', $stats);
    }

    protected function providersWithoutExpirationRows(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();

        $stopPropertyIds = stmtColumn($unbufferedConnectionRU->executeQuery("select ProviderPropertyID from ProviderProperty where Name = 'currency' or Name = 'currencytype'"))
            ->mapToInt()
            ->toArray();

        $stmt = $unbufferedConnectionRU->executeQuery(/** @lang MySQL */ '
            select 
                p.ProviderID,
                p.Code,
                count(ah.UUID) as ExpirationHistoryRows,
                p.Accounts as Popularity
            from Provider p
            left join Account a on
                a.ProviderID = p.ProviderID and
                a.UpdateDate > :startDate
            left join AccountProperty ap on 
                ap.AccountID = a.AccountID and
                ap.SubAccountID is null and
                ap.ProviderPropertyID in (:stopPropertyIds)
            left join AccountHistory ah on
                ah.AccountID = a.AccountID and
                ah.SubAccountID is null and
                (ah.PostingDate between :startDate and :endDate) and 
                ah.Miles < 0 and
                position(\'expir\' in ah.Description) > 0
            where 
                p.ProviderID not in (84, 7, 26) and # exclude amex, delta, united
                p.Kind in (1, 2, 6) and # airline, hotel, credit card
                ap.AccountPropertyID is null and # exclude rows with stopProperties

                p.CanCheckHistory = 1
            group by p.ProviderID
            having ExpirationHistoryRows = 0
            order by popularity desc
            limit 100
            ',
            [
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
                ':stopPropertyIds' => $stopPropertyIds,
            ],
            [
                ':startDate' => \PDO::PARAM_STR,
                ':endDate' => \PDO::PARAM_STR,
                ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
            ]
        );

        $unbufferedConnectionRU->close();

        return $this->log('Providers without expiration', $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    protected function expiredExamples(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();

        $stopPropertyIds = stmtColumn($unbufferedConnectionRU->executeQuery("select ProviderPropertyID from ProviderProperty where Name = 'currency' or Name = 'currencytype'"))
            ->mapToInt()
            ->toArray();

        $othersExpirations = function () use ($unbufferedConnectionRU, $startDate, $endDate, $stopPropertyIds) {
            $providers = $unbufferedConnectionRU->executeQuery('
                select 
                   ProviderID, 
                   Code 
                from Provider 
                where 
                   CanCheckHistory = 1 and
                   ProviderID not in (?) and 
                   ProviderID not in (84, 7, 26) and # exclude amex, delta, united
                   Accounts > 0 and 
                   State > 0
                order by Accounts desc
                ', [self::PROVIDERS_WITHOUT_EXPIRATION_HISTORY_ROWS], [Connection::PARAM_STR_ARRAY]);

            $counter = 0;

            foreach (
                stmt($providers)
                ->collect()
                ->increment($counter)
                ->fromPairs() as $providerId => $providerCode
            ) {
                $timer = $this->startTimer("# {$counter} # Expired miles exapmles ($providerCode) (AccountHistory) query");
                $stmt = $unbufferedConnectionRU->executeQuery(/** @lang MySQL */ '
                select 
                    :providerCode as ProviderCode,
                    ah.AccountID,
                    ah.Miles,
                    ah.Description,
                    ah.PostingDate
                from Provider p
                join Account a on
                    a.ProviderID = p.ProviderID and
                    a.UpdateDate > :startDate
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                join AccountHistory ah on
                    ah.AccountID = a.AccountID and
                    ah.SubAccountID is null and
                    (ah.PostingDate between :startDate and :endDate)
                where 
                    a.ProviderID = :provider and
                    ap.AccountPropertyID is null and # exclude rows with stopProperties
                    ah.Miles < 0 and
                    position(\'expir\' in ah.Description) > 0
                limit 10
                ',
                    [
                        ':providerCode' => $providerCode,
                        ':startDate' => $startDate->format('Y-m-d H:i:s'),
                        ':endDate' => $endDate->format('Y-m-d H:i:s'),
                        ':stopPropertyIds' => $stopPropertyIds,
                        ':provider' => $providerId,
                    ],
                    [
                        ':providerCode' => \PDO::PARAM_STR,
                        ':startDate' => \PDO::PARAM_STR,
                        ':endDate' => \PDO::PARAM_STR,
                        ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                        ':provider' => \PDO::PARAM_INT,
                    ]);
                $timer->stop();
                $accounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $this->log("# {$counter} # Expired examples ({$providerCode})", $accounts);

                yield from $accounts;
            }
        };

        $unbufferedConnectionRU->close();

        return $this->log('Expired', it($othersExpirations())->toArray());
    }

    protected function flights(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();

        $unbufferedConnectionRU->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS tzdatainfographicsDep (
                Code VARCHAR(3) NOT NULL,
                TimeZoneLocation VARCHAR(64) NOT NULL,
                TimeZoneOffset INT DEFAULT 0 NOT NULL,
                PRIMARY KEY (Code)
            ) ENGINE=INNODB AS (
                SELECT
                    AirCode AS Code,
                    TimeZoneLocation
                FROM AirCode
            )
        ");
        $unbufferedConnectionRU->executeQuery("CREATE TEMPORARY TABLE IF NOT EXISTS tzdatainfographicsArr LIKE tzdatainfographicsDep");
        $unbufferedConnectionRU->executeQuery("INSERT INTO tzdatainfographicsArr SELECT * FROM tzdatainfographicsDep");

        $tzdatainfographicsRows = $unbufferedConnectionRU->executeQuery('SELECT * FROM tzdatainfographicsDep')->fetchAllAssociative();
        $updateQueryArr = $unbufferedConnectionRU->prepare('UPDATE tzdatainfographicsArr SET TimeZoneOffset = ? WHERE Code = ?');
        $updateQueryDep = $unbufferedConnectionRU->prepare('UPDATE tzdatainfographicsDep SET TimeZoneOffset = ? WHERE Code = ?');

        foreach ($tzdatainfographicsRows as $row) {
            try {
                $tz = new \DateTimeZone($row['TimeZoneLocation']);
            } catch (\Exception $e) {
                $tz = new \DateTimeZone('UTC');
            }
            $updateParams = [$tz->getOffset(new \DateTime()), $row['Code']];
            $updateQueryArr->execute($updateParams);
            $updateQueryDep->execute($updateParams);
        }

        $timer = $this->startTimer('Flight duration query');
        $duration = $unbufferedConnectionRU->executeQuery("
            select 
                sum(
                    greatest(
                        unix_timestamp(date_add(ts.ArrDate, interval - ifnull(arrTz.TimeZoneOffset, 0) second)) 
                        -
                        unix_timestamp(date_add(ts.DepDate, interval - ifnull(depTz.TimeZoneOffset, 0) second)), 
                        0
                    )
                ) as TotalDuration
                 
            from TripSegment ts
            join Trip t on ts.TripID = t.TripID
            left join Provider p on 
                t.ProviderID = p.ProviderID and 
                p.State = :testKind
            left join tzdatainfographicsDep depTz on
                depTz.Code = ts.DepCode
            left join tzdatainfographicsArr arrTz on
                arrTz.Code = ts.ArrCode  
            where
                (ts.DepDate between :startDate and :endDate) and 
                p.ProviderID is null and 
                ts.Hidden = 0 and
                t.Hidden = 0 and
                t.Cancelled = 0
        ",
            [
                ':testKind' => PROVIDER_TEST,
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ],
            [
                ':testKind' => \PDO::PARAM_INT,
                ':startDate' => \PDO::PARAM_STR,
                ':endDate' => \PDO::PARAM_STR,
            ])->fetchColumn();
        $timer->stop();

        $flights = $this->flightsCount($unbufferedConnectionRU, $startDate, $endDate);

        $timer = $this->startTimer('Flights awards spent query');
        $milesSpentStmt = $unbufferedConnectionRU->executeQuery("
                    select
                        distinct t.TripID,
                        t.SpentAwards,
                        t.Total
                    from TripSegment ts
                    join Trip t on
                        ts.TripID = t.TripID
                    left join Provider p on
                        t.ProviderID = p.ProviderID and
                        p.State = :testKind
                    where 
                        (ts.DepDate between :startDate and :endDate) and
                        p.ProviderID is null and 
                        t.Hidden = 0 and
                        ts.Hidden = 0 and
                        t.Cancelled = 0",
            [
                ':testKind' => PROVIDER_TEST,
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ],
            [
                ':testKind' => \PDO::PARAM_INT,
                ':startDate' => \PDO::PARAM_STR,
                ':endDate' => \PDO::PARAM_STR,
            ]
        );
        $timer->stop();

        $totalCharge = 0;
        $spendAwards = 0;

        foreach (stmt($milesSpentStmt) as [$_, $spent, $total]) {
            $totalCharge += (int) \round($total);

            if (\preg_match('/([0-9\.,]+)/', $spent, $matches)) {
                $spendAwards += (int) \str_replace([',', '.'], '', $matches[1]);
            }
        }

        $unbufferedConnectionRU->close();

        return $this->log('Flights', [
            'segments' => $flights,
            'duration' => $this->secondsToTime((int) $duration),
            'totalCharge' => $totalCharge,
            'spentAwards' => $spendAwards,
        ]);
    }

    protected function checkins(\DateTime $startDate, \DateTime $endDate): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();

        $nights = $this->hotelsNightsCount($unbufferedConnectionRU, $startDate, $endDate);
        $timer = $this->startTimer('Hotels, spent awards query');
        $spentStmt = $unbufferedConnectionRU->executeQuery("
                select
                    r.Total,
                    r.SpentAwards
                from Reservation r
                left join Provider p on 
                    p.ProviderID = r.ProviderID and 
                    p.State = :testKind
                where
                    (r.CheckInDate between :startDate and :endDate) and
                    r.Cancelled = 0",
            [
                ':testKind' => PROVIDER_TEST,
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ],
            [
                ':testKind' => \PDO::PARAM_INT,
                ':startDate' => \PDO::PARAM_STR,
                ':endDate' => \PDO::PARAM_STR,
            ]
        );
        $timer->stop();

        $totalCharge = 0;
        $spentAwards = 0;

        foreach (stmt($spentStmt) as [$total, $spent]) {
            $totalCharge += (int) \round($total);

            if (\preg_match('/([0-9\.,]+)/', $spent, $matches)) {
                $spentAwards += (int) \str_replace([',', '.'], '', $matches[1]);
            }
        }

        $unbufferedConnectionRU->close();

        return $this->log('Hotels', [
            'nights' => $nights,
            'duration (1 night ~= 1 day)' => $this->secondsToTime(((int) $nights) * 24 * 3600),
            'totalCharge' => $totalCharge,
            'spentAwards' => $spentAwards,
        ]);
    }

    protected function detectedCards(\DateTime $startDate, \DateTime $endDate): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $detectedCardId = $unbufferedConnectionRU->executeQuery("select ProviderPropertyID from ProviderProperty where Code = 'DetectedCards' and ProviderID is null")->fetchColumn();

        $timer = $this->startTimer('Detected cards query');
        $stat = $unbufferedConnectionRU->executeQuery("
            select
               count(distinct a.UserID) as UsersCount,
               sum(
                    if (
                        ap.Val is not null,
                        if (
                            substr(ap.Val from 1 for 2) = 'a:',
                            # extract serialized array count
                            cast(substr(ap.Val from 3 for locate(':', ap.Val, 4) - 3) as unsigned),
                            1
                        ),
                        1
                    )
               ) as DetectedCardsCount
            from Provider p 
            join Account a on p.ProviderID = a.ProviderID  
            left join AccountProperty ap on
                ap.ProviderPropertyID = :detectedCardPropId and 
                ap.AccountID = a.AccountID and
                ap.SubAccountID is null
            where
                p.Kind = :ccKind and
                a.SuccessCheckDate is not null
        ",
            [
                ':detectedCardPropId' => $detectedCardId,
                ':ccKind' => PROVIDER_KIND_CREDITCARD,
            ],
            [
                ':detectedCardPropId' => \PDO::PARAM_INT,
                ':ccKind' => \PDO::PARAM_INT,
            ]
        )->fetch(\PDO::FETCH_ASSOC);
        $timer->stop();

        $unbufferedConnectionRU->close();

        return $this->log('Credit Cards', [
            'Users' => (int) $stat['UsersCount'],
            'Detected Cards' => (int) $stat['DetectedCardsCount'],
            'Average (per user)' => \round(((int) $stat['DetectedCardsCount']) / ((int) $stat['UsersCount']), 2),
        ]);
    }

    protected function userLocations(): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $stat = $unbufferedConnectionRU->executeQuery("
            select 
                ifnull(c.Name, 'unknown') as CountryName,
                ifnull(c.Code, 'unknown') as CountryCode,
                count(c.CountryID) as UsersCount 
            from Usr u
            left join Country c on u.CountryID = c.CountryID
            group by c.CountryID
            order by userscount desc
            limit 50
        ");

        $unbufferedConnectionRU->close();

        return $this->log('User Locations', $stat->fetchAll(\PDO::FETCH_ASSOC));
    }

    protected function bookingRequests(\DateTime $startDate, \DateTime $endDate): string
    {
        $unbufferecConnectionRU = $this->getUnbufferedReadUncomittedConnection();

        $count = $unbufferecConnectionRU->executeQuery('
            select count(*) as Count
            from AbRequest abr
            where 
              (abr.CreateDate between :startDate and :endDate)
        ',
            [
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ],
            [
                ':startDate' => \PDO::PARAM_STR,
                ':endDate' => \PDO::PARAM_STR,
            ]
        )->fetchColumn();

        $unbufferecConnectionRU->close();

        return $this->log('Booking Requests', ['count' => $count]);
    }

    protected function topCityTargets(\DateTime $startDate, \DateTime $endDate): string
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $timer = $this->startTimer('Top 10 cities targets (reservations) query');
        $topCitiesFromReservations = $unbufferedConnectionRU->executeQuery("
            select
                count(*) as StaysCount,
                trim(gt.City) as CityName,
                trim(gt.Country) as CountryName
            from Reservation r
            left join Provider p on
                r.ProviderID = p.ProviderID and
                p.State = :testKind
            join GeoTag gt on 
                r.GeoTagID = gt.GeoTagID and 
                gt.City is not null and
                trim(gt.City) <> ''
            where
                (r.CheckInDate between :startDate and :endDate) and 
                p.ProviderID is null and
                r.Hidden = 0
            group by trim(gt.Country), trim(gt.City)
            order by StaysCount desc
            limit 10
            ",
            [
                ':testKind' => PROVIDER_TEST,
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ],
            [
                ':testKind' => \PDO::PARAM_INT,
                ':startDate' => \PDO::PARAM_STR,
                ':endDate' => \PDO::PARAM_STR,
            ]
        )->fetchAll(\PDO::FETCH_ASSOC);
        $timer->stop();

        $unbufferedConnectionRU->close();

        return $this->log('Top 10 reservations targets (by city)', [
            'from reservations' => $topCitiesFromReservations,
        ]);
    }

    protected function topCountryTargets(\DateTime $startDate, \DateTime $endDate)
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $timer = $this->startTimer('Top 10 countries targets (reservations) query');
        $topCitiesFromReservations = $unbufferedConnectionRU->executeQuery("
            select
                count(*) as StaysCount,
                trim(gt.Country) as CountryName
            from Reservation r
            left join Provider p on
                r.ProviderID = p.ProviderID and
                p.State = :testKind
            join GeoTag gt on 
                r.GeoTagID = gt.GeoTagID and 
                gt.Country is not null and
                trim(gt.Country) <> ''
            where
                (r.CheckInDate between :startDate and :endDate) and 
                p.ProviderID is null and
                r.Hidden = 0
            group by trim(gt.Country)
            order by StaysCount desc
            limit 10
            ",
            [
                ':testKind' => PROVIDER_TEST,
                ':startDate' => $startDate->format('Y-m-d H:i:s'),
                ':endDate' => $endDate->format('Y-m-d H:i:s'),
            ],
            [
                ':testKind' => \PDO::PARAM_INT,
                ':startDate' => \PDO::PARAM_STR,
                ':endDate' => \PDO::PARAM_STR,
            ]
        )->fetchAll(\PDO::FETCH_ASSOC);
        $timer->stop();

        $unbufferedConnectionRU->close();

        return $this->log('Top 10 reservations targets (by country)', [
            'from reservations' => $topCitiesFromReservations,
        ]);
    }

    protected function balances()
    {
        $unbufferedConnectionRU = $this->getUnbufferedReadUncomittedConnection();
        $stats = [
            'air' => 0,
            'hotel' => 0,
            'credit' => 0,
        ];

        $stopPropertyIds = stmtColumn($unbufferedConnectionRU->executeQuery("select ProviderPropertyID from ProviderProperty where Name = 'currency' or Name = 'currencytype'"))
            ->mapToInt()
            ->toArray();

        $balanceMiles = function () use ($stopPropertyIds, $unbufferedConnectionRU) {
            $timer = $this->startTimer('Balance miles (Account) query');
            $stmt = $unbufferedConnectionRU->executeQuery(/** @lang MySQL */ '
                select 
                    sum(round(a.Balance)) as Balance,
                    p.Kind as Kind
                from Provider p
                join Account a on
                    a.ProviderID = p.ProviderID
                left join AccountProperty ap on 
                    ap.AccountID = a.AccountID and
                    ap.SubAccountID is null and
                    ap.ProviderPropertyID in (:stopPropertyIds)
                where
                    p.Kind in (1, 2, 6) and # airline, hotel
                    p.State > 0 and
                    a.ProviderID not in (84, 7, 26) and # exclude amex, delta, united 
                    ap.AccountPropertyID is null and # exclude rows with stopProperties
                    a.Balance < 50 * 1000 * 1000
                group by p.Kind
        ',
                [
                    ':stopPropertyIds' => $stopPropertyIds,
                ],
                [
                    ':stopPropertyIds' => Connection::PARAM_INT_ARRAY,
                ]);
            $timer->stop();

            yield from $stmt;
        };

        $unbufferedConnectionRU->close();

        foreach ($balanceMiles() as $sum) {
            $kind = [1 => 'air', 2 => 'hotel', 6 => 'credit'][$sum['Kind']];

            $stats[$kind] += $sum['Balance'];
        }

        return $this->log('Balances', $stats);
    }

    protected function startTimer(string $title): Timer
    {
        return new Timer($title, $this->output);
    }

    protected function log(string $title, $data, ?string $key = null): string
    {
        if (isset($key)) {
            $this->cachedValuesMap[$key] = $data;
        }

        $this->output->writeln('');
        $this->output->write($total = "{$title}:\n" . \json_encode($data, \JSON_PRETTY_PRINT));
        $this->output->writeln('');

        return $total;
    }

    protected function secondsToTime($seconds): string
    {
        $start = new \DateTime('@0');
        $end = new \DateTime("@$seconds");

        return $start->diff($end)->format('%a day(s) (%y year(s), %m month(s), %d day(s)), %h hour(s), %i minute(s) and %s second(s)');
    }

    protected function getUnbufferedReadUncomittedConnection(): Connection
    {
        $connection = $this->replicaUnbufferedConnection;
        $connection->setTransactionIsolation(Connection::TRANSACTION_READ_UNCOMMITTED);

        return $connection;
    }

    private function flightsCount(Connection $unbufferedConnectionRU, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): int
    {
        $timer = $this->startTimer('Flights count query');
        $flights = $unbufferedConnectionRU->executeQuery("
            select 
                count(*) 
            from TripSegment ts 
            join Trip t on 
                ts.TripID = t.TripID 
            left join Provider p on
                t.ProviderID = p.ProviderID and
                p.State = :testKind
            where
                " . (isset($startDate, $endDate) ? "(ts.DepDate between :startDate and :endDate) and" : "") . "
                p.ProviderID is null and
                ts.Hidden = 0 and
                t.Hidden = 0 and
                t.Cancelled = 0
            ",
            \array_merge(
                [':testKind' => PROVIDER_TEST],
                isset($startDate, $endDate) ? [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                ] : []
            ),
            \array_merge(
                [':testKind' => \PDO::PARAM_INT],
                isset($startDate, $endDate) ? [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                ] : [],
            )
        )->fetchAllNumeric();
        $timer->stop();

        return $flights[0][0];
    }

    private function hotelsNightsCount(Connection $unbufferedConnectionRU, ?\DateTime $startDate, ?\DateTime $endDate)
    {
        $timer = $this->startTimer('Hotels, spent nights query');
        $nights = $unbufferedConnectionRU->executeQuery("
            select
                sum(
                    greatest(
                        round(
                            (
                                unix_timestamp(date_format(
                                    r.CheckOutDate,
                                    '%Y-%m-%d'
                                ))
                                -
                                unix_timestamp(date_format(
                                    if(
                                        date_format(r.CheckInDate, '%H:%i') < '06:00', 
                                        date_add(r.CheckInDate, interval - 1 day), 
                                        r.CheckInDate
                                    ), 
                                    '%Y-%m-%d'
                                ))
                            ) / (3600 * 24)
                        ),
                        1
                    )
                )
            from Reservation r
            left join Provider p on 
                p.ProviderID = r.ProviderID and 
                p.State = :testKind
            where
                " . (isset($startDate, $endDate) ? "(r.CheckInDate between :startDate and :endDate) and " : "") . " 
                p.ProviderID is null and 
                r.Hidden = 0 and
                r.Cancelled = 0
        ",
            \array_merge(
                [':testKind' => PROVIDER_TEST],
                isset($startDate, $endDate) ? [
                    ':startDate' => $startDate->format('Y-m-d H:i:s'),
                    ':endDate' => $endDate->format('Y-m-d H:i:s'),
                ] : []
            ),
            \array_merge(
                [':testKind' => \PDO::PARAM_INT],
                isset($startDate, $endDate) ? [
                    ':startDate' => \PDO::PARAM_STR,
                    ':endDate' => \PDO::PARAM_STR,
                ] : []
            )
        )->fetchAllNumeric();
        $timer->stop();

        return $nights[0][0];
    }
}

class Timer
{
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var int
     */
    private $start;
    /**
     * @var string
     */
    private $title;

    public function __construct(string $title, OutputInterface $output)
    {
        $this->output = $output;
        $this->start = \time();
        $this->title = $title;

        $output->writeln($title . ': started...');
    }

    public function stop()
    {
        $this->output->writeln($this->title . ': took ' . (\time() - $this->start) . 's');
    }
}

class ExpiredStats
{
    /**
     * @var int
     */
    public $air = 0;

    /**
     * @var int
     */
    public $hotel = 0;

    /**
     * @var int
     */
    public $credit = 0;
}
