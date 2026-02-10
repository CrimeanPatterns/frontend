<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\TimelineShare;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TimelineShareVoter extends AbstractVoter
{
    public function edit(TokenInterface $token, TimelineShare $share)
    {
        $user = $this->getBusinessUser($token);

        if (empty($user)) {
            return false;
        }

        return $this->view($token, $share) && $share->getUserAgent()->getTripAccessLevel();
    }

    public function view(TokenInterface $token, TimelineShare $share)
    {
        $user = $this->getBusinessUser($token);

        if (empty($user)) {
            return false;
        }

        return $user->getUserid() == $share->getRecipientUser()->getUserid() && $share->getUserAgent()->getIsapproved();
    }

    protected function getAttributes()
    {
        return [
            'EDIT' => [$this, 'edit'],
            'VIEW' => [$this, 'view'],
        ];
    }

    protected function getClass()
    {
        return '\\AwardWallet\\MainBundle\\Entity\\TimelineShare';
    }
}
