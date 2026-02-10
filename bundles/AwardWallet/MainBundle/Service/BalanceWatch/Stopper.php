<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\BalanceWatchCreditsTransaction;
use AwardWallet\MainBundle\Entity\BusinessTransaction;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use AwardWallet\MainBundle\Service\BusinessTransaction\BalanceWatchProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class Stopper
{
    private Query $query;
    private EntityManagerInterface $entityManager;
    private Notifications $notifications;
    private LoggerInterface $logger;
    private BackgroundCheckScheduler $scheduler;
    private BalanceWatchProcessor $balanceWatchProcessor;

    public function __construct(
        Query $query,
        EntityManagerInterface $entityManager,
        Notifications $notifications,
        LoggerInterface $logger,
        BalanceWatchProcessor $balanceWatchProcessor,
        BackgroundCheckScheduler $scheduler
    ) {
        $this->query = $query;
        $this->entityManager = $entityManager;
        $this->notifications = $notifications;
        $this->logger = $logger;
        $this->scheduler = $scheduler;
        $this->balanceWatchProcessor = $balanceWatchProcessor;
    }

    public function stopBalanceWatch(Account $account, int $reason): self
    {
        $balanceWatch = $this->query->getAccountBalanceWatch($account);
        $logContext = [
            'userId' => $account->getUser()->getId(),
            'accountId' => $account->getAccountid(),
            'reason' => Constants::EVENTS[$reason],
            'watchFound' => null !== $balanceWatch,
        ];
        $this->logger->info('Account BalanceWatch Update - STOP', $logContext);

        if (null === $balanceWatch) {
            if (null !== $account->getBalanceWatchStartDate()) {
                $logContext['forceDisable'] = 1;
                $account->setBalanceWatchStartDate(null);
                $this->entityManager->persist($account);
                $this->entityManager->flush();
            }

            throw new \RuntimeException('BalanceWatchManager balanceWatch row not found', $logContext);
        }

        switch ($reason) {
            case Constants::EVENT_BALANCE_CHANGED:
                $balanceWatch->setStopReason(BalanceWatch::REASON_BALANCE_CHANGED);

                break;

            case Constants::EVENT_TIMEOUT:
                $balanceWatch->setStopReason(BalanceWatch::REASON_TIMEOUT);

                break;

            case Constants::EVENT_UPDATE_ERROR:
                $balanceWatch->setStopReason(BalanceWatch::REASON_UPDATE_ERROR);

                break;

            case Constants::EVENT_FORCED_STOP:
                $balanceWatch->setStopReason(BalanceWatch::REASON_FORCED_STOP);

                break;

            default:
                throw new \RuntimeException('BalanceWatchManager unknown reason to stop', $logContext);
        }
        $balanceWatch->setStopDate(new \DateTime());
        $this->entityManager->persist($balanceWatch);

        $this->notifications->sendAccountNotification($account, $reason, $balanceWatch);
        $account->setBalanceWatchStartDate(null);
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        if (in_array($reason, [Constants::EVENT_UPDATE_ERROR, Constants::EVENT_FORCED_STOP])) {
            if ($balanceWatch->isBusiness()) {
                /** @var BusinessTransaction $bwTransactionStart */
                $bwTransactionStart = $this->entityManager->getRepository(BusinessTransaction\BalanceWatchStart::class)->findBy([
                    'sourceID' => $account->getId(),
                    'user' => $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($balanceWatch->getPayerUser())->getId(),
                ], ['id' => 'DESC'], 1);

                if (empty($bwTransactionStart)) {
                    throw new \RuntimeException('BalanceWatchManager paid BUSINESS transaction not found', $logContext);
                }

                $bwTransactionStart = $bwTransactionStart[0];
                $this->logger->info('BalanceWatchManager REFUND BUSINESS start - reason ' . Constants::EVENTS[$reason], [
                    'userId' => $account->getUser()->getId(),
                    'accountId' => $account->getId(),
                    'businessId' => $bwTransactionStart->getUser()->getUserid(),
                ]);

                $this->balanceWatchProcessor->balanceWatch($reason, $balanceWatch->getPayerUser(), $account, $bwTransactionStart);
            } else {
                $balanceWatchCreditTransaction = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\BalanceWatchCreditsTransaction::class)->findBy([
                    'account' => $account,
                    'type' => BalanceWatchCreditsTransaction::TYPE_SPEND,
                ], ['id' => 'DESC'], 1);

                if (empty($balanceWatchCreditTransaction)) {
                    throw new \RuntimeException('BalanceWatchManager paid USER credit transaction not found', $logContext);
                }

                $balanceWatchCreditTransaction = $balanceWatchCreditTransaction[0];
                $this->logger->info('BalanceWatchManager REFUND USER start - reason ' . Constants::EVENTS[$reason], [
                    'userId' => $balanceWatchCreditTransaction->getUser()->getId(),
                    'accountId' => $account->getAccountid(),
                    'balanceWatchCredits' => $balanceWatchCreditTransaction->getUser()->getBalanceWatchCredits(),
                ]);
                $count = Constants::TRANSACTION_COST + $balanceWatchCreditTransaction->getUser()->getBalanceWatchCredits();
                $balanceWatchCreditTransaction->getUser()->setBalanceWatchCredits($count);
                $this->entityManager->flush($balanceWatchCreditTransaction->getUser());
                $this->logger->info('BalanceWatchManager REFUND USER stop - reason ' . Constants::EVENTS[$reason], [
                    'userId' => $balanceWatchCreditTransaction->getUser()->getId(),
                    'accountId' => $account->getAccountid(),
                    'balanceWatchCredits' => $count,
                ]);
            }
        }

        $this->scheduler->schedule($account->getAccountid());

        return $this;
    }
}
