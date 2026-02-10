<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\Controller\HomeController;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Security\LoginRedirector;
use AwardWallet\MainBundle\Service\User\Constants;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class Registrator
{
    public const SESSION_LAST_SUCCESSFUL_LOGIN_INFO = 'oauth_last_successful_login_info';

    /**
     * @var UsrRepository
     */
    private $users;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LoginRedirector
     */
    private $loginRedirector;

    public function __construct(
        UsrRepository $users,
        UserManager $userManager,
        LoggerInterface $securityLogger,
        LoginRedirector $loginRedirector
    ) {
        $this->users = $users;
        $this->userManager = $userManager;
        $this->logger = $securityLogger;
        $this->loginRedirector = $loginRedirector;
    }

    /**
     * @return array [bool $success, Usr $user]: [true, $createdUser, "/account/list"] or [false, $conflictedUser, null]
     */
    public function register(string $provider, UserInfo $userInfo, ?Tokens $tokens, Request $request, array $query): RegistratorResult
    {
        /** @var Usr $user */
        $user = $this->users->findOneBy(['email' => $userInfo->getEmail()]);
        $session = $request->getSession();

        $session->remove(self::SESSION_LAST_SUCCESSFUL_LOGIN_INFO);
        $session->remove(HomeController::SESSION_LOGIN_USERNAME);

        if ($user === null) {
            $this->logger->info("creating user account from {$provider} oauth, id {$userInfo->getId()}");
            $user = new Usr();
            $user->setEmail($userInfo->getEmail());
            $user->setFirstname($userInfo->getFirstName());
            $user->setLastname($userInfo->getLastName());
            $user->getOAuth()->add(
                new UserOAuth(
                    $user,
                    $userInfo->getEmail(),
                    $userInfo->getFirstName() ?? '',
                    $userInfo->getLastName(),
                    $provider,
                    $userInfo->getId(),
                    $userInfo->getAvatarURL()
                )
            );

            if (!array_key_exists($provider, Usr::REGISTRATION_METHODS)) {
                throw new \RuntimeException('Unknown OAuth provider');
            }
            $user
                ->setRegistrationPlatform(
                    $request->attributes->has(Constants::REQUEST_PLATFORM_KEY) && Constants::REQUEST_PLATFORM_MOBILE === $request->attributes->get(Constants::REQUEST_PLATFORM_KEY)
                        ? Usr::REGISTRATION_PLATFORM_MOBILE_BROWSER
                        : Usr::REGISTRATION_PLATFORM_DESKTOP_BROWSER
                )
                ->setRegistrationMethod(Usr::REGISTRATION_METHODS[$provider]);
            $this->userManager->registerUser($user, $request);
            $this->userManager->loadToken($user, true);

            return new RegistratorResult(true, $user, $this->loginRedirector->getRegistrationTargetPage($user, $query));
        }

        $this->logger->info("user account already exists, user id: {$user->getUserid()}, while creating from {$provider} oauth, {$userInfo->getId()}, email: {$userInfo->getEmail()}");

        $session->set(
            self::SESSION_LAST_SUCCESSFUL_LOGIN_INFO,
            new LastLoginInfo($user->getUserid(), $provider, $userInfo, $tokens)
        );
        $session->set(HomeController::SESSION_LOGIN_USERNAME, $userInfo->getEmail());

        return new RegistratorResult(false, $user, null);
    }
}
