<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsUsers2InternalForIsUs extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->countries = ['us', 'unknown'];
            $option->usUsers2 =
            $option->ignoreEmailLog =
            $option->ignoreEmailOffers =
            $option->ignoreEmailProductUpdates =
            $option->ignoreGroupDoNotCommunicate = true;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getTitle(): string
    {
        return '(system internal) US users 2 (do not use for mailing)';
    }

    public function getSortPriority(): int
    {
        return -100;
    }
}
