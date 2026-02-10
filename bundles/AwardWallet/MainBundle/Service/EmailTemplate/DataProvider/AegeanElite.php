<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class AegeanElite extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->hasAccountFromProviders = [Provider::AEGEAN_ID];
            $option->minEliteLevel = 1;
            $option->accountFromProvidersLinkedToFamilyMember = false;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return "Description: <a href='https://redmine.awardwallet.com/issues/22224' target='_blank'>#22224</a><br/>
Users with Aegean account and Elite level >= 1 (Silver, Gold)";
    }

    public function getTitle(): string
    {
        return "Aegean Elite";
    }
}
