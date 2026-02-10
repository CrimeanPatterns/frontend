<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

trait ControllerTrait
{
    public function getCurrentUser()
    {
        return $this->getUser() ?? false;
    }

    public function isAuthorized(AuthorizationCheckerInterface $authorizationChecker): bool
    {
        return $authorizationChecker->isGranted('ROLE_USER');
    }

    public function checkImpersonation(AuthorizationCheckerInterface $authorizationChecker): void
    {
        if ($authorizationChecker->isGranted('USER_IMPERSONATED')) {
            throw new ImpersonatedException();
        }
    }

    public function checkCsrfToken(AuthorizationCheckerInterface $authorizationChecker): void
    {
        if (!$authorizationChecker->isGranted('CSRF')) {
            throw new AccessDeniedHttpException('Invalid CSRF-token');
        }
    }
}
