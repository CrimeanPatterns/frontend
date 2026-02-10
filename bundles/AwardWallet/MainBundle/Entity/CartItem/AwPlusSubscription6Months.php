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
class AwPlusSubscription6Months extends AwPlus implements TranslationContainerInterface, AwPlusSubscriptionInterface
{
    public const TYPE = 18;
    public const PRICE = SubscriptionPrice::AWPLUS_PRICE[self::DURATION];
    public const DURATION = SubscriptionPeriod::DURATION_6_MONTHS;

    protected $months = 6;
    protected $price = self::PRICE;
    protected $startDate;

    public function translate(TranslateArgs $args)
    {
        $user = $this->getCart()->getUser();
        $hasAwPlus = $user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS;
        $payDate = $this->getCart()->getPaydate();

        if ($hasAwPlus) {
            $startDate = $user->getPlusExpirationDate();
        } elseif ($payDate instanceof \DateTime) {
            $startDate = $payDate;
        } else {
            $startDate = new \DateTime();
        }

        $this->setName(
            $args->getTranslator()->trans('cart.item.type.awplus-subscription-6-months', [])
        );

        $this->setDescription($args->getTranslator()->trans('cart.item.type.awplus-subscription-6-months.desc', [
            '%startDate%' => $args->getLocalizer()->formatDateTime($startDate, 'short', null),
        ]));
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.awplus-subscription-6-months'))->setDesc('AwardWallet Plus 6 months subscription'),
            (new Message('cart.item.type.awplus-subscription-6-months.desc'))->setDesc('6 months (starting from %startDate%)'),
            (new Message('cart.item.type.awplus-subscription-6-months.scheduled'))->setDesc('6 months, starting from %startDate% (payment was scheduled, not yet processed)'),
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
