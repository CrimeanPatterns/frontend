<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\Subscription;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\CartMatcherInterface;
use Psr\Log\LoggerInterface;

class AwPlus extends AbstractSubscription implements CartMatcherInterface
{
    public static $duration = AwPlusSubscription::DURATION;
    protected $cartTypes = [AwPlusSubscription::TYPE];

    public function getExpiresDate(): ?\DateTime
    {
        if (!empty($this->expiresDate)) {
            return $this->expiresDate;
        } else {
            return date_create("@" . strtotime(static::$duration, $this->purchaseDate->getTimestamp()));
        }
    }

    public function match(Cart $cart, LoggerInterface $logger): bool
    {
        return $this->user->getUserid() === $cart->getUser()->getUserid()
            && $this->getPaymentType() === $cart->getPaymenttype()
            && !empty($cart->getPaydate()) && DateTimeUtils::areEqualByTimestamp($cart->getPaydate(), $this->getPurchaseDate())
            && $cart->getItemsByType($this->cartTypes)->count() === sizeof($this->cartTypes);
    }
}
