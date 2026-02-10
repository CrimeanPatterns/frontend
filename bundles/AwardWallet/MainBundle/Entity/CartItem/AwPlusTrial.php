<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 */
class AwPlusTrial extends AwPlus implements TranslationContainerInterface
{
    public const PRICE = 0;
    public const TYPE = 10;
    public const DURATION = SubscriptionPeriod::DURATION_3_MONTHS;

    protected $price = self::PRICE;
    protected $months = 3;

    public function translate(TranslateArgs $args)
    {
        $this->setName($args->getTranslator()->trans('cart.item.type.awplus-trial'));
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.awplus-trial'))->setDesc('Account upgrade from regular to AwardWallet Plus, 3 months trial'),
        ];
    }
}
