<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AwPlusVIP1YearUpgrade extends AwPlus
{
    public const PRICE = 0;
    public const TYPE = 32;
    public const DURATION = SubscriptionPeriod::DURATION_1_YEAR;

    protected $price = self::PRICE;
    protected $months = 12;

    public function translate(TranslateArgs $args)
    {
        $this->setName('VIP account upgrade to AwardWallet Plus for 1 year');
    }
}
