<?php

namespace AwardWallet\MainBundle\Service\BusinessTransaction;

use AwardWallet\MainBundle\Event\BusinessPaymentEvent;

class BusinessTransactionListener
{
    private BusinessTransactionManager $transactionManager;

    public function __construct(BusinessTransactionManager $transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    public function onBusinessPayment(BusinessPaymentEvent $event)
    {
        $this->transactionManager->addPayment($event->getBusiness(), $event->getAmount());
    }
}
