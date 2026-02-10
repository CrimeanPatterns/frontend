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
class AwPlusTrial6Months extends AwPlus implements TranslationContainerInterface
{
    public const PRICE = 0;
    public const TYPE = 105;
    public const DURATION = SubscriptionPeriod::DURATION_6_MONTHS;

    protected $price = self::PRICE;
    protected $months = 6;

    public function translate(TranslateArgs $args): void
    {
        $this->setName($args->getTranslator()->trans('cart.item.type.awplus-trial'));
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('upgrade-awplus-6months-trial'))->setDesc('Account upgrade from regular to AwardWallet Plus, 6 months trial'),
        ];
    }
}
