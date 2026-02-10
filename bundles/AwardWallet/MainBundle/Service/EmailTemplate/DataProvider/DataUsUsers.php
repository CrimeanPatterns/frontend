<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;

class DataUsUsers extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->countries = ['us', 'unknown'];
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Users from US or from unknown country. Country detected by last logon ip (if any) or registration ip';
    }

    public function getTitle(): string
    {
        return 'US users';
    }

    public function getSortPriority(): int
    {
        return 100;
    }
}
