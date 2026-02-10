<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsersWithoutSubscription extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->hasSubscription = false;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'All users without subscription';
    }

    public function getTitle(): string
    {
        return 'All users without subscription';
    }
}
