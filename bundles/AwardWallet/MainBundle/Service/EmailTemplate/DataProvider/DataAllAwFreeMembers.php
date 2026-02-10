<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataAllAwFreeMembers extends AbstractAwPlusRelatedDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->awPlusUpgraded = false;
            $option->notBusiness = true;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'All Free users';
    }

    public function getTitle(): string
    {
        return 'All Free users';
    }

    protected function isUserValid(Usr $user, ?Cart $cart): bool
    {
        return $user->isFree() && !$user->getSubscription() && !$cart;
    }
}
