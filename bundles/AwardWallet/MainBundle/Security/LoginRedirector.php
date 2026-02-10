<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Service\Counter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LoginRedirector
{
    /**
     * @var BookingRequestManager
     */
    private $bookingRequestManager;
    /**
     * @var Counter
     */
    private $counter;
    /**
     * @var AwTokenStorage
     */
    private $tokenStorage;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        BookingRequestManager $bookingRequestManager,
        Counter $counter,
        AwTokenStorage $tokenStorage,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ) {
        $this->bookingRequestManager = $bookingRequestManager;
        $this->counter = $counter;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->session = $session;
        $this->em = $entityManager;
    }

    public function getLoginTargetPage(array $query = []): string
    {
        $backTo = $this->session->get(UserManager::SESSION_KEY_AUTHORIZE_SUCCESS_URL);

        if (!empty($backTo)) {
            $this->session->remove(UserManager::SESSION_KEY_AUTHORIZE_SUCCESS_URL);

            return $backTo;
        }

        $isBusinessArea = $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA');
        $code = $query['code'] ?? $query['Code'] ?? $query['coupon'] ?? $query['Coupon'] ?? null;
        $isInvite = isset($query['invId']);

        if (!empty($code) && !$isInvite) {
            $this->session->set("coupon", $code);

            return $this->router->generate('aw_users_usecoupon');
        }

        if ($isBusinessArea && !$this->authorizationChecker->isGranted('BUSINESS_ACCOUNTS')) {
            return $this->router->generate('aw_booking_list_queue');
        }

        if ($url = $this->getAccountListRedirect()) {
            return $url;
        }

        return $this->router->generate("aw_account_list");
    }

    /**
     * where to send user after registration.
     *
     * @return string
     */
    public function getRegistrationTargetPage(Usr $user, array $query = [])
    {
        $result = $this->getLoginTargetPage($query);

        $inviteCode = $this->session->get("InviteCode");

        if ($inviteCode) {
            $invite = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Invites::class)->findOneBy(['code' => $inviteCode]);

            if ($invite) {
                $userAgent = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->findOneBy(['agentid' => $invite->getInviterid(), 'clientid' => $user]);

                if ($userAgent) {
                    $result = $this->router->generate('aw_user_connection_edit', ['userAgentId' => $userAgent->getUseragentid()]);
                }
            }
        }

        return $result;
    }

    public function getAccountListRedirect(): ?string
    {
        if ($url = $this->bookingRequestManager->checkUnreadMessagesAndGetRedirect()) {
            return $url;
        }

        $cnt = $this->counter->getTotalAccounts($this->tokenStorage->getBusinessUser()->getUserid());

        if ($cnt === 0) {
            return $this->router->generate('aw_select_provider', ['new' => 1]);
        }

        return null;
    }
}
