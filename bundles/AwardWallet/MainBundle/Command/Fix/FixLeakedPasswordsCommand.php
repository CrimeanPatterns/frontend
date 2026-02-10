<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixLeakedPasswordsCommand extends Command
{
    public static $defaultName = 'aw:fix:leaked-passwords';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \Elasticsearch\Client
     */
    private $client;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var int
     */
    private $startTime;
    /**
     * @var int
     */
    private $endTime;

    public function __construct(
        string $elasticSearchHost,
        LoggerInterface $logger,
        \Memcached $memcached
    ) {
        parent::__construct();

        $this->client = ClientBuilder::create()
            ->setHosts([$elasticSearchHost])
            ->build();
        $this->logger = $logger;
        $this->memcached = $memcached;
        $this->startTime = strtotime("2020-02-25 12:00");
        $this->endTime = strtotime("2020-02-26 03:00");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $knownPasswords = $this->findUsersWithKnownPasswords();
        $notChangedPasswords = $this->excludeChangedPasswords($knownPasswords);
        $this->renderTable("Users who not changed password", $notChangedPasswords, $output);
        $loggedIn = $this->findLoggedIn($notChangedPasswords);
        $this->renderTable("Users who logged in and not changed password", $loggedIn, $output);
        $passwordAccessed = $this->findPasswordAccess($loggedIn);
        $this->renderTable("Users with password access", $passwordAccessed, $output);
        $this->generateSql($notChangedPasswords);

        return 0;
    }

    private function findUsersWithKnownPasswords()
    {
        $this->logger->info("searching for valid passwords");

        $logins = $this->query("\"valid password\" AND extra.app: frontend", $this->startTime, $this->endTime);
        $users = it($logins)
            ->reindex(function (array $row) { return $row['UserID']; })
            ->collapseByKey()
            ->map(function (array $rows) { return $rows[count($rows) - 1]; })
            ->chunk(100)
            ->map(function (array $rows) {
                return $this->addRequestData($rows, 'first_line_request: *', $this->startTime, $this->endTime, function (?array $row) {
                    return [
                        'UserAgent' => $row['useragent'] ?? null,
                        'RequestLine' => $row['first_line_request'] ?? null,
                    ];
                });
            })
            ->flatten(1)
            ->filter(function (array $row) {
                return
                    $row['RequestLine'] === 'POST /m/api/login_check HTTP/1.1'
                    && $row['UserAgent'] === 'AwardWallet/4.13.16 CFNetwork/978.0.7 Darwin/18.7.0';
            })
            ->chunk(100)
            ->map(function (array $rows) {
                return $this->addRequestData($rows, 'message: stat', $this->startTime, $this->endTime, function (?array $row) {
                    return ['DeviceUuid' => $row['context']['deviceUuid'] ?? null];
                });
            })
            ->flatten(1)
            ->filter(function (array $row) {
                return isset($row['DeviceUuid']) && stripos($row['DeviceUuid'], 'CB00-47E6-8A52') !== false;
            })
            ->map(function (array $row) {
                return array_intersect_key($row, ['UserID' => true, 'RequestID' => true]);
            })
            ->toArray()
        ;

        return $users;
    }

    private function findLoggedIn(array $users)
    {
        $users = it($users)
            ->chunk(100)
            ->map(function (array $rows) {
                $userIds = it($rows)
                    ->map(function (array $row) { return $row['UserID']; })
                    ->joinToString(' OR ');

                $successes = it($this->query('message: "Auth success" AND context.userid: (' . $userIds . ')', $this->startTime, strtotime("tomorrow")))
                    ->reindex(function (array $row) { return $row['context']['userid']; })
                    ->collapseByKey()
                    ->map(function (array $rows) { return $rows[0]; })
                    ->toArrayWithKeys();

                $rows = it($rows)
                    ->map(function (array $row) use ($successes) {
                        $row['SuccessRequestID'] = $successes[$row['UserID']]['RequestID'] ?? null;

                        return $row;
                    })
                    ->toArray();

                return $rows;
            })
            ->flatten(1)
            ->filter(function (array $row) {
                return $row['SuccessRequestID'] !== null;
            })
            ->toArray()
        ;

        return $users;
    }

    private function findPasswordAccess(array $users)
    {
        $users = it($users)
            ->chunk(100)
            ->map(function (array $rows) {
                $userIds = it($rows)
                    ->map(function (array $row) { return $row['UserID']; })
                    ->joinToString(' OR ');

                $accesses = it($this->query('message: "password access" AND UserID: (' . $userIds . ')', $this->startTime, strtotime("tomorrow")))
                    ->reindex(function (array $row) { return $row['UserID']; })
                    ->collapseByKey()
                    ->map(function (array $rows) { return $rows[0]; })
                    ->toArrayWithKeys();

                $rows = it($rows)
                    ->map(function (array $row) use ($accesses) {
                        $row['PasswordRequestID'] = $accesses[$row['UserID']]['RequestID'] ?? null;

                        return $row;
                    })
                    ->toArray();

                return $rows;
            })
            ->flatten(1)
            ->filter(function (array $row) {
                return $row['PasswordRequestID'] !== null;
            })
            ->toArray()
        ;

        return $users;
    }

    private function query(string $query, int $startTime, int $endTime)
    {
        $key = "es_query1_" . sha1($query . $startTime . $endTime);
        $response = $this->memcached->get($key);

        if ($response !== false) {
            return $response;
        }

        $response = $this->client->search([
            "index" => "logstash-*",
            "size" => 500,
            'scroll' => '30s',
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
                            $this->dateRange($startTime, $endTime),
                        ],
                    ],
                ],
            ],
        ]);

        $result = array_map(function (array $doc) { return $doc['_source']; }, $this->scroll($response));
        $this->memcached->set($key, $result, 86400);

        return $result;
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

    private function scroll(array $response): array
    {
        $result = $response['hits']['hits'];

        while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
            $response = $this->client->scroll([
                'scroll_id' => $response['_scroll_id'],
                'scroll' => '30s',
            ]);
            $result = array_merge($result, $response['hits']['hits']);
            $this->logger->info("loaded " . count($result) . " documents");
        }

        return $result;
    }

    private function addRequestData(array $rows, string $query, int $startTime, int $endTime, callable $dataExtractor): array
    {
        $requestIds = it($rows)
            ->map(function (array $row) { return $row['RequestID']; })
            ->joinToString(' OR ');

        $requests = $this->query($query . ' AND RequestID: (' . $requestIds . ')', $startTime, $endTime);
        $requests = it($requests)
            ->reindex(function (array $row) { return (string) $row['RequestID']; })
            ->toArrayWithKeys()
        ;

        $rows = it($rows)
            ->map(function (array $row) use ($requests, $dataExtractor) {
                $request = $requests[$row['RequestID']] ?? null;

                return array_merge($row, call_user_func($dataExtractor, $request ?? null));
            })
            ->toArray();

        return $rows;
    }

    private function excludeChangedPasswords(array $users): array
    {
        $users = it($users)
            ->chunk(100)
            ->flatMap(function (array $rows) {
                $userIds = it($rows)
                    ->map(function (array $row) { return $row['UserID']; })
                    ->joinToString(' OR ');

                $passwordChanges = it($this->query('message: "changing password" AND UserID: (' . $userIds . ')', $this->startTime, strtotime("tomorrow")))
                    ->reindex(function (array $row) { return $row['UserID']; })
                    ->collapseByKey()
                    ->map(function (array $rows) { return $rows[0]; })
                    ->toArrayWithKeys();

                $rows = it($rows)
                    ->filter(function (array $row) use ($passwordChanges) {
                        return !array_key_exists($row['UserID'], $passwordChanges);
                    })
                    ->toArray();

                return $rows;
            })
            ->toArray()
        ;
        $this->logger->info("users count who not changed password: " . count($users));

        return $users;
    }

    private function renderTable(string $title, array $rows, OutputInterface $output)
    {
        if (count($rows) > 0) {
            $output->writeln($title);
            $table = new Table($output);
            $table->setHeaders(array_keys($rows[0]));
            $table->setRows($rows);
            $table->render();
        }
        $output->writeln($title . " - total: " . count($rows));
    }

    private function generateSql(array $notChangedPasswords)
    {
        it($notChangedPasswords)
            ->map(function (array $row) { return $row['UserID']; })
            ->chunk(50)
            ->flatMap(function (array $userIds) {
                $this->logger->info("resetting passwords for " . count($userIds) . " users");
                $sql = "update Usr set Pass = replace(Pass, '\$', '_') where UserID in (" . implode(", ", $userIds) . ") limit 50";
                $this->logger->info($sql);

                return [$sql];
            })
            ->toArray()
        ;
    }
}
