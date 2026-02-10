<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\LockWrapper;
use AwardWallet\MainBundle\Updater\Event\AbstractEvent;
use AwardWallet\MainBundle\Updater\Event\DebugEvent;
use AwardWallet\MainBundle\Updater\Event\FailEvent;
use AwardWallet\MainBundle\Updater\Event\LoggableEventContextInterface;
use AwardWallet\MainBundle\Updater\Formatter\FormatterInterface;
use AwardWallet\MainBundle\Updater\Options\ClientPlatform;
use AwardWallet\MainBundle\Updater\Plugin\AccessPlugin;
use AwardWallet\MainBundle\Updater\Plugin\CheckItinerariesPlugin;
use AwardWallet\MainBundle\Updater\Plugin\ClientCheckV3Plugin;
use AwardWallet\MainBundle\Updater\Plugin\FailPlugin;
use AwardWallet\MainBundle\Updater\Plugin\GroupCheckPlugin;
use AwardWallet\MainBundle\Updater\Plugin\LimitPlugin;
use AwardWallet\MainBundle\Updater\Plugin\LocalPasswordPlugin;
use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;
use AwardWallet\MainBundle\Updater\Plugin\PluginInterface;
use AwardWallet\MainBundle\Updater\Plugin\ResultPlugin;
use AwardWallet\MainBundle\Updater\Plugin\ServerCheckPlugin;
use AwardWallet\MainBundle\Updater\Plugin\WaitEmailOTCPlugin;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Duration\Duration;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class UpdaterSession implements MasterInterface
{
    /** update session storage lifetime */
    public const UPDATE_SESSION_RETRY = 10;
    public const UPDATER_SESSION_TTL_SECONDS = 60 * 3 * self::UPDATE_SESSION_RETRY;

    /** max events in response */
    public const EVENTS_RESPONSE_LIMIT = 100;
    private const PLUGINS_ORDER = [
        LimitPlugin::ID,
        AccessPlugin::ID,
        LocalPasswordPlugin::ID,
        CheckItinerariesPlugin::ID,
        ClientCheckV3Plugin::ID,
        ServerCheckPlugin::ID,
        GroupCheckPlugin::ID,
        WaitEmailOTCPlugin::ID,
        ResultPlugin::ID,
        FailPlugin::ID,
    ];

    private int $timeout;
    private EntityManagerInterface $entityManager;
    private FormatterInterface $formatter;

    /**
     * @var AbstractEvent[]
     */
    private array $events = [];
    private string $key;
    /**
     * @var PluginInterface[]
     */
    private array $plugins;
    /**
     * @var AccountState[]
     */
    private array $accounts = [];
    private bool $stateLoaded = false;
    private \Memcached $memcached;
    private LoggerInterface $logger;
    /**
     * @var array{
     *     __local_password_v3_wait_map: array<int, Duration>,
     *     __v3_isolated_check_wait_map: array<int, bool>,
     *     __v3_isolated_check_switched_to_browser: bool
     *     ...
     * }
     */
    private array $options = [];
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    /**
     * @var array - ids of removed accounts on the end of tick
     */
    private array $removedAccounts = [];
    private LockWrapper $lockWrapper;
    private CacheManager $cacheManager;
    private ContextAwareLoggerWrapper $contextLogger;
    private ExtensionV3LocalPasswordWaitMapOps $extensionV3LocalPasswordWaitMapOps;

    /**
     * @param iterable<PluginInterface> $plugins
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        \Memcached $memcached,
        LockWrapper $lockWrapper,
        CacheManager $cacheManager,
        LoggerInterface $statLogger,
        FormatterInterface $formatter,
        AuthorizationCheckerInterface $authorizationChecker,
        ExtensionV3LocalPasswordWaitMapOps $extensionV3LocalPasswordWaitMapOps,
        int $updaterTimeout,
        iterable $plugins
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
        $this->formatter->setMaster($this);
        $this->memcached = $memcached;
        $this->logger = $statLogger;
        $this->timeout = $updaterTimeout;
        $this->loadPlugins($plugins, \array_flip(self::PLUGINS_ORDER));
        $this->options[Option::CHECK_TRIPS] = false;
        $this->options[Option::SOURCE] = 'group';
        $this->options[Option::CHECK_PROVIDER_GROUP] = true;
        $this->options[Option::EXTENSION_INSTALLED] = false;
        $this->options[Option::EXTENSION_DISABLED] = false;
        $this->options[Option::EXTENSION_V3_SUPPORTED] = false;
        $this->options[Option::EXTENSION_V3_INSTALLED] = false;
        $this->options[Option::BROWSER_SUPPORTED] = false;
        $this->options[Option::DEBUG] = true;
        $this->options[Option::EXTRA] = [];
        $this->options[Option::ASYNC] = false;
        $this->options[Option::CLIENT_PLATFORM] = ClientPlatform::DESKTOP;
        // internal
        $this->options[InternalOptions::V3_LOCAL_PASSWORD_WAIT_MAP] = [];
        $this->options[InternalOptions::V3_ISOLATED_CHECK_WAIT_MAP] = [];
        $this->options[InternalOptions::V3_ISOLATED_CHECK_SWITCHED_TO_BROWSER] = false;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->lockWrapper = $lockWrapper;
        $this->cacheManager = $cacheManager;
        $this->contextLogger =
            (new ContextAwareLoggerWrapper($statLogger))
            ->withClass(self::class)
            ->withTypedContext();
        $this->extensionV3LocalPasswordWaitMapOps = $extensionV3LocalPasswordWaitMapOps;
    }

    /**
     * @param int|string $startKey
     */
    public function startLockSafe(Usr $user, $startKey, array $accountIds, array $options)
    {
        $startLockCacheKey = UpdaterSessionManager::createStartLockCacheKey(
            $user,
            (string) $startKey,
            $accountIds,
            $options
        );
        $startResponseCacheKey = UpdaterSessionManager::createStartResponseCacheKey(
            $user,
            (string) $startKey,
            $accountIds,
            $options
        );

        return $this->cacheManager->load((new CacheItemReference(
            $startResponseCacheKey,
            [],
            function () use ($options, $startLockCacheKey, $accountIds, $startKey) {
                return $this->lockWrapper->wrap(
                    $startLockCacheKey,
                    function () use ($options, $accountIds, $startKey) {
                        foreach ($options as $optionName => $optionValue) {
                            $this->setOption($optionName, $optionValue);
                        }

                        $ret = $this->start($accountIds);
                        $ret->startKey = $startKey;

                        return $ret;
                    },
                    self::UPDATER_SESSION_TTL_SECONDS
                );
            }
        ))->setExpiration(self::UPDATER_SESSION_TTL_SECONDS));
    }

    /**
     * @internal
     */
    public function start(array $accountIds): StartResponse
    {
        $key = StringHandler::getRandomString(ord('a'), ord('z'), 30);

        return $this->startWithKey($key, $accountIds);
    }

    /**
     * @internal
     */
    public function startWithKey(string $key, array $accountIds): StartResponse
    {
        $this->events = [];
        $this->accounts = [];
        $this->key = $key;

        if ($this->options[Option::SOURCE] == 'trips') {
            $this->options[Option::CHECK_TRIPS] = true;
        }

        $this->log(null, 'Start. Key: ' . $this->key);
        $this->saveState();

        return new StartResponse($this->key, $this->tick($this->key, 0, $accountIds));
    }

    /**
     * @param string $key
     * @param int $eventIndex
     * @param list<AddAccount>|list<int> $addAccounts
     * @param list<int> $removeAccounts
     * @param list<int> $refuseLocalPasswords
     * @return list<AbstractEvent>
     */
    public function tick(
        $key,
        $eventIndex,
        array $addAccounts = [],
        array $removeAccounts = [],
        array $refuseLocalPasswords = []
    ): array {
        $tickLogSpan = $this->contextLogger->span(['updater_session_key' => $key]);

        $this->key = $key;
        $this->loadState();

        if ($addAccounts) {
            if (!($addAccounts[0] instanceof AddAccount)) {
                $addAccounts =
                    it($addAccounts)
                        ->map(fn ($accountId) => AddAccount::createLowPriority((int) $accountId))
                        ->toArray();
            }

            $this->addAccounts($addAccounts);
        }

        $accountIdsOnStart = array_keys($this->accounts);

        if ($removeAccounts) {
            $this->removeAccounts($removeAccounts);
        }

        if ($refuseLocalPasswords) {
            $this->extensionV3LocalPasswordWaitMapOps->removeAccounts($this, $refuseLocalPasswords);
        }

        $breakOnUnchangedStates = false;

        for ($n = 0; $n < 20; $n++) {
            $pluginStates = $this->getPluginStates();

            if (!empty($this->accounts) && is_array($this->accounts)) {
                foreach ($this->plugins as $pluginId => $plugin) {
                    $statesWithActivePlugin = \array_filter(
                        $this->accounts,
                        fn (AccountState $state) => $state->getActivePlugin() == $pluginId
                    );
                    $plugin->tick($this, $statesWithActivePlugin);
                }
            }

            if ($pluginStates == $this->getPluginStates()) {
                $breakOnUnchangedStates = true;

                break;
            }
        }

        $this->log(null, 'Tick. Accounts: ' . count($this->accounts) . ', events: ' . count($this->events));
        $this->contextLogger->info('Tick retries', [
            'tick_retries' => $n,
            'break_on_unchanged_states' => $breakOnUnchangedStates,
        ]);

        foreach ($this->plugins as $plugin) {
            $plugin->postTick($this, $this->accounts);
        }

        $this->saveState();
        $this->removedAccounts = array_diff($accountIdsOnStart, array_keys($this->accounts));

        $formattedEvents = $this->formatter->format(array_slice($this->events, $eventIndex, self::EVENTS_RESPONSE_LIMIT, true));
        unset($tickLogSpan);

        return $formattedEvents;
    }

    /**
     * @param AddAccount[] $addAccounts
     */
    public function add(string $key, array $addAccounts = []): void
    {
        $this->key = $key;

        if ($addAccounts) {
            $this->loadState();
            $this->addAccounts($addAccounts);
            $this->saveState();
        }
    }

    public function addEvent(AbstractEvent $event)
    {
        if (
            isset($event->accountId)
            && \is_scalar($event->accountId)
        ) {
            $logContext = [
                'account_id' => $event->accountId,
                'event_class' => \get_class($event),
                'event_type' => $event->type,
            ];

            if ($event instanceof LoggableEventContextInterface) {
                $logContext = \array_merge($logContext, $event->getLogContext());
            }

            $this->contextLogger->info('updater event: ' . $event->type, $logContext);
        }

        $this->events[count($this->events) + 1] = $event;
    }

    public function removeAccount(Account $account)
    {
        unset($this->accounts[$account->getAccountid()]);
    }

    public function saveState()
    {
        $state = $this->getState();
        $cacheData = \json_encode($state);
        $this->memcached->set(
            $cacheKey = $this->getCacheKey(),
            $cacheData,
            $ttl = ((self::UPDATE_SESSION_RETRY + 5) * $this->timeout)
        );

        $saveLogSpan = $this->contextLogger->span([
            'updater_session_key' => $this->key,
            'cacheKey' => $cacheKey,
        ]);

        if (\Memcached::RES_SUCCESS !== $this->memcached->getResultCode()) {
            $this->contextLogger->critical($error = 'Failed to store updater cache', [
                'updaterError' => true,
                'memcachedError' => $this->memcached->getResultMessage(),
            ]);

            throw new UpdaterStateException($error);
        }

        $this->contextLogger->info('Updater cache stored', [
            'cache_data_length' => \strlen($cacheData),
            'cache_data_ttl' => $ttl,
        ]);
        $this->stateLoaded = true;
        unset($saveLogSpan);

        return $state;
    }

    /**
     * @param Option::*|InternalOptions::* $option
     * @throws \UnexpectedValueException
     */
    public function getOption(string $option, $defaultValue = null)
    {
        if (!\array_key_exists($option, $this->options)) {
            if (\func_num_args() === 1) {
                throw new \UnexpectedValueException(sprintf('Updater: unexpected option "%s"', $option));
            }

            return $defaultValue;
        }

        return $this->options[$option];
    }

    /**
     * @param Option::*|InternalOptions::* $option
     */
    public function setOption($option, $value): self
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * @param string $message
     */
    public function log(?Account $account = null, $message = '')
    {
        if ($this->getOption(Option::DEBUG)) {
            $this->addEvent(new DebugEvent($account ? $account->getAccountid() : 0, $message));
        }
    }

    public function isEmptyQueue()
    {
        return empty($this->accounts) || !count($this->accounts);
    }

    /**
     * @return AccountState[]
     */
    public function getAccounts(): array
    {
        return $this->accounts;
    }

    public function getKey(): string
    {
        if (!isset($this->key)) {
            throw new \RuntimeException('Session is not initialized!');
        }

        return $this->key;
    }

    public function getRemovedAccounts(): array
    {
        return $this->removedAccounts;
    }

    protected function getState()
    {
        $result = [];
        $result['options'] = serialize($this->options);
        $result['events'] = serialize($this->events);

        if (!empty($this->accounts) && is_array($this->accounts)) {
            $result['accounts'] = array_combine(array_keys($this->accounts), array_map(function (AccountState $account) {
                return $account->saveState();
            }, $this->accounts));
        } else {
            $result['accounts'] = [];
        }

        return $result;
    }

    protected function loadState()
    {
        if ($this->stateLoaded) {
            return;
        }

        $cacheData = $this->memcached->get($cacheKey = $this->getCacheKey());
        $loadLogSpan = $this->contextLogger->span([
            'updater_session_key' => $this->key,
            'cacheKey' => $cacheKey,
        ]);

        if (\Memcached::RES_SUCCESS !== $this->memcached->getResultCode()) {
            $this->contextLogger->warning($error = 'Failed to load updater cache', [
                'updaterError' => true,
                'memcachedError' => $this->memcached->getResultMessage(),
            ]);

            throw new UpdaterStateException($error);
        }

        $this->contextLogger->info('Loaded updater cache', [
            'cache_data_length' => \strlen($cacheData),
        ]);
        $data = @json_decode($cacheData, true);

        if (json_last_error() === JSON_ERROR_NONE && !is_array($data)) {
            $this->contextLogger->critical($error = 'Failed to load updater cache', [
                'updaterError' => true,
                'cacheData' => substr($cacheData, 0, 256),
                'cacheDataLength' => strlen($cacheData),
                'jsonErrorCode' => json_last_error(),
                'jsonError' => json_last_error_msg(),
            ]);

            throw new UpdaterStateException($error);
        }

        $this->options = unserialize($data['options']);
        $this->events = unserialize($data['events']);

        $accounts = $data['accounts'];

        if (!empty($accounts) && is_array($accounts)) {
            $entities = $this->loadAccounts(array_keys($accounts));

            foreach ($entities as $account) {
                /** @var Account $account */
                if (!isset($this->accounts[$account->getAccountid()])) {
                    $this->accounts[$account->getAccountid()] = new AccountState($account);

                    if (array_key_exists($account->getAccountid(), $accounts)) {
                        $this->accounts[$account->getAccountid()]->loadState($accounts[$account->getAccountid()]);
                    }
                }
            }
        }

        $this->stateLoaded = true;
        unset($loadLogSpan);
    }

    protected function getCacheKey()
    {
        return "update_" . $this->tokenStorage->getToken()->getUser()->getUserid() . "_{$this->key}";
    }

    /**
     * @param AddAccount[] $accountAdds
     */
    private function addAccounts(array $accountAdds): void
    {
        $addLogSpan = $this->contextLogger->span(['updater_session_key' => $this->key]);

        /** @var array<int, AddAccount> $accountIdsMap */
        $accountIdsMap =
            it($accountAdds)
                ->reindex(fn (AddAccount $accountAdd) => $accountAdd->getAccountId())
                ->toArrayWithKeys();
        $accountIds = \array_keys($accountIdsMap);
        $accounts = $this->loadAccounts($accountIds);
        $notFound = array_diff($accountIds, array_map(fn (Account $account) => $account->getAccountid(), $accounts));

        foreach ($notFound as $accountId) {
            // not found in database
            $this->addEvent(new FailEvent($accountId, 'updater2.messages.fail.not-found'));
        }

        $isImpersonated = $this->authorizationChecker->isGranted('ROLE_IMPERSONATED');
        $needSort = false;
        $accountsNew = $this->accounts;
        $newAccountIdx = \count($this->accounts);

        foreach ($accounts as $account) {
            /** @var Account $account */
            if (isset($this->accounts[$account->getAccountid()])) {
                continue;
            }

            $updateLimit = $isImpersonated ? null : $account->getDailyUpdateLimit($this->tokenStorage->getToken()->getUser());
            $this->contextLogger->info('UpdaterSessionAccount', [
                'AccountID' => $account->getAccountid(),
                'isImpersonated' => $isImpersonated,
                'updateLimit' => $updateLimit,
            ]);
            $this->log($account, 'Add Account');

            $state = new AccountState($account);
            $state->pushPlugin(FailPlugin::ID);
            $state->pushPlugin(ResultPlugin::ID, [
                'accountData' => [
                    'Balance' => $account->getBalance(),
                    'LastBalance' => $account->getLastbalance(),
                ],
                'updateLimit' => $updateLimit,
            ]);

            $state->pushPlugin(WaitEmailOTCPlugin::ID);

            // we want to group-check only newly added or modified
            if ($account->isDirty() && $this->options[Option::CHECK_PROVIDER_GROUP]) {
                $this->log($account, 'Group Check Enable');
                $state->pushPlugin(GroupCheckPlugin::ID);
            }
            $state->pushPlugin(ServerCheckPlugin::ID);
            $state->pushPlugin(ClientCheckV3Plugin::ID);
            $state->pushPlugin(CheckItinerariesPlugin::ID);
            $state->pushPlugin(LocalPasswordPlugin::ID);
            $state->pushPlugin(AccessPlugin::ID);
            $state->pushPlugin(LimitPlugin::ID, [
                'updateLimit' => $updateLimit,
            ]);

            $addAccount = $accountIdsMap[$account->getAccountid()];
            $state->setSharedValue('add_priority', [$addAccount->getPriority(), $newAccountIdx++]);
            $this->extensionV3LocalPasswordWaitMapOps->removeAccounts($this, [$account->getId()]);

            if ($addAccount->getPriority() === AddAccount::HIGH_PRIORITY) {
                $needSort = true;
            }

            $accountsNew[$account->getAccountid()] = $state;
        }

        if ($needSort) {
            $accountsKeys = \array_flip(\array_keys($accountsNew));
            \uasort($accountsNew, function (AccountState $stateA, AccountState $stateB) use ($accountsKeys) {
                [$priorityA, $ascIdxA] =
                        $stateA->getSharedValue('add_priority')
                    ?? [AddAccount::LOW_PRIORITY, $accountsKeys[$stateA->account->getId()]];
                [$priorityB, $ascIdxB] =
                        $stateB->getSharedValue('add_priority')
                    ?? [AddAccount::LOW_PRIORITY, $accountsKeys[$stateB->account->getId()]];

                return
                    ($priorityB <=> $priorityA) // high priority first
                        ?: ($ascIdxA <=> $ascIdxB); // FIFO order for index
            });
        }

        $this->accounts = $accountsNew;
        unset($addLogSpan);
    }

    private function loadAccounts(array $accountIds)
    {
        $accounts = $this->entityManager
            ->createQuery('SELECT a FROM AwardWallet\MainBundle\Entity\Account a WHERE a.accountid IN (:accountIds)')
            ->setParameter('accountIds', $accountIds)
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();

        usort($accounts, function (Account $a1, Account $a2) use ($accountIds) {
            $index1 = array_search($a1->getAccountid(), $accountIds);
            $index2 = array_search($a2->getAccountid(), $accountIds);

            return $index1 - $index2;
        });

        return $accounts;
    }

    private function getPluginStates()
    {
        if (!empty($this->accounts) && is_array($this->accounts)) {
            return array_map(function (AccountState $state) {
                return $state->getActivePlugin();
            }, $this->accounts);
        }

        return [];
    }

    /**
     * @param iterable<PluginInterface> $plugins
     * @param list<string> $pluginsOrder
     */
    private function loadPlugins(iterable $plugins, array $pluginsOrder): void
    {
        /** @var list<string> $failedPluginsList */
        $failedPluginsList = [];

        foreach ($plugins as $plugin) {
            $pluginId = $plugin->getId();

            if (\array_key_exists($pluginId, $pluginsOrder)) {
                $pluginsOrder[$pluginId] = $plugin;
            } else {
                $failedPluginsList[] = $pluginId;
            }
        }

        if ($failedPluginsList) {
            throw new \LogicException('Updater: failed to load plugin(s): ' . \implode(', ', $failedPluginsList));
        }

        $this->plugins = $pluginsOrder;
    }

    /**
     * @param list<int> $removeAccounts
     */
    private function removeAccounts(array $removeAccounts): void
    {
        /** @var array<int, AccountState> $accountsByAccountIdMap */
        $accountsByAccountIdMap =
            it($this->getAccounts())
            ->reindex(fn (AccountState $state) => $state->account->getId())
            ->map(fn (AccountState $state) => $state)
            ->toArrayWithKeys();

        foreach ($removeAccounts as $removeAccount) {
            if (isset($accountsByAccountIdMap[$removeAccount])) {
                $accountState = $accountsByAccountIdMap[$removeAccount];
                $this->removeAccount($accountState->account);
            }
        }
    }
}
