<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class IhgRewardsEliteMembers extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->hasAccountFromProviders = [Provider::IHG_REWARDS_ID];
            $option->minEliteLevel = 1;
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'IHG Rewards - Elite Members';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302" target="_blank">#22302</a><br/>
                An audience that includes all IHG rewards members that have elite status. This should include all levels of elite status besides basic members with Club (Rank = 0) status.';
    }
}
