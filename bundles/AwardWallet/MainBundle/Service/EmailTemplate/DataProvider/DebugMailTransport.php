<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DebugMailTransport extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->ignoreEmailLog = true;
            $option->userId = [2110];
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Debug Mail Transport (VSilantyev)';
    }

    public function getTitle(): string
    {
        return 'Debug Mail Transport (VSilantyev)';
    }
}
