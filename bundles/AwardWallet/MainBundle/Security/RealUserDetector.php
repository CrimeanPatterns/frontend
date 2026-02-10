<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class RealUserDetector
{
    private const INVALID_PASSWORD_CACHE_PREFIX = 'rud_invp_';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var UserMailboxCounter
     */
    private $mailboxCounter;

    public function __construct(LoggerInterface $logger, Connection $connection, \Memcached $memcached, UserMailboxCounter $mailboxCounter)
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->memcached = $memcached;
        $this->mailboxCounter = $mailboxCounter;
    }

    public function getScore(int $userId): RealUserDetectorResult
    {
        $result = new RealUserDetectorResult(
            $this->userRegistrationDateScore($userId),
            $this->validAccountsScore($userId),
            1 - $this->invalidPasswordsScore($userId),
            $this->hasMobileAppScore($userId),
            $this->providersScore($userId),
            // could use Usr.ValidMailboxesCount for perfomance reasons. updated daily in CacheMailboxInfoCommand
            min(1, $this->mailboxCounter->total($userId))
        );

        $this->logger->info("real user score: " . json_encode($result->toArray()), ["Score" => $result->getTotal(), "UserID" => $userId]);

        return $result;
    }

    /**
     * @internal
     */
    public function onAccountUpdated(AccountUpdatedEvent $event)
    {
        $this->logger->info("account updated, code: " . $event->getAccount()->getErrorcode(), ["UserID" => $event->getAccount()->getUser()->getId()]);

        if (in_array($event->getAccount()->getErrorcode(), [ACCOUNT_INVALID_PASSWORD, ACCOUNT_LOCKOUT, ACCOUNT_MISSING_PASSWORD, ACCOUNT_PREVENT_LOCKOUT, ACCOUNT_QUESTION, ACCOUNT_PROVIDER_ERROR])) {
            $this->logger->info("increasing invalid passwords counter", ["UserID" => $event->getAccount()->getUser()->getId()]);
            $this->getInvalidPasswordThrottler()->increment(self::INVALID_PASSWORD_CACHE_PREFIX . $event->getAccount()->getUser()->getId());
        }
    }

    private function userRegistrationDateScore(int $userId): float
    {
        $regDate = $this->connection->executeQuery("select CreationDateTime from Usr where UserID = ?", [$userId])->fetchColumn();

        if ($regDate === false) {
            return 0;
        }

        $realUserAfterSeconds = 30 * 86400;

        return min(1, (time() - strtotime($regDate)) / $realUserAfterSeconds);
    }

    private function validAccountsScore(int $userId): float
    {
        $oldValidAccounts = $this->connection->executeQuery("select count(AccountID) from Account where UserID = :userId and ErrorCode = :successErrorCode
        and CreationDate < adddate(now(), -10)", ["userId" => $userId, "successErrorCode" => ACCOUNT_CHECKED])->fetchColumn();

        $freshValidAccounts = $this->connection->executeQuery("select count(AccountID) from Account where UserID = :userId and ErrorCode = :successErrorCode
        and CreationDate >= adddate(now(), -10)", ["userId" => $userId, "successErrorCode" => ACCOUNT_CHECKED])->fetchColumn();

        // we require 3 old valid accounts
        return min(1, ($oldValidAccounts / 3) + ($freshValidAccounts / 10));
    }

    private function providersScore(int $userId): float
    {
        $providersCount = $this->connection->executeQuery("select count(distinct ProviderID) from Account where UserID = :userId", ["userId" => $userId])->fetchColumn();

        if ($providersCount <= 1) {
            return 0;
        }

        // we require 10 different providers
        return min(1, $providersCount / 10);
    }

    private function invalidPasswordsScore(int $userId): float
    {
        // 30 invalid passwords in 24 hours = bot

        return min(1, $this->getInvalidPasswordThrottler()->getThrottledRequestsCount(self::INVALID_PASSWORD_CACHE_PREFIX . $userId) / 30);
        // possible improvement: lookup older history through elasticsearch query:
        // channel: stat AND message: "account saved" AND context.errorCode: (3 OR 2) AND context.AccountUserID: 375138
    }

    private function getInvalidPasswordThrottler(): \ThrottlerInterface
    {
        // 30 actually not used here, correct number in invalidPasswordsScore
        return new \Throttler($this->memcached, 3600, 24, 30);
    }

    private function hasMobileAppScore(int $userId): float
    {
        $hasDevices = $this->connection->executeQuery("select 1 from MobileDevice where UserID = ? and DeviceType in (" . implode(", ", MobileDevice::TYPES_MOBILE) . ")", [$userId])->fetchColumn();

        return $hasDevices === false ? 0 : 1;
    }
}
