<?php

namespace AwardWallet\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\MainBundle\Entity\Account;
use Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class WaitTracker
{
    private OtcCache $cache;
    private ClockInterface $clock;
    private LoggerInterface $logger;

    public function __construct(
        OtcCache $cache,
        ClockInterface $clock,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->clock = $clock;
        $this->logger = $logger;
    }

    /**
     * checks if the account is eligible for otc waiting in general.
     */
    public function canWaitOtc(Account $account): bool
    {
        return ProviderQuestionAnalyzer::isProviderOtc($account->getProviderid()->getCode())
                && $account->getUser()->getValidMailboxesCount() > 0;
    }

    /**
     * checks if the account can receive an otc emails in the near future.
     *
     * @param int $maxWaitTime seconds, `-1` - ignore time limit, maximum value is 20 min (OtcCache::DATA_TTL)
     */
    public function isWaitingOtc(Account $account, int $maxWaitTime): bool
    {
        $upMark = $this->cache->getUpdate($account->getId());

        return $this->canWaitOtc($account)
            && $account->getErrorcode() === ACCOUNT_QUESTION
            && !empty($account->getQuestion())
            && ProviderQuestionAnalyzer::isQuestionOtc($account->getProviderid()->getCode(), $account->getQuestion())
            && (!empty($upMark) && ($upMark > $this->clock->current()->getAsSecondsInt() - $maxWaitTime) || ($maxWaitTime === -1));
    }

    /**
     * returns timestamp of the last check request with an OTC from an email or null.
     */
    public function getLastOtcCheckDate(Account $account): ?int
    {
        return $this->cache->getAutoCheck($account->getId());
    }

    public function getNextRequestId(string $oldRequestId): ?string
    {
        return $this->cache->getNextRequestId($oldRequestId);
    }

    /**
     * stops an account from automatically sending new check request with a received OTC.
     */
    public function dontWaitOtc(Account $account): void
    {
        $this->logger->info('otc: ignoring next code for this provider', ['accountId' => $account->getId(), 'userId' => $account->getUser()->getId()]);
        $this->cache->setStop($account->getUser()->getId(), $account->getProviderid()->getCode());
    }
}
