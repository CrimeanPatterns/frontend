<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsCreditCardUsersWithFico extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->countries = ['us', 'unknown'];
            $option->hasFicoScore = true;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Users from US or from unknown country (detected by last logon ip (if any) or registration ip) <br/>
                who have card (Bank of America, American Express, Discover, Barclaycard, Citibank) with FICO score equal or greater than 700';
    }

    public function getTitle(): string
    {
        return 'US users with FICO score';
    }
}
