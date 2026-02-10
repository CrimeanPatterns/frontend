<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Manager\UserManager;

class CouponReferralListener
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function onCartMarkPaid(CartMarkPaidEvent $event): void
    {
        $cart = $event->getCart();
        $cartCoupon = $cart->getCoupon();

        if ($cartCoupon && ($couponUser = $cartCoupon->getUser())) {
            $this->userManager->setInviteByCouponUser($cart->getUser(), $couponUser);
        }
    }
}
