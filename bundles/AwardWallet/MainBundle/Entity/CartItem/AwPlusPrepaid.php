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
class AwPlusPrepaid extends AwPlus implements TranslationContainerInterface
{
    public const PRICE = 30;
    public const TYPE = 31;
    public const DURATION = SubscriptionPeriod::DURATION_1_YEAR;

    protected $price = self::PRICE;
    protected $months = 12;

    public function translate(TranslateArgs $args)
    {
        $this->setName(
            $args->getTranslator()->trans(
                'cart.item.type.awplus-multiple-years',
                ['%count%' => $this->cnt]
            )
        );
    }

    public function getDuration()
    {
        return sprintf('+%d year', $this->cnt);
    }

    public function getQuantity()
    {
        return $this->getCnt();
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.awplus-multiple-years'))
                ->setDesc('Account upgrade to AwardWallet Plus for %count% year|Account upgrade to AwardWallet Plus for %count% years'),
            (new Message('cart.item.type.awplus-multiple-years.renewal-notice'))
                ->setDesc('This one-time payment unlocks AwardWallet Plus through %date%. Afterwards, your account will automatically renew at %price_per_period% until cancelled.'),
            (new Message('cart.item.type.awplus-multiple-years.apple-notice'))
                ->setDesc('This one-time payment unlocks AwardWallet Plus through %date%.'),
            (new Message('cart.item.type.awplus-multiple-years.downgrade-notice'))
                ->setDesc('This one-time payment unlocks AwardWallet Plus through %date%. Afterwards, your account will be downgraded to the free version of AwardWallet, and you will not be charged again unless you purchase a new subscription.'),
        ];
    }
}
