<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TransHelper
{
    private AuthorizationCheckerInterface $authorizationChecker;

    private \Memcached $memcached;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, \Memcached $memcached)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->memcached = $memcached;
    }

    public function isUserTranslator(?Usr $user = null): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_TRANSLATOR', $user);
    }

    public function isEnabled(Request $request, ?Usr $user = null): bool
    {
        $thCookie = $request->cookies->get('transhelper');

        if (
            $this->authorizationChecker->isGranted('ROLE_USER', $user)
            && $this->authorizationChecker->isGranted('ROLE_TRANSLATOR', $user)
        ) {
            return !empty($thCookie);
        }

        return !empty($this->memcached->get($thCookie));
    }
}
