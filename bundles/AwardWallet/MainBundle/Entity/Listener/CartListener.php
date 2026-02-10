<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\MainBundle\Entity\Cart;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class CartListener
{
    public function prePersist(Cart $cart, LifecycleEventArgs $args)
    {
        $this->setCartAttrHash($cart);
    }

    public function preUpdate(Cart $cart, PreUpdateEventArgs $args)
    {
        $this->setCartAttrHash($cart);
    }

    private function setCartAttrHash(Cart $cart)
    {
        $user = $cart->getUser();
        $paymentType = $cart->getPaymenttype();
        $payDate = $cart->getPaydate();

        if (
            $paymentType == Cart::PAYMENTTYPE_APPSTORE
            && isset($user) && isset($payDate)
        ) {
            $cart->setCartAttrHash(sprintf(
                "%s|%s|%s",
                $user->getId(),
                $paymentType,
                $payDate->format("Y-m-d H:i:s")
            ));
        }
    }
}
