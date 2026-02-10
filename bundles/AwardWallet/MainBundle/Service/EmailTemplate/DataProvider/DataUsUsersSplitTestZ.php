<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsUsersSplitTestZ extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->countries = ['us', 'unknown'];

            $option->usUsers2 = true;
            $option->userIdSplit = [2, 1];
        });

        return $options;
    }

    // even
    public function getDescription(): string
    {
        return "This is the <b>2/2 slice</b><br/>
            AW users from US\unknown (detected by last logon ip (if any) or registration ip) were shuffled and split into 2 groups by whether UserID is even or odd (this group has odd UserID's)";
    }

    public function getTitle(): string
    {
        return "2/2 Group (half of users from US)";
    }

    public function getGroup(): string
    {
        return Group::GROUPS_2_US;
    }
}
