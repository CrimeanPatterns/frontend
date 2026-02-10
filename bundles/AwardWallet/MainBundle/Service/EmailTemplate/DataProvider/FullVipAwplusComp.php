<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class FullVipAwplusComp extends AbstractVIPUsersDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->vipHasEverSubscriptionType = Options::SUBSCRIPTION_TYPE_FULL_30;
            $option->hasVIPUpgrade = false;
            $option->ignoreEmailProductUpdates = true;
            $option->notBusiness = true;
            $option->ignoreGroupDoNotCommunicate = true;
        });

        return $options;
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options[Mailer::OPTION_SKIP_DONOTSEND] = true;

        return $options;
    }

    public function getDescription(): string
    {
        return 'Eligible VIP Members, full ($30) supporters';
    }

    public function getTitle(): string
    {
        return 'Eligible VIP Members, full ($30) supporters';
    }
}
