<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class MileageplusMembers extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) {
            $option->hasAccountFromProviders = [26];
            $option->accountFromProvidersLinkedToFamilyMember = false;
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'United MileagePlus accounts';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302#note-22" target="_blank">#22302</a><br/>
                All AW members that have a United MileagePlus account connected to AW';
    }
}
