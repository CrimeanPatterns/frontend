<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class AlaskaNonelites extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->hasAccountFromProviders = [Provider::ALASKA_ID];
            $option->maxEliteLevel = 1;
            $option->accountFromProvidersLinkedToFamilyMember = false;
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'Alaska Non-Elites';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/23387" target="_blank">#23387</a><br/>
                providerID = 18<br/>
                SELECT any user with an elite status rank =0';
    }
}
