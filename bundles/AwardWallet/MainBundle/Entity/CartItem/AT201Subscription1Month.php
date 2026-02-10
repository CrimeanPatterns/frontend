<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusSubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 */
class AT201Subscription1Month extends AT201Subscription implements AwPlusUpgradableInterface, TranslationContainerInterface, AT201SubscriptionInterface, AwPlusSubscriptionInterface
{
    public const PRICE = SubscriptionPrice::AT201_PRICE[self::DURATION];
    public const TYPE = 201;
    public const DURATION = SubscriptionPeriod::DURATION_1_MONTH;

    protected $price = self::PRICE;
    protected $months = 1;

    public function getMonths(): int
    {
        return $this->months;
    }

    public function getSavings(): ?int
    {
        return null;
    }

    public function translate(TranslateArgs $args)
    {
        $this->setName(
            $args->getTranslator()->trans('cart.item.type.at201-1month')
        );
    }

    public function getDuration()
    {
        return static::DURATION;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.at201-1month'))->setDesc('Award Travel 201 Monthly Subscription (with AwardWallet Plus)'),
        ];
    }
}
