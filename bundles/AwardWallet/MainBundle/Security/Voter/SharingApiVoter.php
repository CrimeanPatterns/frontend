<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SharingApiVoter extends AbstractVoter
{
    public function businessApiInvite(TokenInterface $token, Usr $user)
    {
        return
            $user->isBusiness()
            && ($businessInfo = $user->getBusinessInfo())
            && $businessInfo->isApiEnabled()
            && $businessInfo->isApiInviteEnabled()
            && !StringHandler::isEmpty($businessInfo->getApiCallbackUrl());
    }

    protected function getAttributes()
    {
        return [
            'BUSINESS_API_INVITE' => [$this, 'businessApiInvite'],
        ];
    }

    protected function getClass()
    {
        return Usr::class;
    }
}
