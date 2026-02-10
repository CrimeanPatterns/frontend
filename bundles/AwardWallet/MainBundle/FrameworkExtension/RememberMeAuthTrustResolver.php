<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RememberMeAuthTrustResolver extends AuthenticationTrustResolver
{
    /**
     * overridden to see RememberMe authorized users as fully authorized, to prevent redirect to authorization in case of 403
     * see RememberMeCest
     * get rid of  && !$this->isRememberMe($token).
     */
    public function isFullFledged(?TokenInterface $token = null)
    {
        if (null === $token) {
            return false;
        }

        return !$this->isAnonymous($token);
    }
}
