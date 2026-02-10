<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculatorInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * @ORM\Entity
 */
class AwPlus extends CartItem implements TranslationContainerInterface, AwPlusUpgradableInterface, ExpirationCalculatorInterface
{
    public const PRICE = 5;
    public const TYPE = 1;
    public const FLAG_RECURRING = 100;
    public const DURATION = SubscriptionPeriod::DURATION_6_MONTHS;

    protected $price = self::PRICE;
    protected $months = 6;

    /**
     * @return int
     */
    public function getMonths()
    {
        return $this->months;
    }

    /**
     * @param int $months
     */
    public function setMonths($months)
    {
        $this->months = $months;

        return $this;
    }

    public function translate(TranslateArgs $args)
    {
        $translator = $args->getTranslator();
        $user = $this->getCart()->getUser();
        $hasAwPlus = $user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS;
        $startDate = new \DateTime("+" . $this->getMonths() . " month");

        if ($hasAwPlus) {
            $data = $args->getExpirationCalculator()->getAccountExpiration($user->getId());
            $startDate->setTimestamp($data['date']);
            $startDate->modify("+" . $this->getMonths() . " month");
            $this->setName($translator->trans('cart.item.type.awplus-ext'));
        } else {
            $this->setName($translator->trans('cart.item.type.awplus'));
        }
        $this->setDescription($translator->trans('cart.item.type.awplus.desc', [
            '%months%' => $this->getMonths(),
            '%extensionDate%' => $args->getLocalizer()->formatDateTime($startDate, 'short', null),
        ]));
    }

    public function getDuration()
    {
        return static::DURATION;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('cart.item.type.awplus'))->setDesc('Account upgrade from regular to AwardWallet Plus'),
            (new Message('cart.item.type.awplus-ext'))->setDesc('Extension of AwardWallet Plus'),
            (new Message('cart.item.type.awplus.desc'))->setDesc('for <strong>%months% months</strong> (until <strong>%extensionDate%</strong>)'),
        ];
    }

    public function calcExpirationDate($date, string $cartItemClass)
    {
        $d = $this->getCart()->getPaydate()->getTimestamp();
        $dateRange = $this->getDuration();

        if ($d < $date) {
            $date = strtotime($dateRange, $date);
        } else {
            $date = strtotime($dateRange, $d);
        }

        return $date;
    }
}
