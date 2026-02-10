<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\Event\CancelRecurringEvent;
use AwardWallet\MainBundle\Service\Paypal\AgreementHack;
use PayPal\Api\AgreementStateDescriptor;
use Psr\Log\LoggerInterface;

class PaypalSoapCancelListener
{
    private PaypalRestApi $paypalRestApi;
    private LoggerInterface $logger;

    public function __construct(PaypalRestApi $paypalRestApi, LoggerInterface $logger)
    {
        $this->paypalRestApi = $paypalRestApi;
        $this->logger = $logger;
    }

    public function cancelRecurring(CancelRecurringEvent $event): void
    {
        if ($event->getUser()->getSubscription() !== Usr::SUBSCRIPTION_PAYPAL || empty($event->getUser()->getPaypalrecurringprofileid())) {
            return;
        }

        $apiContext = $this->paypalRestApi->getApiContext();
        $agreement = AgreementHack::get($event->getUser()->getPaypalrecurringprofileid(), $apiContext);

        if ($agreement->getState() === 'Cancelled') {
            $this->logger->info("agreement {$agreement->getId()} is already cancelled");
            $event->stopPropagation();

            return;
        }

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("PaypalSoapCancelListener");
        $agreement->cancel($agreementStateDescriptor, $apiContext);

        $event->stopPropagation();
    }
}
