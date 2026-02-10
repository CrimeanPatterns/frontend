<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 24.03.16
 * Time: 10:56.
 */

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountPackageRequest;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckErrorResponse;
use AwardWallet\MainBundle\Loyalty\Resources\QueueInfoItem;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckBalancesCommand extends Command
{
    public const LOYALTY_CHECK_GROUP_ID = 61; // temporary, remove after full migrate to loyalty

    public const DEFAULT_TOTAL_QUEUE_SIZE = 5000;
    public const DEFAULT_PROVIDER_QUEUE_SIZE = 10;
    public const DEFAULT_QUERY_LIMIT = 1000;
    public const PACKAGE_MAX_SIZE = 20;
    public const CUSTOM_LIMITS = [
        1 /* 'aa' */ => 1000,
        //        87 /* 'chase' */ => 1000,
    ];
    public const CHUNK_SIZE = 500;
    public const MIN_PROVIDER_QUEUE_SIZE = 0;

    private const CACHE_PREFIX = "cbc_provider_queue4_";

    protected static $defaultName = 'aw:check-balances';

    /** @var ApiCommunicator */
    private $communicator;

    /** @var Converter */
    private $converter;

    /** @var Connection */
    private $unBuffConnection;

    /** @var Connection */
    private $connection;

    /** @var Logger */
    private $logger;

    /** @var int */
    private $providerMaxSize;

    /** @var AccountRepository */
    private $repository;

    /** @var int */
    private $queryLimit;

    /** @var \Memcached */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $statLogger;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    private \AwardWallet\MainBundle\Entity\Repositories\ParameterRepository $parameterRepository;

    public function __construct(ParameterRepository $parameterRepository, AccountRepository $repository, Converter $converter, LoggerInterface $logger, \Memcached $memcached, Connection $connection, EntityManagerInterface $entityManager, ApiCommunicator $communicator, Connection $unbufConnection, LoggerInterface $statLogger)
    {
        $this->parameterRepository = $parameterRepository;
        parent::__construct();
        $this->repository = $repository;
        $this->converter = $converter;
        $this->logger = $logger;
        $this->memcached = $memcached;
        $this->connection = $connection;
        $this->em = $entityManager;
        $this->communicator = $communicator;
        $this->unBuffConnection = $unbufConnection;
        $this->statLogger = $statLogger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check balances background command')
            ->addOption('total-queue-size', 'tq', InputOption::VALUE_OPTIONAL, 'partner total queue max size', self::DEFAULT_TOTAL_QUEUE_SIZE)
            ->addOption('provider-queue-size', 'pq', InputOption::VALUE_OPTIONAL, 'provider queue max size')
            ->addOption('query-limit', 'ql', InputOption::VALUE_OPTIONAL, 'db query accounts limit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paramRepo = $this->parameterRepository;
        $this->providerMaxSize = $input->getOption('provider-queue-size');

        if (empty($this->providerMaxSize)) {
            $this->providerMaxSize = $paramRepo->getParam(ParameterRepository::PROVIDER_QUEUE_SIZE, self::DEFAULT_PROVIDER_QUEUE_SIZE);
        }
        $this->queryLimit = empty($input->getOption('query-limit')) ? self::DEFAULT_QUERY_LIMIT : $input->getOption('query-limit');

        $this->logger->pushProcessor(function (array $record) {
            $record['extra']['worker'] = 'CheckBalancesCommand';

            return $record;
        });

        // current queue definition
        $queues = $this->communicator->GetQueueInfo()->getQueues();
        /** @var QueueInfoItem $queueItem */
        $excludeProviders = [];
        $totalQueueSize = 0;

        foreach ($queues as $queueItem) {
            if (!Converter::isBackgroundCheck($queueItem->getPriority())) {
                continue;
            }

            if ($queueItem->getItemsCount() >= self::MIN_PROVIDER_QUEUE_SIZE) {
                $excludeProviders[] = $queueItem->getProvider();
            }
            $totalQueueSize += $queueItem->getItemsCount();
        }
        $maxTotalQueue = $input->getOption('total-queue-size');

        if ($totalQueueSize >= $maxTotalQueue) {
            $this->logger->info("total queue is full, exiting");

            return 0;
        }
        $this->logger->info("total queue size: " . $totalQueueSize);

        $providers = $this->connection->executeQuery("select 
            ProviderID,
            Code
        from 
            Provider
        where
            (State >= " . PROVIDER_ENABLED . " OR State = " . PROVIDER_TEST . ")
            AND State not in(" . implode(", ", [PROVIDER_CHECKING_EXTENSION_ONLY, PROVIDER_CHECKING_OFF, PROVIDER_FIXING]) . ")
            AND CanCheck = 1
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $providerQueues = [[]];

        foreach ($providers as $provider) {
            if (in_array($provider['Code'], $excludeProviders)) {
                continue;
            }
            $providerQueues[] = $this->loadQueue($provider['ProviderID']);
        }

        $queue = array_merge(...$providerQueues);
        usort($queue, function (array $a, array $b) {
            $result = (int) isset(self::CUSTOM_LIMITS[$b['ProviderID']]) - (int) isset(self::CUSTOM_LIMITS[$a['ProviderID']]);

            if ($result === 0) {
                $result = (int) $a['NextCheckPriority'] - (int) $b['NextCheckPriority'];
            }

            if ($result === 0) {
                $result = strcmp($a['QueueDate'], $b['QueueDate']);
            }

            return $result;
        });

        $totalQueueSize = $this->sendQueue($queue, $totalQueueSize, $maxTotalQueue);

        $this->logger->info('done, queue size: ' . $totalQueueSize);

        return 0;
    }

    protected function sendPackage(array $packet)
    {
        $accountIds = array_map(function (array $row) { return $row['AccountID']; }, $packet);
        $accounts = $this->repository->findBy(["accountid" => $accountIds]);
        $now = time();
        $accounts = array_filter($accounts, function (Account $account) use ($now) {
            return $account->getQueuedate()->getTimestamp() <= $now;
        });

        if (empty($accounts)) {
            return 0;
        }

        $package = array_map(function (Account $account) {
            $priority = array_key_exists($account->getProviderid()->getProviderid(), self::CUSTOM_LIMITS)
                ? Converter::BACKGROUND_CHECK_REQUEST_PRIORITY_MEDIUM
                : Converter::BACKGROUND_CHECK_REQUEST_PRIORITY_MIN;
            $this->statLogger->info("sending to background check", ["AccountID" => (string) $account->getAccountid(), "Provider" => (string) $account->getProviderid()->getCode(), "Priority" => $priority]);

            return $this->converter->prepareCheckAccountRequest($account, null, $priority);
        }, $accounts);

        $request = (new CheckAccountPackageRequest())->setPackage($package);
        $response = $this->communicator->CheckAccountsPackage($request);

        if (!empty($response->getErrors())) {
            /** @var PostCheckErrorResponse $errorItem */
            foreach ($response->getErrors() as $errorItem) {
                $this->logger->warning('Check account package item error', [
                    'message' => $errorItem->getMessage(),
                    'userData' => $errorItem->getUserData(),
                ]);
            }
        }
        $this->logger->info(count($package) . ' accounts successfully sent to check');
        $this->connection->executeUpdate("UPDATE Account SET QueueDate = ADDDATE(NOW(), 7) WHERE AccountID IN (" . implode(", ", $accountIds) . ")");
        $this->em->clear();

        return count($package);
    }

    private function loadQueue($providerId)
    {
        $eof = $this->memcached->get("cbc_provider_eof_" . $providerId);

        if ($eof) {
            return [];
        }

        $nextTime = $this->memcached->get(self::CACHE_PREFIX . $providerId);

        if (!is_array($nextTime) || empty($nextTime)) {
            $nextTime = [];
        }

        $maxQueueSize = $this->getProviderMaxQueueSize($providerId);

        if (count($nextTime) < $maxQueueSize) {
            $loaded = $this->loadAccounts($providerId, self::CHUNK_SIZE);

            if (count($loaded) < self::CHUNK_SIZE) {
                $this->memcached->set("cbc_provider_eof_" . $providerId, true, 600);
            }
            $nextTime = array_merge($nextTime, $loaded);
        }

        return $nextTime;
    }

    private function getProviderMaxQueueSize($providerId)
    {
        if (array_key_exists($providerId, self::CUSTOM_LIMITS)) {
            return self::CUSTOM_LIMITS[$providerId];
        } else {
            return $this->providerMaxSize;
        }
    }

    /**
     * @return int[]
     */
    private function loadAccounts($providerId, $limit)
    {
        $sql = "
            SELECT
                a.AccountID,
                a.ProviderID,
                a.NextCheckPriority,
                a.QueueDate
            FROM
                Account a
            WHERE a.ProviderID = $providerId
            AND a.BackgroundCheck = 1
            AND a.QueueDate < now() 
            ORDER BY a.NextCheckPriority, a.QueueDate 
            LIMIT $limit
		";

        $result = $this->connection->executeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $this->logger->info("loaded " . count($result) . " accounts", ["ProviderID" => $providerId]);

        return $result;
    }

    private function sendQueue(array $queue, $totalQueueSize, $maxTotalQueue)
    {
        $this->logger->info("ready to send " . count($queue) . " accounts");
        $providerQueues = [];
        $packet = [];

        while (!empty($queue) && $totalQueueSize < $maxTotalQueue) {
            $account = array_shift($queue);
            $packet[] = $account;

            if (!isset($providerQueues[$account['ProviderID']])) {
                $providerQueues[$account['ProviderID']] = 0;
            }
            $providerQueues[$account['ProviderID']]++;

            if ($providerQueues[$account['ProviderID']] >= $this->getProviderMaxQueueSize($account['ProviderID'])) {
                $queue = $this->removeFromQueue($queue, $account['ProviderID']);
            }

            if (count($packet) >= self::PACKAGE_MAX_SIZE) {
                $totalQueueSize += $this->sendPackage($packet);
                $packet = [];
            }
        }
        $totalQueueSize += $this->sendPackage($packet);

        $this->logger->info("cached " . count($queue) . " accounts for next time");

        while (!empty($queue)) {
            $queue = $this->removeFromQueue($queue, $queue[0]['ProviderID']);
        }

        return $totalQueueSize;
    }

    private function removeFromQueue(array $queue, $providerId)
    {
        $reminder = [];
        $queue = array_filter($queue, function (array $item) use (&$reminder, $providerId) {
            if ($item['ProviderID'] == $providerId) {
                $reminder[] = $item;

                return false;
            } else {
                return true;
            }
        });
        $this->memcached->set(self::CACHE_PREFIX . $providerId, $reminder, 86400);

        return array_values($queue);
    }
}
