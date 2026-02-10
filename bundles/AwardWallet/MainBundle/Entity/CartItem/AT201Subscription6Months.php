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
class AT201Subscription6Months extends AT201Subscription implements AwPlusUpgradableInterface, TranslationContainerInterface, AT201SubscriptionInterface, AwPlusSubscriptionInterface
{
    public const PRICE = SubscriptionPrice::AT201_PRICE[self::DURATION];
    public const TYPE = 202;
    public const DURATION = SubscriptionPeriod::DURATION_6_MONTHS;

    protected $price = self::PRICE;
    protected $months = 6;

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
            $args->getTranslator()->trans('cart.item.type.at201-6months')
        );
    }

    public function getDuration()
    {
        return static::DURATION;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.at201-6months'))->setDesc('Award Travel 201 Semi-Annual Subscription (with AwardWallet Plus)'),
        ];
    }
}
