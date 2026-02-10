<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Command {
    use AwardWallet\MainBundle\Command\AnalyzeMissingRememberMeCookiesCommand\UserStat;
    use AwardWallet\MainBundle\Service\ElasticSearch\Client;
    use AwardWallet\MainBundle\Service\FriendsOfLoggerTrait;
    use Psr\Log\LoggerInterface;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

    /**
     * @psalm-type AccessLogRow = array{
     *     RequestID?: string,
     *     path: string,
     *     code: string,
     *     remote: string,
     *     agent: string,
     *     "@timestamp": string,
     *     UserID?: string,
     *     deviceUUID?: string,
     * }
     * @psalm-type StatLogRow = array{
     *     RequestID?: string,
     *     context?: array{
     *         deviceUuid?: string
     *     }
     * }
     */
    class AnalyzeMissingRememberMeCookiesCommand extends Command
    {
        use FriendsOfLoggerTrait;
        protected static $defaultName = 'aw:analyze-missing-remember-me-cookies';
        private Client $elasticSearchClient;
        private LoggerInterface $logger;

        public function __construct(Client $elasticSearchClient, LoggerInterface $logger)
        {
            parent::__construct();

            $this->elasticSearchClient = $elasticSearchClient;
            $this->logger = $this->makeContextAwareLogger($logger);
        }

        public function configure(): void
        {
            $this
                ->setDescription('Analyze missing remember-me cookies');
        }

        public function execute(InputInterface $input, OutputInterface $output): int
        {
            $nowDate = new \DateTime();
            // load 403s on /m/api/mailbox/check-status
            $mbCheckStatusSuddenLogoutsIpMap =
                it($this->queryAccessLog(
                    'path:"/m/api/mailbox/check-status" AND code:403',
                    $nowDate
                ))
                ->reindex(fn (array $arrayLog): string => $arrayLog['remote'])
                ->collapseByKey()
                ->toArrayWithKeys();
            $output->writeln('Found ' . \count($mbCheckStatusSuddenLogoutsIpMap) . ' IPs with 403s on /m/api/mailbox/check-status');

            $accessLogsFromAffectedIpsList =
                it($mbCheckStatusSuddenLogoutsIpMap)
                ->keys()
                ->map(fn ($ip) => \sprintf('"%s"', $ip))
                ->chunk(200)
                ->flatMap(fn (array $ipsList) => $this->queryAccessLog(
                    'remote:(' . \implode(' OR ', $ipsList) . ')',
                    $nowDate
                ))
                ->filter(fn (array $accessLog): bool =>
                    $accessLog['@timestamp'] >= $mbCheckStatusSuddenLogoutsIpMap[$accessLog['remote']][0]['@timestamp']
                )
                ->collect()
                ->usort(fn (array $rowA, array $rowB) => isset($rowB['UserID']) <=> isset($rowA['UserID']))
                ->toArray();
            $output->writeln('Found ' . \count($accessLogsFromAffectedIpsList) . ' access logs from affected IPs');

            /** @var array<int, UserStat> $userStatsByIdMap */
            $userStatsByIdMap = [];
            /** @var array<RemoteIP, UserStat> $remoteToUserStatMap */
            $remoteToUserStatMap = [];
            /** @var array<string, UserStat> $requestIdToUserStatMap */
            $requestIdToUserStatMap = [];

            foreach ($accessLogsFromAffectedIpsList as $accessLog) {
                $userId = $accessLog['UserID'] ?? null;

                if (!isset($userId) || ($userId === '-')) {
                    continue;
                }

                if (!isset($accessLog['RequestID'])) {
                    continue;
                }

                $userId = (int) $userId;
                $userStat = $userStatsByIdMap[$userId] ?? null;

                if (\is_null($userStat)) {
                    $userStat = new UserStat();
                }

                $remote = $accessLog['remote'];
                $logsByRemote = \array_key_exists($remote, $userStat->remotesMap) ?
                    [] :
                    ($mbCheckStatusSuddenLogoutsIpMap[$remote] ?? []);

                if ($logsByRemote) {
                    $userStat->accessLogMap = \array_merge($userStat->accessLogMap, $logsByRemote);
                }

                $userStat->remotesMap[$remote] = null;
                $requestId = $accessLog['RequestID'];
                $userStat->accessLogMap[$requestId] = $accessLog;
                $userStatsByIdMap[$userId] = $userStat;
                $remoteToUserStatMap[$remote] = $userStat;
                $requestIdToUserStatMap[$requestId] = $userStat;
            }
            unset($userStat);

            foreach (
                it($userStatsByIdMap)
                ->keys()
                ->chunk(200)
                ->flatMap(fn (array $userIdsList) => $this->queryAccessLog(
                    'UserID:(' . \implode(' OR ', $userIdsList) . ')',
                    $nowDate
                ))
                ->filter(function (array $accessLog) use ($userStatsByIdMap): bool {
                    $userStat = $userStatsByIdMap[(int) ($accessLog['UserID'] ?? null)];

                    /** @psalm-suppress PossiblyNullArrayOffset */
                    return $accessLog['@timestamp'] > $userStat->accessLogMap[\array_key_first($userStat->accessLogMap)]['@timestamp'];
                }
                ) as $userAccessRow
            ) {
                $userId = $userAccessRow['UserID'] ?? null;

                if (\is_null($userId)) {
                    continue;
                }

                if (!isset($userAccessRow['RequestID'])) {
                    continue;
                }

                $userId = (int) $userId;
                $remote = $userAccessRow['remote'];
                $userStat = $userStatsByIdMap[$userId];
                $requestId = $userAccessRow['RequestID'];
                $userStat->accessLogMap[$requestId] = $userAccessRow;
                $userStat->remotesMap[$remote] = null;
                $remoteToUserStatMap[$remote] = $userStat;
                $requestIdToUserStatMap[$requestId] = $userStat;
            }

            foreach (
                it($remoteToUserStatMap)
                ->keys()
                ->map(fn ($ip) => \sprintf('"%s"', $ip))
                ->chunk(200)
                ->flatMap(fn (array $ipsList) => $this->queryAccessLog(
                    'remote:(' . \implode(' OR ', $ipsList) . ') AND NOT (UserID:*)',
                    $nowDate
                )) as $userIpAccessLog
            ) {
                if (!isset($userIpAccessLog['RequestID'])) {
                    continue;
                }

                $remote = $userIpAccessLog['remote'];
                $userStat = $remoteToUserStatMap[$remote];
                $requestId = $userIpAccessLog['RequestID'];
                $userStat->accessLogMap[$requestId] = $userIpAccessLog;
                $userStat->remotesMap[$remote] = null;
                $requestIdToUserStatMap[$requestId] = $userStat;
            }

            foreach (
                it($requestIdToUserStatMap)
                ->keys()
                ->chunk(200)
                ->flatMap(fn (array $requestIds) => $this->queryStatLog(
                    "RequestID:(" . \implode(" OR ", $requestIds) . ") AND message:stat AND _exists_:context.deviceUuid",
                    $nowDate
                )) as $statLog
            ) {
                if (!isset($statLog['RequestID'])) {
                    continue;
                }

                $requestId = $statLog['RequestID'];
                $uuid = $statLog['context']['deviceUuid'] ?? 'none';
                $requestIdToUserStatMap[$requestId]->accessLogMap[$requestId]['deviceUUID'] = $uuid;
            }

            $output->writeln('Found ' . \count($userStatsByIdMap) . ' users data');

            foreach (
                it($userStatsByIdMap)
                ->filter(fn (UserStat $stat) =>
                    it($stat->accessLogMap)
                    ->any(fn (array $accessLog) =>
                        ($accessLog['path'] === '/m/api/mailbox/check-status')
                        && ($accessLog['code'] === '200')
                    )
                ) as $userId => $userStat
            ) {
                \uasort(
                    $userStat->accessLogMap,
                    fn (array $rowA, array $rowB) => $rowA['@timestamp'] <=> $rowB['@timestamp']
                );
                $output->writeln("UserID: {$userId}");
                $output->writeln("Access Log: ");

                foreach ($userStat->accessLogMap as $accessLog) {
                    $output->writeln("{$accessLog['@timestamp']}: {$accessLog['path']} - {$accessLog['code']} - " . ($accessLog['RequestID'] ?? 'none') . " - {$accessLog['remote']} - " . ($accessLog['deviceUUID'] ?? 'none') . " - {$accessLog['agent']}");
                }
            }

            return 0;
        }

        /**
         * @psalm-suppress InvalidReturnType
         * @return iterable<AccessLogRow>
         */
        private function queryAccessLog(string $query, \DateTime $nowDate): iterable
        {
            /** @psalm-suppress InvalidReturnStatement */
            return $this->query(
                'channel:"nginx_access_log" AND ' . $query,
                $nowDate
            );
        }

        /**
         * @psalm-suppress InvalidReturnType
         * @return iterable<StatLogRow>
         */
        private function queryStatLog(string $query, \DateTime $nowDate): iterable
        {
            /** @psalm-suppress InvalidReturnStatement */
            return $this->query(
                'message:"stat" AND _exists_:context.route AND ' . $query,
                $nowDate
            );
        }

        /**
         * @psalm-suppress MoreSpecificReturnType
         * @return iterable<array{"@timesamp": string, "RequestID": string, ...}>
         */
        private function query(string $query, \DateTime $nowDate): iterable
        {
            // date of rollout of remember-me cookies (series\token hashes) logging
            $startDate = new \DateTime('09-Jul-2024 10:20:00');

            /** @psalm-suppress LessSpecificReturnStatement */
            return $this->elasticSearchClient->query(
                $query,
                $startDate,
                $nowDate,
                5000
            );
        }

        private static function makeDateFromTimestamp(string $timestamp): \DateTimeImmutable
        {
            return new \DateTimeImmutable(\substr($timestamp, 0, 19));
        }
    }
}

namespace AwardWallet\MainBundle\Command\AnalyzeMissingRememberMeCookiesCommand {
    /**
     * @psalm-type AccessLogRow = array{
     *     RequestID: string,
     *     path: string,
     *     code: string,
     *     remote: string,
     *     agent: string,
     *     "@timestamp": string,
     *     UserID?: string,
     *     deviceUUID?: string,
     * }
     */
    class UserStat
    {
        /**
         * @var array<string, AccessLogRow>
         */
        public array $accessLogMap = [];
        /**
         * @var array<string, null>
         */
        public array $remotesMap = [];
    }
}
