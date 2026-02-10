<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class InvitecodeVoter extends AbstractVoter
{
    public function edit(TokenInterface $token, Invitecode $invitecode): bool
    {
        /** @var Usr $user */
        $user = $this->getBusinessUser($token);

        if (!($user instanceof Usr)) {
            return false;
        }

        return $invitecode->getUserid()->getUserid() === $user->getUserid();
    }

    protected function getAttributes()
    {
        return [
            'EDIT' => [$this, 'edit'],
        ];
    }

    protected function getClass()
    {
        return 'AwardWallet\\MainBundle\\Entity\\Invitecode';
    }
}
