<?php

namespace AwardWallet\MainBundle\Service\Billing;

use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;

class GooglePurchasesSubscriptionsFactory
{
    private \Google_Service_AndroidPublisher $googleServiceAndroidPublisher;

    public function __construct(\Google_Service_AndroidPublisher $googleServiceAndroidPublisher)
    {
        $this->googleServiceAndroidPublisher = $googleServiceAndroidPublisher;
    }

    public function getPurchasesSubscriptions(): PurchasesSubscriptions
    {
        return $this->googleServiceAndroidPublisher->purchases_subscriptions;
    }
}
