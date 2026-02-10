<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\Subscription;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription;

class AwPlusWeek extends AwPlus
{
    public static $duration = AwPlusWeekSubscription::DURATION;

    protected $cartTypes = [AwPlusWeekSubscription::TYPE];
}
