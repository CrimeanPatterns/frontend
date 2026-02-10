<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\AwPlusSubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AwPlusWeekSubscription extends CartItem implements AwPlusUpgradableInterface, AwPlusSubscriptionInterface
{
    public const TYPE = 17;
    public const PRICE = 1;
    public const DURATION = SubscriptionPeriod::DURATION_1_WEEK;

    protected $price = self::PRICE;

    public function translate(TranslateArgs $args)
    {
        $this->setName('Staff account upgrade from regular to AwardWallet Plus for 1 week');
    }

    public function getDuration()
    {
        return self::DURATION;
    }
}
