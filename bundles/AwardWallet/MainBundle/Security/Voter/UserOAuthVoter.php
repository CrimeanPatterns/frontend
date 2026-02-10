<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserOAuthVoter extends AbstractVoter
{
    public function unlink(TokenInterface $token, UserOAuth $object)
    {
        $user = $token->getUser();

        if (!$user instanceof Usr) {
            return false;
        }

        return $user->getOAuth()->contains($object);
    }

    protected function getAttributes()
    {
        return [
            'UNLINK' => [$this, 'unlink'],
        ];
    }

    protected function getClass()
    {
        return UserOAuth::class;
    }
}
