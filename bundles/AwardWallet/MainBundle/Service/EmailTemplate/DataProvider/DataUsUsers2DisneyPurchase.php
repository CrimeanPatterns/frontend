<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsUsers2DisneyPurchase extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->usUsers2 = true;
            $option->countries = ['us', 'unknown'];
            // $option->hasMerchantsLike = ['%disney%'];
            $option->hasDisneyTransactions = true;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/25272" target="_blank">#25272</a>';
    }

    public function getTitle(): string
    {
        return 'US users 2 Disney Purchase';
    }

    public function getSortPriority(): int
    {
        return 99;
    }
}
