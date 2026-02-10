<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Repository\UserOAuthRepository;
use AwardWallet\MainBundle\Scanner\MailboxManager;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticationRequestListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UserOAuthRepository
     */
    private $userOAuthRepository;
    /**
     * @var MailboxManager
     */
    private $mailboxManager;
    /**
     * @var MobileDeviceManager
     */
    private $mobileDeviceManager;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    public function __construct(
        LoggerInterface $securityLogger,
        UserOAuthRepository $userOAuthRepository,
        MailboxManager $mailboxManager,
        MobileDeviceManager $mobileDeviceManager,
        ApiVersioningService $apiVersioning
    ) {
        $this->logger = $securityLogger;
        $this->userOAuthRepository = $userOAuthRepository;
        $this->mailboxManager = $mailboxManager;
        $this->mobileDeviceManager = $mobileDeviceManager;
        $this->apiVersioning = $apiVersioning;
    }

    /**
     * link account to last successful oauth id.
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!($user instanceof Usr)) {
            return;
        }

        $session = $event->getRequest()->getSession();

        if ($session === null) {
            return;
        }

        if (
            $this->apiVersioning->supports(MobileVersions::KEYCHAIN_REAUTH)
            && !($event->getAuthenticationToken() instanceof RememberMeToken)
        ) {
            $session->set(MobileReauthenticationRequestListener::SESSION_ENABLE_KEYCHAIN_AFTER_LOGGING_IN_KEY, true);
        }

        /** @var LastLoginInfo $lastLoginInfo */
        $lastLoginInfo = $session->get(Registrator::SESSION_LAST_SUCCESSFUL_LOGIN_INFO);
        $session->remove(Registrator::SESSION_LAST_SUCCESSFUL_LOGIN_INFO);

        if (
            $lastLoginInfo === null
            || $lastLoginInfo->getUserid() !== $user->getUserid()
            || \strcasecmp($lastLoginInfo->getUserInfo()->getEmail(), $user->getEmail()) !== 0
        ) {
            if ($lastLoginInfo) {
                $this->logger->info(
                    "unable to link user {$lastLoginInfo->getUserId()} (last) {$user->getId()} (current)" .
                    " to last oauth id ({$lastLoginInfo->getProvider()})" .
                    " with email {$lastLoginInfo->getUserInfo()->getEmail()} (last) {$user->getEmail()} (current)"
                );
            } else {
                $this->logger->info("unable to link user {$user->getId()}, no last oauth info was found.");
            }

            return;
        }

        $userInfo = $lastLoginInfo->getUserInfo();
        $this->logger->info("linking user {$user->getUserid()} to last oauth id: {$lastLoginInfo->getProvider()}, {$userInfo->getId()}");
        $user->getOAuth()->add(
            new UserOAuth(
                $user,
                $userInfo->getEmail(),
                $userInfo->getFirstName() ?? '',
                $userInfo->getLastName(),
                $lastLoginInfo->getProvider(),
                $userInfo->getId(),
                $userInfo->getAvatarURL()
            )
        );
        $this->userOAuthRepository->save($user);

        $tokens = $lastLoginInfo->getTokens();

        if ($tokens !== null) {
            $this->logger->info("adding mailbox {$lastLoginInfo->getProvider()}, {$userInfo->getEmail()} from last oauth registration, userId: {$user->getUserid()}");
            $this->mailboxManager->linkMailbox($user, null, $lastLoginInfo->getProvider(), $userInfo->getEmail(), $tokens);
        }
    }
}
