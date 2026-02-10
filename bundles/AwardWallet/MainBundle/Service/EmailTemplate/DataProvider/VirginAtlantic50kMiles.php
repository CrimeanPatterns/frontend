<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class VirginAtlantic50kMiles extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) {
            $option->hasBalanceExpr = [Provider::VIRGIN_ATLANTIC_ID => fn ($balanceField) => "{$balanceField} >= 50000"];
            $option->accountFromProvidersLinkedToFamilyMember = false;
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'Virgin Atlantic';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302" target="_blank">#22302</a><br/>
                SELECT AW members with >= 50,000 Virgin Atlantic points';
    }
}
