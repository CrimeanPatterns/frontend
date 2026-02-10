<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Supporters3MonthsUpgrade extends AwPlus
{
    public const PRICE = 0;
    public const TYPE = 33;
    public const DURATION = SubscriptionPeriod::DURATION_3_MONTHS;

    protected $price = self::PRICE;
    protected $months = 3;

    public function translate(TranslateArgs $args)
    {
        $this->setName('Supporters account upgrade to AwardWallet Plus for 3 months');
    }
}
