<?php

namespace AwardWallet\MainBundle\Service\WebPush;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SafariTokenGenerator
{
    public const USER_PREFIX = "aw";

    private AuthorizationCheckerInterface $authorizationChecker;
    private AwTokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private string $secret;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, AwTokenStorageInterface $tokenStorage, LoggerInterface $logger, string $secret)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->secret = $secret;
    }

    public function getToken(): ?string
    {
        if (
            $this->authorizationChecker->isGranted('USER_IMPERSONATED')
            || $this->authorizationChecker->isGranted('USER_IMPERSONATED_AS_SUPER')
        ) {
            return null;
        }

        /** @var Usr $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if (!($user instanceof Usr)) {
            $user = null;
        }
        $token = $this->getUserToken($user);
        $this->logger->info("safari get user token", ["userId" => ($user ? $user->getId() : null), "userToken" => $token]);

        return $token;
    }

    public function getUserToken(?Usr $user = null): string
    {
        if ($user === null) {
            $result = self::USER_PREFIX . ":anonymous:" . sha1("safari_web_push_salt" . $this->secret);
        } else {
            $result = self::USER_PREFIX . ":" . $user->getUserid() . ":" . sha1($user->getId() . $user->getCreationdatetime()->format("Y-m-d H:i:s") . "safari_web_push_salt" . $this->secret);
        }

        return $result;
    }
}
