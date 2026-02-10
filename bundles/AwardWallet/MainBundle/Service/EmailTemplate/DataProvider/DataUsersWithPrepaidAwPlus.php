<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsersWithPrepaidAwPlus extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->hasPrepaidAwPlus = true;
            $option->notBusiness = true;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'AW members who purchased >= 1 year of prepaid AwardWallet plus via the <a href="https://awardwallet.com/user/pre-payment" target="_blank">pre payment</a> page';
    }

    public function getTitle(): string
    {
        return 'Users with prepaid AW Plus';
    }
}
