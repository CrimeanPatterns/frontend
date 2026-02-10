<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\Subscription;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\Discount;

class AwPlusDiscounted extends AwPlus
{
    protected $cartTypes = [AwPlusSubscription::TYPE, Discount::TYPE];
}
