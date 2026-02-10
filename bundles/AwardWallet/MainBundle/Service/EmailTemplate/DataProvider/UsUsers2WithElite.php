<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class UsUsers2WithElite extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->usUsers2 = true;
            $option->countries = ['us', 'unknown'];
            $option->minEliteLevel = 1;
            $option->hasAccountFromProvidersByKind = [
                \PROVIDER_KIND_AIRLINE,
                \PROVIDER_KIND_HOTEL,
                \PROVIDER_KIND_CAR_RENTAL,
            ];
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/24311" target="_blank">#24311</a>';
    }

    public function getTitle(): string
    {
        return 'US users 2 with Elite Status';
    }

    public function getSortPriority(): int
    {
        return 100;
    }
}
