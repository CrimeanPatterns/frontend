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
class Discount extends CartItem implements TranslationContainerInterface
{
    public const TYPE = 12;

    public const ID_PROMO_500K = 1;
    public const ID_EARLY_SUPPORTER = 2;
    public const ID_PROMO_EMILES = 3;
    public const ID_DISCOUNT30 = 4;
    public const ID_COUPON = 100;

    public function translate(TranslateArgs $args)
    {
        if (empty($this->getName())) {
            $this->setName($args->getTranslator()->trans('cart.item.discount-without-percent'));
        }
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.discount-without-percent'))->setDesc('Discount'),
        ];
    }

    public function isVisibleInCart(): bool
    {
        return false;
    }

    public function getQuantity()
    {
        return null;
    }
}
