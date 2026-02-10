<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsUsers2 extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->usUsers2 = true;
            $option->countries = ['us', 'unknown'];
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/19939#1-User-has-a-one-of-the-following-connected-accounts" target="_blank">#19939</a>';
    }

    public function getTitle(): string
    {
        return 'US users 2';
    }

    public function getSortPriority(): int
    {
        return 100;
    }
}
