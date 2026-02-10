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
class OneCardShipping extends CartItem implements TranslationContainerInterface
{
    public const TYPE = 8;

    public function translate(TranslateArgs $args)
    {
        $this->setName($args->getTranslator()->trans('cart.item.type.one-card-shipping'));
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.one-card-shipping'))->setDesc('AwardWallet OneCard Credit'),
        ];
    }
}
