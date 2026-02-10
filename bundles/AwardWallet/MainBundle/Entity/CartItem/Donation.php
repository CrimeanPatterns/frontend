<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 * @deprecated
 */
class Donation extends CartItem implements TranslationContainerInterface
{
    public const TYPE = 2;

    public function translate(TranslateArgs $args)
    {
        $this->setName($args->getTranslator()->trans('cart.item.type.donation'));
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.donation'))->setDesc('Donation to AwardWallet.com'),
        ];
    }
}
