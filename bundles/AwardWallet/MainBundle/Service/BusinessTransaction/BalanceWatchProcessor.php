<?php

namespace AwardWallet\MainBundle\Service\BusinessTransaction;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BusinessTransaction;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\BalanceWatch\Constants;

class BalanceWatchProcessor
{
    private BusinessTransactionManager $businessTransactionManager;

    private UsrRepository $usrRepository;

    public function __construct(BusinessTransactionManager $businessTransactionManager, UsrRepository $usrRepository)
    {
        $this->businessTransactionManager = $businessTransactionManager;
        $this->usrRepository = $usrRepository;
    }

    public function balanceWatch(int $event, Usr $payerUser, Account $account, ?BusinessTransaction $businessTransaction = null): bool
    {
        $business = $this->usrRepository->getBusinessByUser($payerUser);

        if (null === $business) {
            throw new \InvalidArgumentException('PayerUser must be a business administrator');
        }

        if (Constants::EVENT_START_MONITORED === $event) {
            $transaction = new BusinessTransaction\BalanceWatchStart($account, $payerUser);

            return $this->businessTransactionManager->addTransaction($business, $transaction, $transaction->getAmount());
        }

        if (in_array($event, [Constants::EVENT_UPDATE_ERROR, Constants::EVENT_FORCED_STOP])) {
            if ($businessTransaction === null) {
                throw new \RuntimeException('BusinessTransaction is empty');
            }

            $transaction = (new BusinessTransaction\BalanceWatchRefund($account, $payerUser))
                ->setAmount($businessTransaction->getAmount());

            return $this->businessTransactionManager->addTransaction($business, $transaction);
        }

        throw new \InvalidArgumentException('Function takes only values from the types of notifications BalanceWatchManager');
    }
}
