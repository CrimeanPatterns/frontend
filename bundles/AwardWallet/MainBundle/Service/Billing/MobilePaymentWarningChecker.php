<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class MobilePaymentWarningChecker
{
    private CartRepository $cartRepository;
    private SessionInterface $session;

    public function __construct(CartRepository $cartRepository, SessionInterface $session)
    {
        $this->cartRepository = $cartRepository;
        $this->session = $session;
    }

    public function addWarnings(Usr $user)
    {
        $currentSubscrCart = $this->cartRepository->getActiveAwSubscription($user);

        if ($currentSubscrCart !== null) {
            switch ($currentSubscrCart->getPaymenttype()) {
                case PAYMENTTYPE_APPSTORE:
                    $this->session->set(Usr::TURN_OFF_IOS_SUBSCRIPTION_WARNING, true);

                    break;
            }
        }
    }
}
