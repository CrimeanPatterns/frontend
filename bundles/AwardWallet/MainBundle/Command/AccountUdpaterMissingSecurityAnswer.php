<?php

namespace AwardWallet\MainBundle\Command {
    use AwardWallet\MainBundle\Command\AccountUpdaterMissingSecurityAnswer as Utils;
    use Doctrine\DBAL\Connection;
    use Elasticsearch\Client;
    use Elasticsearch\ClientBuilder;
    use Psr\Log\LoggerInterface;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;

    use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

    class AccountUdpaterMissingSecurityAnswer extends Command
    {
        private Client $client;
        private LoggerInterface $logger;
        private Connection $dbConnection;
        private array $scrollIds;

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
            $this->scrollIds = [];
        }

        public function configure()
        {
            $this->setName('aw:stat:no-security-answers')
                ->setDescription('Stats for inforgraphics')
                ->addArgument("startDate", InputArgument::REQUIRED, 'start date')
                ->addArgument("endDate", InputArgument::REQUIRED, 'end date')
                ->addOption('query-chunk-size', null, InputOption::VALUE_REQUIRED, 'ES-query chunk params size', 500)
                ->addOption('response-page-size', null, InputOption::VALUE_REQUIRED, 'ES-response page size', 500);
        }

        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            try {
                $beginDate = new \DateTimeImmutable($input->getArgument('startDate'));
                $endDate = new \DateTimeImmutable($input->getArgument('endDate'));
                $queryChunkSize = $input->getOption('query-chunk-size');
                $responseChunkSize = $input->getOption('response-page-size');

                // registration point
                /** @var Utils\SecurityAnswerNeeded[] $accountsWithSecurityQuestionsMap */
                $accountsWithSecurityQuestionsMap =
                    it($this->query(
                        'message:"account updated, code: 10" 
                        AND _exists_:context.UserID 
                        AND _exists_:context.accountId
                        AND _exists_:extra.requestId',
                        $beginDate,
                        $endDate,
                        $responseChunkSize
                    ))
                    ->filter(fn (array $doc) => isset($doc['context']['UserID'], $doc['context']['accountId']))
                    ->map(fn (array $doc) => new Utils\SecurityAnswerNeeded(
                        new \DateTimeImmutable(\substr($doc['@timestamp'], 0, 19)),
                        (int) $doc['context']['accountId'],
                        $doc['extra']['requestId']
                    ))
                    ->reindex(fn (Utils\SecurityAnswerNeeded $hit) => $hit->accountId)
                    ->collapseByKey()
                    ->map(function (array $hits) {
                        $hitsNew = $hits;

                        if (\count($hitsNew) > 1) {
                            \usort($hitsNew, fn (Utils\SecurityAnswerNeeded $doc1, Utils\SecurityAnswerNeeded $doc2) => $doc1->timestamp <=> $doc2->timestamp);
                        }

                        return $hitsNew;
                    })
                    ->toArrayWithKeys();

                /** @var Utils\UpdaterSessionAccount[] $updateSessionsWithSecurityQuestionsBySessionIdMap */
                $updateSessionsWithSecurityQuestionsBySessionIdMap =
                    it($accountsWithSecurityQuestionsMap)
                    ->keys()
                    ->chunk($queryChunkSize)
                    ->flatMap(fn (array $chunk) => $this->query(
                        'message:"active session(s) found" AND context.accountid:(' . \implode(' OR ', $chunk) . ') AND _exists_:context.updater_session_keys',
                        $beginDate->modify('+30 minutes'),
                        $endDate->modify('-30 minutes'),
                        $responseChunkSize
                    ))
                    ->filter(function (array $sessionFoundDoc) use ($accountsWithSecurityQuestionsMap) {
                        $sessionFoundDocTime = new \DateTimeImmutable(\substr($sessionFoundDoc['@timestamp'], 0, 19));

                        return
                            it($accountsWithSecurityQuestionsMap[$sessionFoundDoc['context']['accountid']])
                            ->find(fn (Utils\SecurityAnswerNeeded $securityAnswerNeeded) =>
                                ($sessionFoundDocTime >= $securityAnswerNeeded->timestamp->modify('-1 minute'))
                                && ($sessionFoundDocTime <= $securityAnswerNeeded->timestamp->modify('+30 minutes'))
                            );
                    })
                    ->filterNotNull()
                    ->reindex(fn (array $doc) => $doc['context']['updater_session_keys'][0])
                    ->mapIndexed(fn (array $doc, string $sessionKey) => new Utils\UpdaterSessionAccount(
                        new \DateTimeImmutable(\substr($doc['@timestamp'], 0, 19)),
                        $doc['context']['accountid'],
                        $sessionKey,
                        $accountsWithSecurityQuestionsMap[$doc['context']['accountid']][0]->loyaltyRequestId
                    ))
                    ->toArrayWithKeys();

                /** @var string[] $updateSessionsByRequestIDMap */
                $updateSessionsByRequestIDMap =
                    it($updateSessionsWithSecurityQuestionsBySessionIdMap)
                    ->keys()
                    ->chunk($queryChunkSize)
                    ->flatMap(fn (array $chunk) => $this->query(
                        'message:"updater session manager: Session start." AND context.updater_session_key:('
                        . it($chunk)->joinToString(' OR ')
                        . ')',
                        $beginDate,
                        $endDate,
                        $responseChunkSize
                    ))
                    ->reindex(fn (array $doc) => $doc['RequestID'])
                    ->map(fn (array $doc) => $doc['context']['updater_session_key'])
                    ->toArrayWithKeys();

                /** @var array $doc */
                foreach (
                    it($updateSessionsByRequestIDMap)
                    ->keys()
                    ->chunk($queryChunkSize)
                    ->flatMap(fn (array $updateSessionsRequestChunk) => $this->query(
                        'RequestID:('
                        . it($updateSessionsRequestChunk)->joinToString(' OR ')
                        . ') AND _exists_:context.route',
                        $beginDate,
                        $endDate,
                        $responseChunkSize
                    )) as $doc
                ) {
                    $updateSessionsWithSecurityQuestionsBySessionIdMap[$updateSessionsByRequestIDMap[$doc['RequestID']]]->platform =
                        $doc['context']['route'] === 'awm_newapp_account_updater_start' ?
                            Utils\UpdaterSessionRequest::MOBILE :
                            Utils\UpdaterSessionRequest::DESKTOP;
                }

                foreach (
                    it($updateSessionsWithSecurityQuestionsBySessionIdMap)
                    ->chunk($queryChunkSize)
                    ->flatMap(fn (array $chunk) => $this->query(
                        'context.updater_has_user_request:true AND context.updater_add_accounts:('
                        . it($chunk)
                            ->map(fn (Utils\UpdaterSessionAccount $session) => $session->accountId)
                            ->joinToString(' OR ')
                        . ')',
                        $beginDate,
                        $endDate,
                        $responseChunkSize
                    )) as $doc
                ) {
                    if (isset($updateSessionsWithSecurityQuestionsBySessionIdMap[$doc['context']['updater_session_key']])) {
                        $updateSessionsWithSecurityQuestionsBySessionIdMap[$doc['context']['updater_session_key']]->answered = true;
                    }
                }

                /** @var Utils\UpdaterSessionAccount[][] $updateSessionsWithSecurityQuestionsByAccountIdMap */
                $updateSessionsWithSecurityQuestionsByAccountIdMap =
                    it($updateSessionsWithSecurityQuestionsBySessionIdMap)
                    ->reindex(fn (Utils\UpdaterSessionAccount $session) => $session->accountId)
                    ->collapseByKey()
                    ->toArrayWithKeys();

                foreach (
                    it($updateSessionsWithSecurityQuestionsByAccountIdMap)
                    ->map(fn (array $sessions) => $sessions[0]->loyaltyRequestId)
                    ->chunk($queryChunkSize)
                    ->flatMap(fn (array $chunk) => $this->query(
                        'extra.requestId:('
                        . it($chunk)->joinToString(' OR ')
                        . ') AND _exists_:extra.provider AND message:"processing of {"',
                        $beginDate,
                        $endDate,
                        $responseChunkSize
                    )) as $doc
                ) {
                    if (
                        isset($doc['extra']['userData'])
                        && ($userData = @\json_decode($doc['extra']['userData'], true))
                        && isset($updateSessionsWithSecurityQuestionsByAccountIdMap[$userData['accountId']])
                    ) {
                        foreach (
                            $updateSessionsWithSecurityQuestionsByAccountIdMap[$userData['accountId']] as $session
                        ) {
                            $session->provider = $doc['extra']['provider'];
                        }
                    }
                }

                [$answeredSessionsList, $unansweredSessionsList] =
                    it($updateSessionsWithSecurityQuestionsBySessionIdMap)
                    ->partition(fn (Utils\UpdaterSessionAccount $session) => $session->answered);
                $answeredSessionsList = $answeredSessionsList->toArray();
                $unansweredSessionsList = $unansweredSessionsList->toArray();

                $unansweredSessionsInfo =
                    it($unansweredSessionsList)
                    ->map(fn (Utils\UpdaterSessionAccount $session) => [
                        $session->accountId,
                        $session->sessionId,
                        $session->provider,
                        $session->platform,
                    ])
                    ->toArrayWithKeys();

                $unansweredAccountsProvidersTop =
                    it($unansweredSessionsList)
                    ->map(fn (Utils\UpdaterSessionAccount $session) => $session->provider)
                    ->stat()
                    ->arsort()
                    ->take(20)
                    ->toArrayWithKeys();

                $answeredAccountsProvidersTop =
                    it($answeredSessionsList)
                    ->map(fn (Utils\UpdaterSessionAccount $session) => $session->provider)
                    ->stat()
                    ->arsort()
                    ->take(20)
                    ->toArrayWithKeys();

                $unansweredAccountsByPlatform =
                    it($unansweredSessionsList)
                    ->map(fn (Utils\UpdaterSessionAccount $session) => $session->platform)
                    ->stat()
                    ->ksort()
                    ->toArrayWithKeys();

                $answeredAccountsByPlatform =
                    it($answeredSessionsList)
                    ->map(fn (Utils\UpdaterSessionAccount $session) => $session->platform)
                    ->stat()
                    ->ksort()
                    ->toArrayWithKeys();

                $output->writeln("############ Results ############");
                $output->writeln("Sessions with unanswered questions: \n" . \json_encode($unansweredSessionsInfo, \JSON_PRETTY_PRINT));
                $output->writeln("############ Stats ############");
                $output->writeln("Dates: [{$beginDate->format('Y-m-d H:i:s')}, {$endDate->format('Y-m-d H:i:s')}]");
                $output->writeln('Sessions with security questions: ' . \count($updateSessionsWithSecurityQuestionsBySessionIdMap));
                $output->writeln('Sessions with security questions answered: ' . (\count($updateSessionsWithSecurityQuestionsBySessionIdMap) - \count($unansweredSessionsList)));
                $output->writeln('Sessions with security questions unanswered: ' . \count($unansweredSessionsList));
                $output->writeln("Unanswered providers top: \n" . \json_encode($unansweredAccountsProvidersTop, \JSON_PRETTY_PRINT));
                $output->writeln("Unanswered platforms: \n" . \json_encode($unansweredAccountsByPlatform, \JSON_PRETTY_PRINT));
                $output->writeln("Answered providers top: \n" . \json_encode($answeredAccountsProvidersTop, \JSON_PRETTY_PRINT));
                $output->writeln("Answered platforms: \n" . \json_encode($answeredAccountsByPlatform, \JSON_PRETTY_PRINT));
            } finally {
                $this->logger->debug('clearing scroll contexts...');

                foreach ($this->scrollIds as $scrollId) {
                    $this->client->clearScroll(['scroll_id' => $scrollId]);
                }
            }

            return 0;
        }

        private function query(string $query, \DateTimeInterface $startTime, \DateTimeInterface $endTime, int $reponsePageSize): iterable
        {
            $query = \preg_replace('/[\r\n]/', ' ', $query);
            $response = $this->client->search([
                "index" => "logstash-*",
                "size" => $reponsePageSize,
                'scroll' => '5m',
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

            yield from it($this->scroll($response, $query, $reponsePageSize))
                ->map(fn (array $doc) => $doc['_source']);
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

        private function scroll(array $response, string $query, int $responsePageSize): iterable
        {
            $result = $response['hits']['hits'] ?? [];
            $resultCount = \count($result);
            $page = 1;
            $this->scrollIds[] = $response['_scroll_id'];

            $logHelper = function (array $hitsList) use (&$page, $query, &$resultCount) {
                [$minDate, $maxDate] =
                    it($hitsList)
                    ->map(fn (array $hit) => $hit['_source']['@timestamp'])
                    ->bounds();

                $this->logger->debug(
                    "query: " . \substr($query, 0, 100) . "..." .
                    ", loaded: {$resultCount} documents" .
                    ", page: {$page}" .
                    ", from: {$minDate}" .
                    ", to: {$maxDate}"
                );
            };

            $logHelper($result);

            yield from $result;

            while (\count($result) >= $responsePageSize) {
                $response = $this->client->scroll([
                    'scroll_id' => $response['_scroll_id'],
                    'scroll' => '5m',
                ]);
                $result = $response['hits']['hits'] ?? [];
                $resultCount += \count($result);
                $page++;
                $logHelper($result);

                yield from $result;
            }

            $this->client->clearScroll(['scroll_id' => \array_pop($this->scrollIds)]);
        }
    }
}

namespace AwardWallet\MainBundle\Command\AccountUpdaterMissingSecurityAnswer {
    class SecurityAnswerNeeded
    {
        public \DateTimeImmutable $timestamp;
        public int $accountId;
        public string $loyaltyRequestId;

        public function __construct(\DateTimeImmutable $timestamp, int $accountId, string $loyaltyRequestId)
        {
            $this->timestamp = $timestamp;
            $this->accountId = $accountId;
            $this->loyaltyRequestId = $loyaltyRequestId;
        }
    }

    class UpdaterSessionAccount
    {
        public const MOBILE = 'mobile';
        public const DESKTOP = 'desktop';

        public \DateTimeImmutable $timestamp;
        public int $accountId;
        public string $sessionId;
        public ?string $provider;
        public bool $answered;
        public ?string $requestId;
        public ?string $platform;
        public string $loyaltyRequestId;

        public function __construct(\DateTimeImmutable $timestamp, int $accountId, string $sessionId, string $loyaltyRequestId, ?string $provider = null, bool $answered = false, ?string $requestId = null, ?string $platform = null)
        {
            $this->timestamp = $timestamp;
            $this->accountId = $accountId;
            $this->sessionId = $sessionId;
            $this->provider = $provider;
            $this->answered = $answered;
            $this->requestId = $requestId;
            $this->platform = $platform;
            $this->loyaltyRequestId = $loyaltyRequestId;
        }
    }

    class UpdaterSessionRequest
    {
        public const MOBILE = 'mobile';
        public const DESKTOP = 'desktop';

        public string $sessionId;
        public string $requestId;
        public ?string $platform;
        public bool $answered;

        public function __construct(string $sessionId, string $requestId, ?string $platform = null, bool $answered = false)
        {
            $this->sessionId = $sessionId;
            $this->requestId = $requestId;
            $this->platform = $platform;
            $this->answered = $answered;
        }
    }
}
