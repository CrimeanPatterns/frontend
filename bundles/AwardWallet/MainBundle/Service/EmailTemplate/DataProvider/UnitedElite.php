<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class UnitedElite extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) {
            $option->hasAccountFromProviders = [Provider::UNITED_ID];
            $option->minEliteLevel = 1;
            $option->accountFromProvidersLinkedToFamilyMember = false;
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'United Elites';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302#note-32" target="_blank">#22302</a><br/>
                SELECT any user with an elite status rank >0';
    }
}
