<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AwPlus1Month extends AwPlus
{
    public const PRICE = 0;
    public const TYPE = 101;
    public const DURATION = SubscriptionPeriod::DURATION_1_MONTH;

    protected $price = self::PRICE;
    protected $months = 1;
}
