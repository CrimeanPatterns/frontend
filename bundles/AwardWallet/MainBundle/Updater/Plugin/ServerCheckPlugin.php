<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Event\AddPasswordVaultEvent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Service\EntitySerializer;
use AwardWallet\MainBundle\Updater\AccountProgress;
use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\ClientCheckSlotsCalculator;
use AwardWallet\MainBundle\Updater\Event\ExtensionV3Event;
use AwardWallet\MainBundle\Updater\Event\FailEvent;
use AwardWallet\MainBundle\Updater\Event\StartProgressEvent;
use AwardWallet\MainBundle\Updater\Event\SwitchFromBrowserEvent;
use AwardWallet\MainBundle\Updater\Event\SwitchToBrowserEvent;
use AwardWallet\MainBundle\Updater\EventsChannelMigrator;
use AwardWallet\MainBundle\Updater\ExtensionV3IsolatedCheckWaitMapOps;
use AwardWallet\MainBundle\Updater\ExtensionV3LocalPasswordWaitMapOps;
use AwardWallet\MainBundle\Updater\ExtensionV3SessionMap;
use AwardWallet\MainBundle\Updater\ExtensionV3SupportLoader;
use AwardWallet\MainBundle\Updater\InternalOptions;
use AwardWallet\MainBundle\Updater\Option;
use AwardWallet\MainBundle\Updater\TickScheduler;
use AwardWallet\MainBundle\Updater\TimeoutResolver;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterSessionTickTask;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Duration\Duration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;
use function Duration\seconds;

/**
 * Class ServerCheckPlugin
 * this class will send accounts to wsdl for checking, with respect to threads limit.
 */
class ServerCheckPlugin extends AbstractPlugin
{
    use PluginIdentity;
    use GetProviderFromStateTrait;
    use NeedV3IsolatedCheckTrait;
    public const ID = 'serverCheck';
    public const SHARED_START_TIME_KEY = 'server_check_plugin_start_time';
    public const LOYALTY_REQUEST_ID_CONTEXT_KEY = 'LoyaltyRequestId';

    private UpdaterEngineInterface $engine;
    private EntitySerializer $entitySerializer;
    private ProviderRepository $providerRep;
    private EntityManagerInterface $em;
    private LocalPasswordsManager $localPasswordsManager;
    private AwTokenStorageInterface $tokenStorage;
    private \Memcached $memcached;
    private EventDispatcherInterface $eventDispatcher;
    private TickScheduler $tickScheduler;
    private AccountProgress $accountProgress;
    private LoggerInterface $logger;
    private BinaryLoggerFactory $check;
    private LockFactory $lockFactory;
    private ClockInterface $clock;
    private TimeoutResolver $timeoutResolver;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ExtensionV3SupportLoader $extensionV3SupportLoader;
    private ExtensionV3LocalPasswordWaitMapOps $extensionV3LocalPasswordWaitMapOps;
    private ExtensionV3IsolatedCheckWaitMapOps $extensionV3IsolatedCheckWaitMapOps;
    private ClientCheckSlotsCalculator $clientCheckSlotsCalculator;
    private EventsChannelMigrator $eventsChannelMigrator;
    private ExtensionV3SessionMap $extensionV3SessionMap;

    public function __construct(
        UpdaterEngineInterface $engine,
        AwTokenStorageInterface $tokenStorage,
        LocalPasswordsManager $localPasswordsManager,
        EntitySerializer $entitySerializer,
        ProviderRepository $providerRep,
        EntityManagerInterface $em,
        TimeoutResolver $timeoutResolver,
        \Memcached $memcached,
        EventDispatcherInterface $eventDispatcher,
        TickScheduler $tickScheduler,
        AccountProgress $accountProgress,
        LoggerInterface $logger,
        LockFactory $lockFactory,
        ClockInterface $clock,
        AuthorizationCheckerInterface $authorizationChecker,
        ExtensionV3SupportLoader $extensionV3SupportLoader,
        ExtensionV3LocalPasswordWaitMapOps $extensionV3LocalPasswordWaitMapOps,
        ExtensionV3IsolatedCheckWaitMapOps $extensionV3IsolatedCheckWaitMapOps,
        ClientCheckSlotsCalculator $clientCheckSlotsCalculator,
        EventsChannelMigrator $eventsChannelMigrator,
        ExtensionV3SessionMap $extensionV3SessionMap
    ) {
        $this->engine = $engine;
        $this->localPasswordsManager = $localPasswordsManager;
        $this->entitySerializer = $entitySerializer;
        $this->providerRepository = $providerRep;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->memcached = $memcached;
        $this->eventDispatcher = $eventDispatcher;
        $this->tickScheduler = $tickScheduler;
        $this->accountProgress = $accountProgress;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->withClass(self::class)
            ->withTypedContext();
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo()->uppercaseInfix();
        $this->lockFactory = $lockFactory;
        $this->clock = $clock;
        $this->timeoutResolver = $timeoutResolver;
        $this->authorizationChecker = $authorizationChecker;
        $this->extensionV3SupportLoader = $extensionV3SupportLoader;
        $this->extensionV3LocalPasswordWaitMapOps = $extensionV3LocalPasswordWaitMapOps;
        $this->extensionV3IsolatedCheckWaitMapOps = $extensionV3IsolatedCheckWaitMapOps;
        $this->clientCheckSlotsCalculator = $clientCheckSlotsCalculator;
        $this->eventsChannelMigrator = $eventsChannelMigrator;
        $this->extensionV3SessionMap = $extensionV3SessionMap;
    }

    /**
     * @psalm-type ToSend = array{state: AccountState, provider: Provider}
     * @param AccountState[] $accountStates
     */
    public function tick(MasterInterface $master, array $accountStates): void
    {
        $needV3IsolatedCheck = $this->needV3IsolatedCheck($master);
        $check = $this->check;
        /** @var array<string, bool> $extensionV3SupportMap */
        $extensionV3SupportMap = lazy(fn (): array => $this->extensionV3SupportLoader->loadV3SupportMap($master, $accountStates));
        $this->logger->pushContext(['updater_session_key' => $master->getKey()]);

        try {
            // handle client check, receive responses first to calc free threads
            /** @var list<ToSend> $toSendList */
            $toSendList = [];
            $sentBeforeCount = 0;

            foreach ($accountStates as $state) {
                $this->logger->pushContext(['account_id' => $state->account->getId()]);

                try {
                    $provider = $this->getProviderFromState($state);

                    if (empty($provider)) {
                        $this->doPopPlugin($master, $state);
                        $master->log($state->account, 'Unknown provider');

                        continue;
                    }

                    if (
                        (
                            in_array($provider->getCheckinbrowser(), [CHECK_IN_CLIENT, CHECK_IN_MIXED])
                            && $provider->getState() == PROVIDER_CHECKING_EXTENSION_ONLY
                            && !($extensionV3SupportMap[ExtensionV3SupportLoader::makeV3SupportMapKey($state->account, $provider)] ?? false)
                        )
                        || !$provider->getCancheck()
                    ) {
                        $this->doPopPlugin($master, $state);

                        continue;
                    }

                    if ($state->getSharedValue('checkedByExtension')) {
                        $this->doPopPlugin($master, $state);

                        continue;
                    }

                    $startTime = $state->getContextValue('startTime');

                    if (!empty($startTime)) {
                        $loyaltyRequestId = $state->getSharedValue(self::LOYALTY_REQUEST_ID_CONTEXT_KEY);

                        if (null === $loyaltyRequestId) {
                            // migration time only
                            $this->logger->info("Aborting check, no loyalty request id");
                            $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.updater'));
                            $master->removeAccount($state->account);

                            continue;
                        }

                        if ($state->account->getUser()->isFraud() && empty($this->memcached->get("fraud_check_" . $state->account->getAccountid()))) {
                            $checkResult = new \AccountCheckReport();
                            $checkResult->errorCode = ACCOUNT_INVALID_PASSWORD;
                            $checkResult->errorMessage = $this->getInvalidPasswordMessage($state->account);
                            \CommonCheckAccountFactory::manuallySave($state->account->getAccountid(), $checkResult, \CommonCheckAccountFactory::getDefaultOptions());
                            $this->accountProgress->finishLoyaltyRequest($loyaltyRequestId, $checkResult->errorMessage, ACCOUNT_INVALID_PASSWORD);
                        }

                        $checkProgress = $this->accountProgress->getLoyaltyRequestInfo($loyaltyRequestId);

                        if (
                            $check('check progress')->does('(exist AND code !== timeout)')
                            ->on($checkProgress !== null && $checkProgress->getCode() !== ACCOUNT_TIMEOUT)
                        ) {
                            $state->setSharedValue(AccountState::SHARED_STATE_ITINERARIES, $checkProgress->getItineraryCodes());
                            $this->doPopPlugin($master, $state);
                        } elseif (
                            $this->clock->isTimedOut($startTime, $this->timeoutResolver->resolveForProvider($provider))
                            || ($checkProgress !== null && $checkProgress->getCode() === ACCOUNT_TIMEOUT)
                        ) {
                            if ($checkProgress !== null && strpos($checkProgress->getMessage(), 'password') !== false) {
                                $master->addEvent(new FailEvent(
                                    $state->account->getAccountid(),
                                    'updater2.messages.fail.password-missing'
                                ));
                            } else {
                                $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.server-timeout'));
                            }

                            $this->doRemoveAccount($master, $state);
                        } else {
                            $sentBeforeCount++;
                        }
                    } else {
                        $toSendList[] = ['state' => $state, 'provider' => $provider];

                        if (
                            $needV3IsolatedCheck
                            && ($extensionV3SupportMap[ExtensionV3SupportLoader::makeV3SupportMapKey($state->account, $provider)] ?? false)
                        ) {
                            $this->extensionV3IsolatedCheckWaitMapOps->addAccount($master, $state->account->getId());
                        }
                    }
                } finally {
                    $this->logger->popContext();
                }
            }

            // send new accounts, if there are free threads
            /** @var array<int, array> $packetMap */
            $packetMap = [];
            /** @var array<array{AccountState, Duration}> $fraudPacket */
            $fraudPacket = [];
            $freeSlotsTotal = lazy(function () use ($sentBeforeCount): int {
                $user = $this->tokenStorage->getBusinessUser();
                $maxThreads = \AccountAuditor::getUserMaxThreads($user->getAccountlevel());

                return \min(
                    $this->engine->getUpdateSlots($user),
                    $maxThreads - $sentBeforeCount
                );
            });
            $freeSlotsExtensionV3 = lazy(fn (): int => $this->clientCheckSlotsCalculator->getFreeSlots(
                $accountStates,
                static fn (AccountState $state) => $state->getContextValue('ExtensionV3startTime')
            ));

            if ($needV3IsolatedCheck) {
                $toSendOriginalOrderMap =
                    it($toSendList)
                    ->flip()
                    ->mapKeys( /** @param ToSend $toSend */ static fn (array $toSend) => $toSend['state']->account->getId())
                    ->toArrayWithKeys();
                \usort(
                    $toSendList,
                    /**
                     * @param ToSend $a
                     * @param ToSend $b
                     */
                    static function (array $a, array $b) use ($extensionV3SupportMap, $toSendOriginalOrderMap) {
                        ['state' => $stateA, 'provider' => $providerA] = $a;
                        ['state' => $stateB, 'provider' => $providerB] = $b;
                        $aV3SupportKey = ExtensionV3SupportLoader::makeV3SupportMapKey($stateA->account, $providerA);
                        $bV3SupportKey = ExtensionV3SupportLoader::makeV3SupportMapKey($stateB->account, $providerB);

                        return
                            (($extensionV3SupportMap[$bV3SupportKey] ?? false) <=> ($extensionV3SupportMap[$aV3SupportKey] ?? false)) ?:
                                ($toSendOriginalOrderMap[$stateA->account->getId()] <=> $toSendOriginalOrderMap[$stateB->account->getId()]);
                    }
                );
            }

            foreach ($toSendList as $toSend) {
                // did not used extract for readability
                /** @var AccountState $state */
                $state = $toSend['state'];
                /** @var Provider $provider */
                $provider = $toSend['provider'];

                if ($freeSlotsTotal->getValue() > 0) {
                    $isV3Account = $extensionV3SupportMap[ExtensionV3SupportLoader::makeV3SupportMapKey($state->account, $provider)] ?? false;

                    if ($isV3Account && $freeSlotsExtensionV3->getValue() <= 0) {
                        continue;
                    }

                    if (!$state->account->getUser()->isFraud()) {
                        $packetMap[$state->account->getId()] = $this->prepareAccountArray(
                            $master,
                            $state->account,
                            $provider,
                            $state->checkIts,
                            $isV3Account
                        );
                        $this->logger->debug("preparing for wsdl: " . $state->account->getProviderid()->getCode() . "-" . $state->account->getLogin() . "-" . $state->account->getAccountid());
                    } else {
                        $this->logger->warning("fraud user, ignoring check request", ["AccountID" => $state->account->getId()]);
                        $fraudPseudoCheckTime = seconds(\random_int(10, max(10, round($provider->getAvgdurationwithoutplans() * 1.3))));
                        $this->memcached->set("fraud_check_" . $state->account->getAccountid(), "fraud", $fraudPseudoCheckTime->getAsSecondsInt());
                        $fraudPacket[] = $state;
                        $master->addEvent(new StartProgressEvent($state->account->getAccountid(), $state->account->getUXLastDuration($state->checkIts), $state->checkIts));
                        $state->setContextValue('startTime', $currentTime = $this->clock->current());
                        $state->setSharedValue(self::SHARED_START_TIME_KEY, $currentTime);
                        $state->setSharedValue(self::LOYALTY_REQUEST_ID_CONTEXT_KEY, \bin2hex(\random_bytes(16)));

                        if ($master->getOption(Option::ASYNC)) {
                            $this->tickScheduler->scheduleTick(
                                new UpdaterSessionTickTask($master->getKey()),
                                $fraudPseudoCheckTime->add(seconds(5))
                            );
                        }
                    }

                    $freeSlotsTotal = $freeSlotsTotal->map(fn (int $slots): int => $slots - 1);

                    if ($isV3Account) {
                        $freeSlotsExtensionV3 = $freeSlotsExtensionV3->map(fn (int $slots): int => $slots - 1);
                    }
                }
            }

            if ($packetMap) {
                $this->sendPacket($packetMap, $master, $accountStates, $needV3IsolatedCheck);
            }

            if (
                $fraudPacket
                && $master->getOption(Option::ASYNC)
            ) {
                $this->scheduleDeadlineTicks($master, $fraudPacket);
            }
        } finally {
            $this->logger->popContext();
        }
    }

    private function getInvalidPasswordMessage(Account $account)
    {
        $key = "provider_invalid_error_" . $account->getProviderid()->getProviderid();
        $cached = $this->memcached->get($key);

        if (!empty($cached)) {
            return $cached;
        }

        $messages = $this->em->getConnection()->executeQuery("
            select 
                ErrorMessage 
            from 
                Account 
            where 
                ProviderID = :providerId
                and ErrorCode = " . ACCOUNT_INVALID_PASSWORD . " and UserID <> :userId 
            order by 
                UpdateDate desc 
            limit 50",
            ["providerId" => $account->getProviderid()->getProviderid(), "userId" => $account->getUserid()->getUserid()]
        )->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($messages)) {
            $result = "Invalid Username or Password";
        } else {
            $result = $messages[array_rand($messages)];
        }
        $this->memcached->set($key, $result, rand(20, 80));

        return $result;
    }

    private function prepareAccountArray(MasterInterface $master, Account $account, Provider $provider, bool $checkIts, bool $isV3Check): array
    {
        // fixme doctrine bug with lazy loading
        $this->em->refresh($account);
        $account->getUserid()->getUsername();
        $data = array_merge(
            $this->entitySerializer->entityToArray($account->getUserid()),
            $this->entitySerializer->entityToArray($provider),
            $this->entitySerializer->entityToArray($account)
        );

        if (SAVE_PASSWORD_LOCALLY == $account->getSavepassword()) {
            $data['Pass'] = $this->localPasswordsManager->getPassword($account->getAccountid());
        }

        $data['LastCheckItDate'] = $data['LastCheckItDate'] ? $data['LastCheckItDate']->format('Y-m-d H:i:s') : '';
        $data['LastCheckHistoryDate'] = $data['LastCheckHistoryDate'] ? $data['LastCheckHistoryDate']->format('Y-m-d H:i:s') : '';
        $data['ID'] = $data['AccountID'];
        $data['AutoGatherPlans'] = $checkIts;
        $data['ProviderPasswordRequired'] = $account->getProviderid()->getPasswordrequired();
        $data['browserExtensionAllowed'] =
            $isV3Check
            && ($isGrantedUpdateClientV3 = $this->authorizationChecker->isGranted('UPDATE_CLIENT_V3', $account))
            && ($optionExtV3Installed = $master->getOption(Option::EXTENSION_V3_INSTALLED, false))
            && ($optionExtV3Supported = $master->getOption(Option::EXTENSION_V3_SUPPORTED, false));

        if ($isV3Check) {
            $this->logger->info('Prepare account V3 breakdown', [
                'browserExtensionAllowed_breakdown_map' => [
                    'isGrantedUpdateClientV3' => $isGrantedUpdateClientV3 ?? null,
                    'optionExtV3Installed' => $optionExtV3Installed ?? null,
                    'optionExtV3Supporter' => $optionExtV3Supported ?? null,
                ],
            ]);
        }

        return $data;
    }

    /**
     * @param list<AccountState> $accountStates
     */
    private function sendPacket(array $packet, MasterInterface $master, array $accountStates, bool $needV3IsolatedCheck): void
    {
        // CapitalCards unauth error
        $errors = [];

        foreach ($packet as $accountId => $accountFields) {
            if (
                (int) $accountFields['ProviderID'] === Provider::CAPITAL_ONE_ID
                && empty($accountFields['AuthInfo'])
                && $accountFields['Login2'] !== "CA"
            ) {
                $errors[$accountId] = 'capital-unauth';
                unset($packet[$accountId]);

                continue;
            }

            if (
                (int) $accountFields['ProviderID'] === Provider::BANKOFAMERICA_ID
                && empty($accountFields['AuthInfo'])
            ) {
                $errors[$accountId] = 'bankofamerica-unauth';
                unset($packet[$accountId]);

                continue;
            }

            if (
                !in_array((int) $accountFields['ProviderID'], [Provider::AA_ID, Provider::BANKOFAMERICA_ID, Provider::CAPITAL_ONE_ID])
                && $accountFields['ProviderPasswordRequired']
                && empty($accountFields['Pass'])
            ) {
                $errors[$accountId] = 'password-missing';
                unset($packet[$accountId]);

                continue;
            }

            $state = $accountStates[$accountId];

            if ($state->account->getProviderid()->getState() === PROVIDER_COLLECTING_ACCOUNTS) {
                $this->eventDispatcher->dispatch(
                    new AddPasswordVaultEvent(
                        $state->account->getProviderid()->getCode(),
                        $state->account->getLogin(),
                        $state->account->getPass(),
                        $state->account->getLogin2(),
                        $state->account->getLogin3(),
                        null,
                        "awardwallet",
                        [],
                        intval($accountId)
                    ),
                    AddPasswordVaultEvent::NAME
                );
            }
        }

        if (!empty($packet)) {
            try {
                $platform = $master->getOption(Option::PLATFORM);
            } catch (\UnexpectedValueException $_) {
                // migration time only
                $platform = UpdaterEngineInterface::SOURCE_DESKTOP;
            }

            $results = $this->engine->sendAccounts($packet, 0, $platform);

            foreach ($results as $result) {
                $state = $accountStates[$result->getAccountid()];
                $state->setSharedValue(self::LOYALTY_REQUEST_ID_CONTEXT_KEY, $result->getLoyaltyRequestId());

                if ($result->getBrowserExtensionSessionId()) {
                    if ($needV3IsolatedCheck && !$master->getOption(InternalOptions::V3_ISOLATED_CHECK_SWITCHED_TO_BROWSER, false)) {
                        $master->setOption(InternalOptions::V3_ISOLATED_CHECK_SWITCHED_TO_BROWSER, true);
                        $master->addEvent(new SwitchToBrowserEvent($this->eventsChannelMigrator->send($master->getKey())));
                    }

                    $state->setContextValue('ExtensionV3startTime', $this->clock->current());
                    $this->extensionV3SessionMap->setAccountId($result->getBrowserExtensionSessionId(), $state->account->getId());
                    $master->addEvent(new ExtensionV3Event(
                        $result->getAccountid(),
                        $result->getBrowserExtensionSessionId(),
                        $result->getBrowserExtensionConnectionToken(),
                        $state->account->getUXLastDuration($state->checkIts)
                    ));
                } else {
                    $this->extensionV3IsolatedCheckWaitMapOps->removeAccount($master, $state->account->getId());
                }
            }
        }

        foreach ($errors as $accountId => $errorCode) {
            $state = $accountStates[$accountId];
            $providerId = $state->getContextValue('providerId');

            if ($providerId) {
                $master->log($state->account, 'Group Check provider ' . $providerId . ' with error ' . $errorCode);
                $this->doPopPlugin($master, $state);
            } else {
                if ($errorCode == ACCOUNT_TIMEOUT) {
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.wsdl.timeout'));
                } elseif ($errorCode == ACCOUNT_ENGINE_ERROR) {
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.wsdl.engine-error'));
                } elseif ($errorCode === 'capital-unauth') {
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.capital-unauth'));
                } elseif ($errorCode === 'bankofamerica-unauth') {
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.bankofamerica-unauth'));
                } elseif ($errorCode === 'password-missing') {
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.password-missing'));
                } elseif (in_array($errorCode, [ACCOUNT_INVALID_PASSWORD])) {
                    continue; // invalid password error will be written to database, ignore it here
                } else {
                    $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.updater'));
                }

                $this->doRemoveAccount($master, $state);
            }
        }

        $statesWithProgress = [];

        foreach ($packet as $account) {
            $state = $accountStates[$account['AccountID']];

            if (
                !isset($errors[$state->account->getAccountid()])
                || (\ACCOUNT_INVALID_PASSWORD == $errors[$state->account->getAccountid()])
            ) {
                $statesWithProgress[] = $state;
                $state->setContextValue('startTime', $currentTime = $this->clock->current());
                $state->setSharedValue(self::SHARED_START_TIME_KEY, $currentTime);

                if (!$state->getContextValue('ExtensionV3startTime')) {
                    $master->addEvent(new StartProgressEvent($state->account->getAccountid(), $state->account->getUXLastDuration($state->checkIts), $state->checkIts));
                }
            }
        }

        if (
            $statesWithProgress
            && $master->getOption(Option::ASYNC)
        ) {
            $this->scheduleDeadlineTicks($master, $statesWithProgress);
        }
    }

    private function scheduleDeadlineTicks(MasterInterface $master, array $accountStates): void
    {
        $this->tickScheduler->scheduleDeadlineTick(
            $master->getKey(),
            $accountStates,
            fn (?Provider $provider) => $this->timeoutResolver->resolveForProvider($provider)
        );
    }

    private function doPopPlugin(MasterInterface $master, AccountState $state): void
    {
        $this->trySwitchFromBrowser($master, $state);
        $state->popPlugin();
    }

    private function trySwitchFromBrowser(MasterInterface $master, AccountState $state): void
    {
        if (!$master->getOption(InternalOptions::V3_ISOLATED_CHECK_SWITCHED_TO_BROWSER, false)) {
            return;
        }

        $wasActive = $this->extensionV3IsolatedCheckWaitMapOps->hasActive($master);
        $this->extensionV3IsolatedCheckWaitMapOps->removeAccount($master, $state->account->getId());
        $hasActive = $this->extensionV3IsolatedCheckWaitMapOps->hasActive($master);

        if ($wasActive && !$hasActive) {
            $master->addEvent(new SwitchFromBrowserEvent());
        }
    }

    private function doRemoveAccount(MasterInterface $master, AccountState $state): void
    {
        $this->trySwitchFromBrowser($master, $state);
        $master->removeAccount($state->account);
    }
}
