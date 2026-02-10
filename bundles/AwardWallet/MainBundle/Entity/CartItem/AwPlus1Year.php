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
class AwPlus1Year extends AwPlus implements TranslationContainerInterface
{
    public const PRICE = 30;
    public const TYPE = 4;
    public const EARLY_SUPPORTER_DISCOUNT = 20;
    public const DURATION = SubscriptionPeriod::DURATION_1_YEAR;

    protected $price = self::PRICE;
    protected $months = 12;

    public function translate(TranslateArgs $args)
    {
        $this->setName($args->getTranslator()->trans('cart.item.type.awplus-1-year'));
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.awplus-1-year'))->setDesc('Account upgrade from regular to AwardWallet Plus for 1 year'),
        ];
    }
}
