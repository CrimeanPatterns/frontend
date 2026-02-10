<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\DateUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class RegisteredUsersWithFirstSucceededAccountStatsCommand extends Command
{
    protected static $defaultName = 'aw:stats:registered-users-with-first-succeeded-account';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Elasticsearch\Client
     */
    private $client;
    /**
     * @var Connection
     */
    private $dbConnection;

    public function __construct(
        string $elasticSearchHost,
        LoggerInterface $logger,
        Connection $connection
    ) {
        parent::__construct();

        $this->client = ClientBuilder::create()
            ->setHosts([$elasticSearchHost])
            ->build();
        $this->logger = $logger;
        $this->dbConnection = $connection;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $startDate = new \DateTime($input->getArgument('startDate'));
        $endDate = new \DateTime($input->getArgument('endDate'));

        // registration point
        $registerHitsList = it($this->query(
            'first_line_request:("POST /m/api/register" OR "POST /user/register") AND UserID:>0',
            $startDate,
            $endDate
        ))->toArray();

        $usersWithNewAccountsMap =
            it($registerHitsList)
            ->usort(function (array $hit1, array $hit2) { return $hit1['timestamp'] <=> $hit2['timestamp']; })
            ->chunk(100)
            ->flatMap(function (array $registrationHitsChunk) use ($endDate) {
                $startDate = new \DateTimeImmutable(
                    it($registrationHitsChunk)
                    ->column('timestamp')
                    ->min()
                );
                $userIds = \array_column($registrationHitsChunk, 'UserID');

                // extract RequestID's from account-add routes
                $accountAddNoProviderIdHits =
                    it($this->query(
                        'context.route:("awm_newapp_account_add" OR "aw_account_add") AND message:"stat" AND UserID:(' .
                            it($userIds)
                            ->joinToString(' OR ')
                        . ')',
                        $startDate,
                        $endDate
                    ))
                    ->toArray();

                $accountAddRequestIdToUserIdMap =
                    it($accountAddNoProviderIdHits)
                    ->reindex(function (array $row) { return $row['RequestID']; })
                    ->column('UserID')
                    ->toArrayWithKeys();

                // extract ProviderID's from account-add routes
                $accountAddWithProviderIdHits =
                    $this->query(
                        'RequestID:(' .
                            it($accountAddNoProviderIdHits)
                            ->column('RequestID')
                            ->joinToString(' OR ')
                        . ') AND message:"Matched route" AND channel:"request" AND context.method:"POST"',
                        $startDate,
                        $endDate
                    );

                $userIdToProviderMap = \array_fill_keys($userIds, true);

                foreach ($accountAddWithProviderIdHits as $accountAddWithProviderIdHit) {
                    $userId = $accountAddRequestIdToUserIdMap[$accountAddWithProviderIdHit['RequestID']];

                    if (
                        isset($userIdToProviderMap[$userId])
                        && !empty($accountAddWithProviderIdHit['context']['route_parameters']['providerId'])
                    ) {
                        yield $userId => [
                            $accountAddWithProviderIdHit['context']['route_parameters']['providerId'],
                            new \DateTime($accountAddWithProviderIdHit['timestamp']),
                        ];
                    }
                }
            })
            ->collapseByKey()
            ->toArrayWithKeys();

        $newAccountsList =
            it($usersWithNewAccountsMap)
            ->chunkWithKeys(100)
            ->flatMap(function (array $usersWithNewAccountsMap) {
                return $this->dbConnection->executeQuery(
                    "
                    select 
                        acc.AccountID,
                        acc.CreationDate,
                        acc.UserID,
                        acc.ProviderID,
                        p.Code,
                        p.State
                    from (
                        select
                            min(a.AccountID) as AccountID,
                            a.UserID
                        from Account a
                        where " .
                            it($usersWithNewAccountsMap)
                            ->flatten(1)
                            ->map(function () {
                                return "(
                                        a.UserID = ? AND 
                                        a.ProviderID = ? AND
                                        (a.CreationDate between DATE_ADD(?, INTERVAL -1 MINUTE) AND DATE_ADD(?, INTERVAL 1 MINUTE))
                                    )";
                            })
                            ->joinToString(' OR ') .
                        " group by a.UserID
                    ) stat
                    join Account acc on acc.AccountID = stat.AccountID
                    join Provider p on acc.ProviderID = p.ProviderID",

                    ...it($usersWithNewAccountsMap)
                    ->fold([[], []], function (array $acc, array $providers, $userId) {
                        [$values, $types] = $acc;

                        foreach ($providers as [$providerId, $date]) {
                            $dateSql = DateUtils::toSQLDateTime($date);
                            $values = \array_merge($values, [$userId, $providerId, $dateSql, $dateSql]);
                            $types = \array_merge($types, [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR]);
                        }

                        return [$values, $types];
                    })
                )->fetchAll(FetchMode::ASSOCIATIVE);
            })
            ->toArray();

        [$checkingOffAccounts, $newAccountsUpdatedList] =
            it($newAccountsList)
            ->partition(function (array $account) { return $account['State'] == 4; });

        $newAccountsUpdatedList = $newAccountsUpdatedList->toArray();
        $checkingOffAccountsList = $checkingOffAccounts->toArray();

        $providerToAccountsMap =
            it($newAccountsUpdatedList)
            ->usort(function (array $acc1, array $acc2) { return $acc1['CreationDate'] <=> $acc2['CreationDate']; })
            ->chunk(100)
            ->flatMap(function (array $accountsChunk) {
                $accountsByIDMap =
                    it($accountsChunk)
                    ->reindex(function (array $row) { return $row['AccountID']; })
                    ->toArrayWithKeys();

                $boundsExtractor = function (iterable $iterable, string $column, string $offset = '10 minute') {
                    [$minStartDate, $maxEndDate] =
                        it($iterable)
                        ->column($column)
                        ->bounds();

                    return [
                        (new \DateTimeImmutable($minStartDate))->modify('-' . $offset),
                        (new \DateTimeImmutable($maxEndDate))->modify('+' . $offset),
                    ];
                };

                $startCheckHitsList = it($this->query(
                    'message:"Account successfully sent to check." AND context.accountId:(' .
                        it($accountsChunk)
                        ->column('AccountID')
                        ->joinToString(' OR ') .
                    ')',
                    ...$boundsExtractor($accountsChunk, 'CreationDate')
                ))->toArray();

                $startCheckHitsList =
                    it($startCheckHitsList)
                    ->filter(function (array $row) use ($accountsByIDMap) {
                        return
                            \strtotime($row['timestamp']) -
                            \strtotime($accountsByIDMap[$row['context']['accountId']]['CreationDate'])
                            < 60 * 35;
                    })
                    ->toArray();

                $statisticHits =
                    it(
                        $this->query(
                            'message:"statistic" AND extra.partner:"awardwallet" AND context.RequestID:(' .
                                    it($startCheckHitsList)
                                    ->map(function (array $row) { return $row['context']['requestId']; })
                                    ->joinToString(" OR ") .
                                ')',
                            ...$boundsExtractor($startCheckHitsList, 'timestamp')
                        )
                    )
                    ->collect()
                    ->usort(function (array $row1, array $row2) { return $row1['timestamp'] <=> $row2['timestamp']; })
                    ->toArray();

                foreach ($statisticHits as $statisticHit) {
                    $userData = \json_decode($statisticHit['context']['UserData'] ?? '[]', true);

                    if (
                        !empty($userData['accountId'])
                        && isset($accountsByIDMap[$userData['accountId']])
                    ) {
                        $accountData = $accountsByIDMap[$userData['accountId']];
                        unset($accountsByIDMap[$userData['accountId']]);

                        yield $accountData['Code'] => \array_merge(
                            $accountData,
                            ['Error' => $statisticHit['context']['ErrorCode'] != 1]
                        );
                    }
                }

                $browserCheckHitsList =
                    it(
                        $this->query(
                            'message:"account saved" AND context.checkedBy:"Browser" AND context.AccountID:(' .
                                it($accountsByIDMap)
                                ->keys()
                                ->joinToString(' OR ') .
                            ')',
                            ...$boundsExtractor($accountsByIDMap, 'CreationDate', '30 minute')
                        )
                    )
                    ->filter(function (array $row) use ($accountsByIDMap) {
                        return
                            \strtotime($row['timestamp']) -
                            \strtotime($accountsByIDMap[$row['context']['AccountID']]['CreationDate'])
                            < 60 * 35;
                    })
                    ->collect()
                    ->usort(function (array $row1, array $row2) { return $row1['timestamp'] <=> $row2['timestamp']; })
                    ->toArray();

                foreach ($browserCheckHitsList as $browserCheckHit) {
                    if (isset($accountsByIDMap[$browserCheckHit['context']['AccountID']])) {
                        $accountId = $browserCheckHit['context']['AccountID'];
                        $accountData = $accountsByIDMap[$accountId];
                        unset($accountsByIDMap[$accountId]);

                        yield $accountData['Code'] => \array_merge(
                            $accountData,
                            ['Error' => $browserCheckHit['context']['errorCode'] != 1]
                        );
                    }
                }
            })
            ->chain(call(function () use ($checkingOffAccountsList) {
                foreach ($checkingOffAccountsList as $checkingOffAccount) {
                    yield $checkingOffAccount['Code'] => \array_merge(
                        $checkingOffAccount,
                        ['Error' => true]
                    );
                }
            }))
            ->collapseByKey()
            ->uasort(function (array $arr1, array $arr2) { return \count($arr2) - \count($arr1); }) // sort by popularity desc
            ->toArrayWithKeys();

        $total =
            it($providerToAccountsMap)
            ->flatten(1)
            ->toArray();

        $providerToAccountsMap = \array_merge(
            ['### total ####' => $total],
            $providerToAccountsMap
        );

        $output->writeln("New users: " . it($registerHitsList)->column('UserID')->collect()->unique()->count());
        $output->writeln("New users with accounts: " . \count($newAccountsList));
        $jsonStat = [];

        foreach ($providerToAccountsMap as $providerCode => $accountsList) {
            $totalAccountsCount = \count($accountsList);
            $firstAccount = $accountsList[0];
            $accountsWithErrorsCount =
                it($accountsList)
                ->filter(function (array $account) { return $account['Error']; })
                ->count();
            $successRate = \round((1 - $accountsWithErrorsCount / $totalAccountsCount) * 100, 2);
            $checkingOff = ($firstAccount['State'] == 4);

            $output->writeln(
                "Provider: {$providerCode}" .
                ", accounts: {$totalAccountsCount}" .
                ", errors: {$accountsWithErrorsCount}" .
                ", success rate: {$successRate}%" .
                ($checkingOff ? ", CHECKING OFF!!!" : "")
            );
            $jsonStat[] = [
                'provider' => $providerCode,
                'accounts' => $totalAccountsCount,
                'errors' => $accountsWithErrorsCount,
                'success_rate' => $successRate,
                'checking_off' => $checkingOff,
            ];
        }

        $output->writeln("Stats json: \n" . \json_encode($jsonStat, \JSON_PRETTY_PRINT));

        return 0;
    }

    protected function configure()
    {
        $this
            ->setDescription('Stats for registrations')
            ->addArgument("startDate", InputArgument::REQUIRED, 'start date')
            ->addArgument("endDate", InputArgument::REQUIRED, 'end date');
    }

    private function query(string $query, \DateTimeInterface $startTime, \DateTimeInterface $endTime): iterable
    {
        $response = $this->client->search([
            "index" => "logstash-*",
            "size" => 500,
            'scroll' => '10m',
            "body" => [
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "query_string" => [
                                    "query" => $query,
                                    "analyze_wildcard" => true,
                                    "default_field" => "*",
                                ],
                            ],
                            $this->dateRange($startTime->getTimestamp(), $endTime->getTimestamp()),
                        ],
                    ],
                ],
            ],
        ]);

        yield from it($this->scroll($response, $query))
            ->map(function (array $doc) { return $doc['_source']; });
    }

    private function scroll(array $response, string $query): iterable
    {
        $result = $response['hits']['hits'] ?? [];
        $resultCount = \count($result);
        $page = 1;

        $logHelper = function (array $hitsList) use (&$page, $query, &$resultCount) {
            [$minDate, $maxDate] =
                it($hitsList)
                ->map(function (array $hit) { return $hit['_source']['timestamp']; })
                ->bounds();

            $this->logger->debug(
                "query: " . \substr($query, 0, 40) . "..." .
                ", loaded: {$resultCount} documents" .
                ", page: {$page}" .
                ", from: {$minDate}" .
                ", to: {$maxDate}"
            );
        };

        $logHelper($result);

        yield from $result;

        while (\count($result) > 0) {
            $response = $this->client->scroll([
                'scroll_id' => $response['_scroll_id'],
                'scroll' => '10m',
            ]);
            $result = $response['hits']['hits'] ?? [];
            $resultCount += \count($result);
            $page++;
            $logHelper($result);

            yield from $result;
        }
    }

    private function dateRange(int $from, int $before)
    {
        return [
            "range" => [
                "@timestamp" => [
                    "gte" => $from * 1000,
                    "lte" => $before * 1000,
                    "format" => "epoch_millis",
                ],
            ],
        ];
    }
}
