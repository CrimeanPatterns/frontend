<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataNoAwSubscriptionUsers extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->awPlusActiveSubscription = false;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Users with expired AW-Plus subscription or with subscription about to expire (1 week)';
    }

    public function getTitle(): string
    {
        return 'Users without active subscription';
    }

    public function isDeprecated(): bool
    {
        return true;
    }
}
