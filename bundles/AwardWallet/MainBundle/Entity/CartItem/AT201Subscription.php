<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculatorInterface;

abstract class AT201Subscription extends CartItem implements ExpirationCalculatorInterface
{
    public function calcExpirationDate($date, string $cartItemClass)
    {
        if ($cartItemClass === AT201SubscriptionInterface::class) {
            return $this->calcAT201Expiration($date);
        }

        if ($cartItemClass === AwPlusUpgradableInterface::class) {
            return $this->calcPlusDate($date);
        }

        return $date;
    }

    private function calcPlusDate(int $plusExpDate): int
    {
        $currentDate = $this->getCart()->getPaydate()->getTimestamp();
        $dateRange = $this->getDuration();

        if ($currentDate > $plusExpDate) { // нет awplus
            return strtotime($dateRange, $currentDate);
        } elseif ($currentDate <= $plusExpDate) { // есть awplus
            return strtotime($dateRange, $plusExpDate);
        } else { // протухание awplus позже чем протухание at201
            return $plusExpDate;
        }
    }

    private function calcAT201Expiration(int $date): int
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
