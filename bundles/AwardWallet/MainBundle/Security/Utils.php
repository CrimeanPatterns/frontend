<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class Utils
{
    public static function tokenHasRole(TokenInterface $token, $roleName): bool
    {
        return in_array($roleName, $token->getRoleNames());
    }

    public static function getImpersonator(?TokenInterface $token = null): ?string
    {
        if (empty($token) || (!$token instanceof SwitchUserToken)) {
            return null;
        }

        $impersonator = $token->getOriginalToken()->getUser();

        if ($impersonator instanceof Usr) {
            return $impersonator->getLogin();
        }

        return null;
    }
}
