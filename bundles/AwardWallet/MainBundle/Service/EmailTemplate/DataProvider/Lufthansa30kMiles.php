<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\AccountProperty\PropertyKind;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class Lufthansa30kMiles extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->hasAccountPropertyExpr = [Provider::LUFTHANSA_ID => (function () {
                $map = new \SplObjectStorage();
                $map[new PropertyKind(PROPERTY_KIND_STATUS_MILES)] = fn ($apVal) => "{$apVal} >= 30000";

                return $map;
            })()];
            $option->accountFromProvidersLinkedToFamilyMember = false;
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'Lufthansa';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302" target="_blank">#22302</a><br/>
                Select AW members with >= 30,000 Lufthansa miles';
    }
}
