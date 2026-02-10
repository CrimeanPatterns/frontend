<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\CartUserSource;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PayerVoter extends Voter
{
    private CartUserSource $cartUserSource;

    public function __construct(CartUserSource $cartUserSource)
    {
        $this->cartUserSource = $cartUserSource;
    }

    protected function supports($attribute, $subject)
    {
        return $attribute === 'USER_PAYER' && $subject === null;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if ($user instanceof Usr) {
            return true;
        }

        $currentCartOwner = $this->cartUserSource->getCartOwner();

        return $currentCartOwner !== null;
    }
}
