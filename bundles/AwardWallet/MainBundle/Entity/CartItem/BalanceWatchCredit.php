<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 */
class BalanceWatchCredit extends CartItem implements TranslationContainerInterface
{
    public const TYPE = 50;
    public const PRICE = 5;

    public const COUNT_PRICE = [
        1 => 5,
        3 => 10,
        5 => 15,
        10 => 25,
    ];

    public function __construct()
    {
        $this->price = self::PRICE;
    }

    public function calcDiscount()
    {
        $discount = $this->getDiscount();

        if ($discount > 0) {
            return $discount;
        }

        if (array_key_exists($this->getCnt(), self::COUNT_PRICE)) {
            return ($this->getCnt() * self::PRICE) - self::COUNT_PRICE[$this->getCnt()];
        }

        return 0;
    }

    public function translate(TranslateArgs $args): void
    {
        $translator = $args->getTranslator();
        $this->setName($translator->trans('cart.item.type.balancewatch-credit'));
    }

    public function getQuantity()
    {
        return $this->getCnt();
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('cart.item.type.balancewatch-credit'))->setDesc('Balance Watch Credits'),
        ];
    }

    public function isCountable()
    {
        return true;
    }
}
