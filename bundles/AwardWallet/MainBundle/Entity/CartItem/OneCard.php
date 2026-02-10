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
class OneCard extends CartItem implements TranslationContainerInterface
{
    public const PRICE = 10;
    public const TYPE = 7;

    public const FLAG_RECURRING_ONECARD = 101;

    protected $price = self::PRICE;

    public function translate(TranslateArgs $args)
    {
        $translator = $args->getTranslator();
        $this->setName($translator->trans('cart.item.type.one-card'));
    }

    public function getQuantity()
    {
        return $this->getCnt();
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.one-card'))->setDesc('OneCard Credits'),
        ];
    }
}
