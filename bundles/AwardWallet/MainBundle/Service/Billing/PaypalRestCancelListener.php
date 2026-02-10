<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\Event\CancelRecurringEvent;
use PayPal\Exception\PayPalConnectionException;
use Psr\Log\LoggerInterface;

class PaypalRestCancelListener
{
    /**
     * @var PaypalRestApi
     */
    private $paypalRestApi;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(PaypalRestApi $paypalRestApi, LoggerInterface $logger)
    {
        $this->paypalRestApi = $paypalRestApi;
        $this->logger = $logger;
    }

    public function cancelRecurring(CancelRecurringEvent $event): void
    {
        if ($event->getUser()->getSubscription() !== Usr::SUBSCRIPTION_SAVED_CARD || empty($event->getUser()->getPaypalrecurringprofileid())) {
            return;
        }

        try {
            $this->paypalRestApi->deleteSavedCard($event->getUser()->getPaypalrecurringprofileid());
        } catch (PayPalConnectionException $e) {
            $data = @json_decode($e->getData(), true);
            $this->logger->warning("paypal api exception: ", ['paypal_error' => $data, "UserID" => $event->getUser()->getUserid()]);

            if (!empty($data['name']) && $data['name'] == 'INVALID_RESOURCE_ID') {
                $this->logger->warning("credit card already deleted", ["UserID" => $event->getUser()->getUserid(), "profileId" => $event->getUser()->getPaypalrecurringprofileid()]);
            } else {
                throw $e;
            }
        }

        $event->stopPropagation();
    }
}
