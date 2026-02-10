<?php

namespace AwardWallet\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FormEntryPointHelper
{
    /**
     * @var AntiBruteforceLockerService
     */
    protected $loginLocker;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var TwoFactorAuthenticationService
     */
    protected $twoFactorAuthenticationService;
    /**
     * @var AntiBruteforceLockerService
     */
    protected $ipLocker;

    public function __construct(
        AntiBruteforceLockerService $loginLocker,
        LoggerInterface $logger,
        TwoFactorAuthenticationService $twoFactorAuthenticationService
    ) {
        $this->loginLocker = $loginLocker;
        $this->logger = $logger;
        $this->twoFactorAuthenticationService = $twoFactorAuthenticationService;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $user = $token->getUser();

        if (!empty($user) && $user instanceof Usr) {
            $this->loginLocker->unlock($user->getLogin());
        }

        // this part for mobile site
        $result = ['success' => true, 'sessionId' => session_id()];
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        if (!empty($user) && $user instanceof Usr) {
            $this->twoFactorAuthenticationService->addAuthKeyCookie($request, $response, $user);
        }

        $this->logger->info("Auth success", [
            "userid" => (!empty($user) && $user instanceof Usr ? $user->getUserid() : "none"),
            "sessionid" => substr($result['sessionId'], -4),
            'aw_server_module' => 'login_form_authenticator',
        ]);

        return $response;
    }
}
