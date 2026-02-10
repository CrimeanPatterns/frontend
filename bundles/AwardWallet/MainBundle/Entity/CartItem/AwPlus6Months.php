<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AwPlus6Months extends AwPlus
{
    public const PRICE = 0;
    public const TYPE = 104;
    public const DURATION = SubscriptionPeriod::DURATION_6_MONTHS;

    protected $price = self::PRICE;
    protected $months = 6;
}
