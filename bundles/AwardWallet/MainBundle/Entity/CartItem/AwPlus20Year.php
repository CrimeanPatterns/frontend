<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 * @deprecated
 */
class AwPlus20Year extends AwPlus implements TranslationContainerInterface
{
    public const PRICE = 0;
    public const TYPE = 3;
    public const DURATION = SubscriptionPeriod::DURATION_20_YEARS;

    protected $price = self::PRICE;
    protected $months = 240;

    public function translate(TranslateArgs $args)
    {
        $this->setName($args->getTranslator()->trans('cart.item.type.awplus-20-year'));
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.awplus-20-year'))->setDesc('Account upgrade from regular to AwardWallet Plus for 20 years'),
        ];
    }
}
