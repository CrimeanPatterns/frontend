<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\Event\CancelRecurringEvent;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;

class StripeCancelListener
{
    private StripeClient $stripe;
    private LoggerInterface $logger;

    public function __construct(
        StripeClient $stripe,
        LoggerInterface $paymentLogger
    ) {
        $this->stripe = $stripe;
        $this->logger = $paymentLogger;
    }

    public function cancelRecurring(CancelRecurringEvent $event): void
    {
        if ($event->getUser()->getSubscription() !== Usr::SUBSCRIPTION_STRIPE || empty($event->getUser()->getPaypalrecurringprofileid())) {
            return;
        }

        try {
            $this->logger->info("removing stripe payment method (dry-run): " . $event->getUser()->getPaypalrecurringprofileid());
            // $method = $this->stripe->paymentMethods->retrieve($event->getUser()->getPaypalrecurringprofileid());
            // $method->detach();
        } catch (\Throwable $exception) {
            $this->logger->critical("error deleting stripe payment method: " . $exception->getMessage(), ["exception" => $exception]);
        }

        $event->stopPropagation();
    }
}
