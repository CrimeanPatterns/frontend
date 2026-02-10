<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class VirginAtlanticMembers extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) {
            $option->hasAccountFromProviders = [Provider::VIRGIN_ATLANTIC_ID];
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'Virgin Atlantic - All Members';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302#note-24" target="_blank">#22302</a><br/>
                An audience that includes all AW members that have a connected Virgin Atlantic (Flying Club account).';
    }
}
