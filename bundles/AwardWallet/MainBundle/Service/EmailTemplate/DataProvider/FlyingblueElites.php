<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class FlyingblueElites extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) {
            $option->hasAccountFromProviders = [Provider::AIRFRANCE_ID];
            $option->minEliteLevel = 1;
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'Flying Blue elites';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302#note-34" target="_blank">#22302</a><br/>
                Flying Blue elites<br/>
                providerID = 44<br/>
                SELECT any user with an elite status rank >0';
    }
}
