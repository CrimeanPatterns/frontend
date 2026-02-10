<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class LifemilesUsa extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) {
            $option->hasAccountFromProviders = [Provider::AVIANCA_ID];
            $option->countries = ['us', 'unknown'];
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'LifeMiles members in the U.S.';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302#note-32" target="_blank">#22302</a><br/>
                LifeMiles members in the U.S.';
    }
}
