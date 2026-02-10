<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\StampedeProtector;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\ApiException;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use Clock\ClockInterface;
use Duration\Duration;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\minutes;
use function Duration\seconds;

class UserMailboxCounter
{
    private const CIRCUIT_BREAKER_CACHE_KEY = 'user_mailbox_counter_circuit_breaker_v2';
    private const THROTTLER_CACHE_KEY = 'user_mailbox_counter_throttler_v2_';
    private const STAMPEDE_BETA = 1.0;
    private EmailScannerApi $scannerApi;
    private LoggerInterface $logger;
    private MailboxOwnerHelper $mailboxOwnerHelper;
    private CacheManager $cacheManager;
    private \Throttler $throttler;
    private \Memcached $memcached;
    private ClockInterface $clock;
    private static Duration $LOCK_EXPIRATION;
    private static Duration $STAMPEDE_TIME_TO_MAKE_REQUEST;
    private StampedeProtector $stampedeProtector;

    public function __construct(
        EmailScannerApi $scannerApi,
        CacheManager $cacheManager,
        LoggerInterface $logger,
        MailboxOwnerHelper $mailboxOwnerHelper,
        \Memcached $memcached,
        ClockInterface $clock,
        StampedeProtector $stampedeProtector
    ) {
        $this->scannerApi = $scannerApi;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
        $this->mailboxOwnerHelper = $mailboxOwnerHelper;
        $this->throttler = new \Throttler(
            $memcached,
            10,
            6,
            10
        );
        $this->memcached = $memcached;
        $this->clock = $clock;
        self::$LOCK_EXPIRATION = minutes(5);
        self::$STAMPEDE_TIME_TO_MAKE_REQUEST = seconds(5);
        $this->stampedeProtector = $stampedeProtector;
    }

    public function invalidateCache(int $userId): void
    {
        $this->cacheManager->invalidateTags([
            Tags::getUserMailboxesKey($userId, false),
            Tags::getUserMailboxesKey($userId, true),
        ]);
    }

    public function total(int $userId, bool $validOnly = false): int
    {
        $byAgent = $this->countMailboxesByUserAgent($userId, $validOnly);

        return array_sum($byAgent);
    }

    public function byFamilyMember(int $userId, int $userAgentId): int
    {
        $byAgent = $this->countMailboxesByUserAgent($userId);

        return $byAgent[$userAgentId] ?? 0;
    }

    public function myOrFamilyMember(int $userId, ?int $userAgentId): int
    {
        if ($userAgentId === null) {
            return $this->onlyMy($userId);
        }

        return $this->byFamilyMember($userId, $userAgentId);
    }

    public function onlyMy(int $userId): int
    {
        $byAgent = $this->countMailboxesByUserAgent($userId);

        return $byAgent[null] ?? 0;
    }

    /**
     * @return array [userAgentId/null => mailboxCount]
     */
    private function countMailboxesByUserAgent(int $userId, bool $validOnly = false): array
    {
        $lockExpiry = $this->getLockExpiry();
        $wasLockObserved = null !== $lockExpiry;

        if (
            $wasLockObserved
            && !$this->canTryAgainEarly($lockExpiry)
        ) {
            return [];
        }

        try {
            $mailboxes = $this->cacheManager->load(new CacheItemReference(
                Tags::getUserMailboxesKey($userId, $validOnly),
                Tags::addTagPrefix(Tags::getUserMailboxesTags($userId)),
                function () use ($userId, $validOnly) {
                    $mailboxes = $this->scannerApi->listMailboxes(["user_" . $userId]);

                    return it($mailboxes)
                        ->reduce(function (array $result, Mailbox $mailbox) use ($validOnly) {
                            if ($validOnly && $mailbox->getState() !== Mailbox::STATE_LISTENING) {
                                return $result;
                            }

                            $owner = $this->mailboxOwnerHelper->getOwnerByUserData($mailbox->getUserData());
                            $index = $owner->isFamilyMember() ? $owner->getFamilyMember()->getId() : null;
                            $result[$index] = ($result[$index] ?? 0) + 1;

                            return $result;
                        }, []);
                }
            ));

            if ($wasLockObserved) {
                $this->unlockCircuitBreaker();
            }

            return $mailboxes;
        } catch (ApiException $e) {
            if (!$wasLockObserved && (null !== $this->getLockExpiry())) {
                // this thread did not observe the lock before, but another thread did lock while this thread was making the request
                return [];
            }

            $this->logger->critical($e->getMessage());

            if ($wasLockObserved || $this->isEmailApiUnstable()) {
                $this->logger->error('user mailbox counter: breaking the circuit');
                $this->lockCircuitBreaker(self::$LOCK_EXPIRATION);
            }

            return [];
        }
    }

    private function canTryAgainEarly(Duration $lockExpiry): bool
    {
        return $this->stampedeProtector->canRecomputeEarlyByExpiry(
            $lockExpiry,
            self::$STAMPEDE_TIME_TO_MAKE_REQUEST,
            self::STAMPEDE_BETA
        );
    }

    private function getLockExpiry(): ?Duration
    {
        $cache = $this->memcached->get(self::CIRCUIT_BREAKER_CACHE_KEY);

        return (false === $cache) ?
            null :
            $cache;
    }

    private function lockCircuitBreaker(Duration $expiration): void
    {
        $this->memcached->set(
            self::CIRCUIT_BREAKER_CACHE_KEY,
            $this->clock->current()->add(self::$LOCK_EXPIRATION),
            $expiration->getAsSecondsInt()
        );
    }

    private function unlockCircuitBreaker(): void
    {
        $this->memcached->delete(self::CIRCUIT_BREAKER_CACHE_KEY);
    }

    private function isEmailApiUnstable(): bool
    {
        return $this->throttler->getDelay(self::THROTTLER_CACHE_KEY) > 0;
    }
}
