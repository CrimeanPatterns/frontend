<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AwPlus2Months extends AwPlus
{
    public const PRICE = 0;
    public const TYPE = 102;
    public const DURATION = SubscriptionPeriod::DURATION_2_MONTHS;

    protected $price = self::PRICE;
    protected $months = 2;
}
