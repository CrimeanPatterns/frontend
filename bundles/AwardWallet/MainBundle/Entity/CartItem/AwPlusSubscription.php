<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\AwPlusSubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 */
class AwPlusSubscription extends AwPlus implements TranslationContainerInterface, AwPlusSubscriptionInterface
{
    public const TYPE = 16;
    public const PRICE = SubscriptionPrice::AWPLUS_PRICE[self::DURATION];
    public const EARLY_SUPPORTER_DISCOUNT = 20;
    public const PROMO_500K_DISCOUNT = 10;
    public const PROMO_EMILES_DISCOUNT = 10;
    public const DURATION = SubscriptionPeriod::DURATION_1_YEAR;

    protected $months = 12;
    protected $price = self::PRICE;
    protected $startDate;

    public function translate(TranslateArgs $args)
    {
        $user = $this->getCart()->getUser();
        $hasAwPlus = $user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS;
        $payDate = $this->getCart()->getPaydate();
        $scheduledDate = $this->getScheduledDate();

        if ($scheduledDate) {
            $startDate = clone $scheduledDate;
        } elseif ($hasAwPlus) {
            $startDate = $user->getPlusExpirationDate();
        } elseif ($payDate instanceof \DateTime) {
            $startDate = $payDate;
        } else {
            $startDate = new \DateTime();
        }

        $this->setName(
            $args->getTranslator()->trans('cart.item.type.awplus-subscription', [])
        );

        $this->setDescription($args->getTranslator()->trans('cart.item.type.awplus-subscription.desc', [
            '%startDate%' => $args->getLocalizer()->formatDateTime($startDate, 'short', null),
        ]));
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.awplus-subscription'))->setDesc('AwardWallet Plus yearly subscription'),
            (new Message('cart.item.type.awplus-subscription.desc'))->setDesc('12 months (starting from %startDate%)'),
            (new Message('cart.item.type.awplus-subscription.scheduled'))->setDesc('1 year, starting from %startDate% (payment was scheduled, not yet processed)'),
        ];
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }
}
