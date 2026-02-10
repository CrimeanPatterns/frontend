<?php

namespace AwardWallet\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\Common\OneTimeCode\ProviderQuestionAnalyzer;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Event\LoyaltyPrepareAccountRequestEvent;
use Psr\Log\LoggerInterface;

class AccountTracker
{
    private OtcCache $cache;

    private LoggerInterface $logger;

    private OTCProcessor $processor;

    public function __construct(OtcCache $cache, LoggerInterface $logger, OTCProcessor $processor)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->processor = $processor;
    }

    public function onLoyaltyPrepareAccountRequest(LoyaltyPrepareAccountRequestEvent $event)
    {
        if (!ProviderQuestionAnalyzer::isProviderOtc($event->getAccount()->getProviderid()->getCode())) {
            return;
        }
        $this->logger->info('acc tracker: sending to check', ['accountId' => $event->getAccount()->getId()]);
        $this->cache->setCheck($event->getAccount()->getId());
        $this->cache->clearStop($event->getAccount()->getUser()->getId(), $event->getAccount()->getProviderid()->getCode());
        $this->setLocalPassword($event->getAccount());
    }

    public function onAccountUpdated(AccountUpdatedEvent $event)
    {
        if (!ProviderQuestionAnalyzer::isProviderOtc($event->getAccount()->getProviderid()->getCode())) {
            return;
        }
        $this->logger->info('acc tracker: account updated', ['accountId' => $event->getAccount()->getId()]);
        $this->cache->setUpdate($event->getAccount()->getId());
        $this->processor->process($event->getAccount(), $event->getCheckAccountResponse());
    }

    private function setLocalPassword(Account $account): void
    {
        if (SAVE_PASSWORD_LOCALLY === $account->getSavepassword()
            && !empty($account->getPass())
            && $account->getProviderid()->getPasswordrequired()) {
            $this->cache->setTempLocalPassword($account->getId(), $account->getPass());
        }
    }
}
