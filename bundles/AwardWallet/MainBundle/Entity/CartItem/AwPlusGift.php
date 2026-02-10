<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 */
class AwPlusGift extends CartItem implements TranslationContainerInterface
{
    public const PRICE = 0;
    public const TYPE = 11;
    public const DURATION = SubscriptionPeriod::DURATION_1_YEAR;

    protected $price = self::PRICE;
    protected $months = 12;

    public function translate(TranslateArgs $args)
    {
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('gift-from-username.message'))->setDesc('Gift from %giverName% %customMessage%'),
        ];
    }

    public function isVisibleInCart(): bool
    {
        return false;
    }

    public function getGiverId(): int
    {
        return $this->id;
    }
}
