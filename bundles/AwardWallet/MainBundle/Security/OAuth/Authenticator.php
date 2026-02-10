<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Repository\UserOAuthRepository;
use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;

class Authenticator
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var UserOAuthRepository
     */
    private $userOAuthRepository;

    public function __construct(LoggerInterface $securityLogger, UserManager $userManager, UserOAuthRepository $userOAuthRepository)
    {
        $this->logger = $securityLogger;
        $this->userManager = $userManager;
        $this->userOAuthRepository = $userOAuthRepository;
    }

    public function authenticate(string $provider, UserInfo $userInfo, bool $rememberMe): bool
    {
        $providerUserId = $userInfo->getId();
        /** @var UserOAuth $userOAuth */
        $userOAuth = $this->userOAuthRepository->findOneBy(['provider' => $provider, 'providerUserId' => $providerUserId]);

        if ($userOAuth !== null) {
            $user = $userOAuth->getUser();
            $this->userManager->loadToken($user, $rememberMe);
            $this->logger->info("successful login by oauth, provider: {$provider}, providerUserId: " . Strings::cutInMiddle($providerUserId, 4) . ", userId: " . $user->getUserid());

            if (!empty($userInfo->getEmail())) {
                $userOAuth->setEmail($userInfo->getEmail());
            }

            if (!empty($userInfo->getFirstName())) {
                $userOAuth->setFirstName($userInfo->getFirstName());
            }

            if (!empty($userInfo->getLastName())) {
                $userOAuth->setLastName($userInfo->getLastName());
            }
            $userOAuth->setAvatarURL($userInfo->getAvatarURL());
            $userOAuth->setLastLoginDate(new \DateTimeImmutable());
            $this->userOAuthRepository->save($userOAuth);

            setcookie('lastLogonProvider', $provider, time() + (60 * 5), '/'); // clickmagick

            return true;
        }

        $this->logger->info("oauth user not found, provider: {$provider}, providerUserId: " . Strings::cutInMiddle($providerUserId, 4));

        return false;
    }
}
