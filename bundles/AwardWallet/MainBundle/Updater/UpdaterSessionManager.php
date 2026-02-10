<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor\SymfonyContext;
use AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor\SymfonyEnvironmentExecutor;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\LockWrapper;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use Clock\ClockInterface;
use Duration\Duration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\seconds;

class UpdaterSessionManager
{
    public const MAX_ADDED_ACCOUNTS_COUNT = 3000;
    public const LOCK_TTL_SECONDS = 2 * 60; // 2 minutes
    public const MESSAGING_CHANNEL_PREFIX = '$update_session_';
    private const START_KEY_LOCK_CACHE_PREFIX = 'update_start_lock_';
    private const START_RESPONSE_CACHE_PREFIX = 'update_start_response_';
    private const MAX_TOTAL_TICK_COUNT = 1500;

    private UpdaterSessionFactory $updaterSessionFactory;
    private LockWrapper $lockWrapper;
    private ClientInterface $fugeClient;
    private UsrRepository $userRep;
    private SymfonyEnvironmentExecutor $symfonyEnvironmentExecutor;
    private AwTokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private RequestSerializer $requestSerializer;
    private CacheManager $cacheManager;
    private ClockInterface $clock;
    private Duration $UPDATER_SESSION_TTL;
    private UpdaterSessionStorage $updaterSessionStorage;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        UpdaterSessionFactory $updaterSessionFactory,
        CacheManager $cacheManager,
        LockWrapper $lockWrapper,
        ClientInterface $fugeCilent,
        UsrRepository $userRep,
        SymfonyEnvironmentExecutor $symfonyEnvironmentExecutor,
        AwTokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        RequestSerializer $requestSerializer,
        ClockInterface $clock,
        UpdaterSessionStorage $sessionStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        // constants
        $this->UPDATER_SESSION_TTL = seconds(UpdaterSession::UPDATER_SESSION_TTL_SECONDS);
        // dependencies
        $this->updaterSessionFactory = $updaterSessionFactory;
        $this->lockWrapper = $lockWrapper;
        $this->fugeClient = $fugeCilent;
        $this->userRep = $userRep;
        $this->symfonyEnvironmentExecutor = $symfonyEnvironmentExecutor;
        $this->tokenStorage = $tokenStorage;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->pushContext([Context::SERVER_MODULE_KEY => 'updater_session_manager'])
            ->setMessagePrefix('updater session manager: ')
            ->withTypedContext();
        $this->requestSerializer = $requestSerializer;
        $this->cacheManager = $cacheManager;
        $this->clock = $clock;
        $this->updaterSessionStorage = $sessionStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param int|string $startKey
     */
    public function startSessionLockSafe($startKey, string $client, string $type, array $optionsMap, array $accountIdsList, Request $request): StartResponse
    {
        $user = $this->tokenStorage->getUser();

        $startLockCacheKey = self::createStartLockCacheKey(
            $user,
            (string) $startKey,
            $accountIdsList,
            $optionsMap
        );
        $startResponseCacheKey = self::createStartResponseCacheKey(
            $user,
            (string) $startKey,
            $accountIdsList,
            $optionsMap
        );

        return $this->cacheManager->load((new CacheItemReference(
            $startResponseCacheKey,
            [],
            fn () =>
                $this->lockWrapper->wrap(
                    $startLockCacheKey,
                    function () use ($optionsMap, $accountIdsList, $startKey, $client, $type, $request) {
                        $ret = $this->startSession($client, $type, $optionsMap, $accountIdsList, $request);
                        $ret->startKey = $startKey;

                        return $ret;
                    },
                    $this->UPDATER_SESSION_TTL->getAsSecondsInt()
                )
        ))
            ->setExpiration($this->UPDATER_SESSION_TTL->getAsSecondsInt())
        );
    }

    /**
     * @internal
     */
    public function startSession(string $client, string $type, array $optionsMap, array $accountIdsList, Request $request): StartResponse
    {
        $updaterSession = $this->updaterSessionFactory->createSessionByType($type);

        foreach ($optionsMap as $optionName => $optionValue) {
            $updaterSession->setOption($optionName, $optionValue);
        }

        $updaterSession->setOption(Option::ASYNC, true);

        $sessionKey = StringHandler::getRandomString(\ord('a'), \ord('z'), 30);
        $user = $this->tokenStorage->getUser();
        $startResponse = $this->syncedSessionAction(
            $sessionKey,
            fn () => $this->doStartSession(
                $updaterSession,
                $request,
                $user,
                $type,
                $sessionKey,
                $accountIdsList
            )
        );
        $startResponse->events = [];
        $startResponse->socketInfo = $this->fugeClient->getClientData();
        $startResponse->socketInfo['channel'] = UpdaterSessionManager::createSessionChannelName($sessionKey);
        $startResponse->socketInfo['auth'] = [
            $startResponse->socketInfo['channel'] => [
                'sign' => $this->fugeClient->generateChannelSign($client, $startResponse->socketInfo['channel']),
                'info' => '',
            ],
        ];

        return $startResponse;
    }

    /**
     * @return bool whether synchronized tick was performed or lock failure has occurred
     */
    public function synchronizedTick(
        string $sessionKey,
        ?UserMessagesHandlerResult $userMessagesHandlerResult = null,
        ?string $serializedRequest = null
    ): bool {
        try {
            $this->syncedSessionAction(
                $sessionKey,
                fn () => $this->doTickSession(
                    $sessionKey,
                    $userMessagesHandlerResult,
                    $serializedRequest
                )
            );

            return true;
        } catch (LockConflictedException|LockAcquiringException $e) {
            $this->logger->info(\sprintf("Session '$sessionKey' is LOCKED. Retrying"), ['updater_session_key' => $sessionKey]);

            return false;
        }
    }

    public static function createStartLockCacheKey(Usr $user, string $startKey, array $accounts, array $options): string
    {
        return self::createStartCacheKey(
            self::START_KEY_LOCK_CACHE_PREFIX,
            $user,
            $startKey,
            $accounts,
            $options
        );
    }

    public static function createStartResponseCacheKey(Usr $user, string $startKey, array $accounts, array $options): string
    {
        return self::createStartCacheKey(
            self::START_RESPONSE_CACHE_PREFIX,
            $user,
            $startKey,
            $accounts,
            $options
        );
    }

    public static function createSessionChannelName(string $sessionKey): string
    {
        return self::MESSAGING_CHANNEL_PREFIX . $sessionKey;
    }

    protected function syncedSessionAction(string $sessionId, callable $callable)
    {
        $this->logger->pushContext(['updater_session_key' => $sessionId]);

        try {
            return $this->lockWrapper->wrap(
                UpdaterSessionManager::createSessionLockCacheKey($sessionId),
                $callable,
                self::LOCK_TTL_SECONDS
            );
        } finally {
            $this->logger->popContext();
        }
    }

    protected static function createStartCacheKey(string $prefix, Usr $user, string $startKey, array $accounts, array $options): string
    {
        $accountsString =
            it($accounts)
            ->map(fn ($account) => (string) $account)
            ->sort()
            ->toJSON();

        return $prefix . hash('sha256', $user->getId() . '_' . $startKey . '_' . $accountsString . '_' . \json_encode($options));
    }

    private function doStartSession(UpdaterSession $updaterSession, Request $request, Usr $user, string $type, string $sessionKey, array $accountIds = []): StartResponse
    {
        $this->updaterSessionStorage->linkUpdaterSessionToAccounts($accountIds, $sessionKey);
        $this->updaterSessionStorage->linkUpdaterSessionToUser($user->getId(), $sessionKey);
        $this->logger->info('Session first tick start. Event index: 0', ['updater_add_accounts' => $accountIds]);
        $lastTickTime = $this->clock->current();
        $startResponse = $updaterSession->startWithKey($sessionKey, $accountIds);
        $eventsMap = $startResponse->events;
        \end($eventsMap);
        $lastEventIndex = \key($eventsMap) ?? 0;
        $this->logger->info('Session first tick end. Event index: ' . $lastEventIndex);
        $notifyChannelTime = $this->clock->stopwatch(fn () => $this->notifyChannel($sessionKey, $startResponse->events));
        $updateStorageTime = $this->clock->stopwatch(fn () => $this->updaterSessionStorage->updateSessionData(
            $sessionKey,
            new SessionData(
                $type,
                $lastEventIndex,
                $lastTickTime,
                1,
                $user->getId(),
                $this->requestSerializer->serializeRequest($this->removeLocalPasswordsAttribute($request)),
                $this->getImpersonatorUserId(),
            )
        ));
        $this->logger->info("Session start timers", [
            'notify_channel_ms_int' => $notifyChannelTime->getAsMillisecondsInt(),
            'update_storage_ms_int' => $updateStorageTime->getAsMillisecondsInt(),
        ]);

        return $startResponse;
    }

    private function getImpersonatorUserId(): ?int
    {
        $token = $this->tokenStorage->getToken();

        if (!$token instanceof SwitchUserToken) {
            return null;
        }

        $origUser = $token->getOriginalToken()->getUser();

        if (!$origUser instanceof Usr) {
            return null;
        }

        return $origUser->getId();
    }

    private function removeLocalPasswordsAttribute(Request $origRequest): Request
    {
        $request = clone $origRequest;
        $request->attributes->remove(LocalPasswordsManager::ATTR_NAME);

        return $request;
    }

    private function doTickSession(
        string $sessionKey,
        ?UserMessagesHandlerResult $userMessagesHandlerResult = null,
        ?string $userProvidedSerializedRequest = null
    ): void {
        $this->logger->info('Loading session: ' . $sessionKey);
        $sessionData = $this->updaterSessionStorage->loadSessionData($sessionKey);

        if (null === $sessionData) {
            $this->logger->info("Session '{$sessionKey}' was not found in storage.");

            return;
        }

        $sessionType = $sessionData->getSessionType();
        $loadedEventIndex = $sessionData->getEventIndex();
        $lastTickTime = $sessionData->getLastTickTime();
        $userId = $sessionData->getUserId();
        $loadedSerializedRequest = $sessionData->getSerializedRequest();
        $impersonatorUserId = $sessionData->getImpersonatorUserId();
        $this->logger->pushContext(['userid' => $userId]);

        try {
            if ($sessionData->getTotalTickCount() >= self::MAX_TOTAL_TICK_COUNT) {
                $this->logger->critical("Session '{$sessionKey}' exceeded max total tick count");
                $this->updaterSessionStorage->removeSession($sessionKey);

                return;
            }

            $nowDate = $this->clock->current();
            $expirationDate = $lastTickTime->add($this->UPDATER_SESSION_TTL);

            if ($nowDate->greaterThan($expirationDate)) {
                $this->logger->info("Session '{$sessionKey}' is expired: (now) {$nowDate->scaleToSeconds()} > (expiration) {$expirationDate->scaleToSeconds()}, diff: {$nowDate->sub($expirationDate)->scaleToSeconds()}. Removing...");
                $this->updaterSessionStorage->removeSession($sessionKey);

                return;
            }

            $lastSerializedRequest = $userProvidedSerializedRequest ?? $loadedSerializedRequest;
            $user = $this->userRep->find($userId);

            if (!$user) {
                return;
            }

            $addAccounts = $userMessagesHandlerResult ? $userMessagesHandlerResult->addAccounts : [];
            $this->updaterSessionStorage->linkUpdaterSessionToAccounts($addAccounts, $sessionKey);
            $this->logger->info("Tick start. Session key: {$sessionKey}. Event index: {$loadedEventIndex}", [
                'updater_add_accounts' =>
                    it($addAccounts)
                    ->map(fn ($account) => $account instanceof AddAccount ?
                        $account->getAccountId() :
                        $account
                    )
                    ->toArray(),
                'updater_has_user_request' => isset($userProvidedSerializedRequest),
            ]);
            $lastTickTime = $this->clock->current();
            $symfonyContext = new SymfonyContext($user, $this->requestSerializer->deserializeRequest($lastSerializedRequest), $impersonatorUserId);
            [$events, $removedAccounts] = $this->symfonyEnvironmentExecutor->process($symfonyContext, function () use ($sessionKey, $loadedEventIndex, $sessionType, $userMessagesHandlerResult) {
                $updaterSession = $this->updaterSessionFactory->createSessionByType($sessionType);

                return [
                    $updaterSession->tick(
                        $sessionKey,
                        $loadedEventIndex,
                        $userMessagesHandlerResult ? $userMessagesHandlerResult->addAccounts : [],
                        $userMessagesHandlerResult ? $userMessagesHandlerResult->removeAccounts : [],
                        $userMessagesHandlerResult ? $userMessagesHandlerResult->refuseLocalPasswords : []
                    ),
                    $updaterSession->getRemovedAccounts(),
                ];
            });
            $lastEventIndex = \array_key_last($events) ?? $loadedEventIndex;
            $this->logger->info(\sprintf("Tick end: {$sessionKey}. Event index: %d, diff: %d", $lastEventIndex, $lastEventIndex - $loadedEventIndex));

            if (!empty($removedAccounts)) {
                $this->updaterSessionStorage->unlinkUpdaterSessionFromAccounts($removedAccounts, $sessionKey);
            }

            $this->updaterSessionStorage->updateSessionData(
                $sessionKey,
                new SessionData(
                    $sessionType,
                    $lastEventIndex,
                    $lastTickTime,
                    $sessionData->getTotalTickCount() + 1,
                    $userId,
                    $lastSerializedRequest,
                    $impersonatorUserId
                )
            );
            $this->notifyChannel($sessionKey, $events);
        } finally {
            $this->logger->popContext();
        }
    }

    private function notifyChannel(string $sessionKey, array $events): void
    {
        $channel = UpdaterSessionManager::createSessionChannelName($sessionKey);

        // reasons for packeting:
        // 1. we need to publish in packets, because client-side updater will stop processing on some messages
        //    for example Security Question will send 2 events, and client updater will stop on first one
        //    packeting will force it to process entire packet
        // 2. performance, do not do round-trips to centrifuge
        $packet = [];

        foreach ($events as $eventIndex => $event) {
            $packet[] = [$eventIndex, $event];
        }
        $this->fugeClient->publish($channel, $packet);
    }

    private static function createSessionLockCacheKey(string $sessionId): string
    {
        return 'updater_session_lock_' . $sessionId;
    }
}
