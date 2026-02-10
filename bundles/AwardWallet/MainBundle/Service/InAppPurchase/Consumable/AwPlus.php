<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\Consumable;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus as AwPlusItem;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractConsumable;
use AwardWallet\MainBundle\Service\InAppPurchase\CartMatcherInterface;
use Psr\Log\LoggerInterface;

class AwPlus extends AbstractConsumable implements CartMatcherInterface
{
    protected $cartTypes = [AwPlusItem::TYPE];

    public function match(Cart $cart, LoggerInterface $logger): bool
    {
        return $this->user->getId() === $cart->getUser()->getId()
            && $this->getPaymentType() === $cart->getPaymenttype()
            && !empty($cart->getPaydate()) && DateTimeUtils::areEqualByTimestamp($cart->getPaydate(), $this->getPurchaseDate())
            && $cart->getItemsByType($this->cartTypes)->count() === sizeof($this->cartTypes);
    }
}
