<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Security\AuthenticationListener;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeServices;
use AwardWallet\MainBundle\Security\SessionListener;
use AwardWallet\MainBundle\Security\Utils;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Twig\Environment;

class LogoutController extends AbstractController
{
    /**
     * this action here only to support graceful logout from impersonate (login back to impersonator).
     *
     * @Route("/security/logout", name="aw_users_logout")
     */
    public function logoutAction(
        Request $request,
        SessionListener $sessionListener,
        AwTokenStorageInterface $tokenStorage,
        UserProviderInterface $userProvider,
        EventDispatcherInterface $eventDispatcher,
        AuthorizationCheckerInterface $authorizationChecker,
        MobileDeviceManager $mobileDeviceManager,
        RememberMeServices $rememberMeServices,
        Environment $twig,
        PageVisitLogger $pageVisitLogger
    ) {
        $token = $tokenStorage->getToken();
        $impersonated = Utils::tokenHasRole($token, 'ROLE_IMPERSONATED') || Utils::tokenHasRole($token, 'ROLE_IMPERSONATED_FULLY');

        $response = new Response();
        $sessionId = $request->getSession()->getId();
        $rememberMeTokenID = $request->getSession()->get('RememberMeTokenID');

        if ($impersonated && $token instanceof SwitchUserToken) {
            $originalToken = $token->getOriginalToken();
            $user = $userProvider->refreshUser($originalToken->getUser());
            $tokenStorage->setToken($originalToken);
            $switchEvent = new SwitchUserEvent($request, $user);
            $eventDispatcher->dispatch($switchEvent, SecurityEvents::SWITCH_USER);
            $request->getSession()->remove('locale');
        } else {
            if ($token !== null) {
                $pageVisitLogger->log(PageVisitLogger::PAGE_LOGOUT);

                $user = $token->getUser();
                $isPersonal = !$authorizationChecker->isGranted('SITE_BUSINESS_AREA');

                if ($isPersonal && $user instanceof Usr) {
                    $mobileDeviceManager->forgetUserBySessionId(
                        $user,
                        $request->getSession()->getId()
                    );
                }
                $rememberMeServices->logout($request, $response, $token);
            }
            $tokenStorage->setToken(null);
            AuthenticationListener::cleanOldSession();
            $request->getSession()->invalidate();
        }

        $cookies = [
            AwCookieFactory::createLax('XSRF-TOKEN', null, 1, '/'),
            AwCookieFactory::createLax(session_name(), null, 1, '/account/'),
            AwCookieFactory::createLax('refCode', null, 1, '/blog'),
        ];

        foreach ($cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }

        if (!$impersonated) {
            if ($token->getUser() instanceof Usr) {
                $sessionListener->invalidateUserSession($token->getUser()->getUserid(), $sessionId);

                if (!empty($rememberMeTokenID)) {
                    $sessionListener->invalidateUserSessionByRememberTokenId($token->getUser()->getUserid(), $rememberMeTokenID);
                }
            }

            $cookies = [
                AwCookieFactory::createLax('Log', null, 1, '/'),
                AwCookieFactory::createLax('Pwd', null, 1, '/'),
                AwCookieFactory::createLax('PwdHash', null, 1, '/'),
                AwCookieFactory::createLax('SavePwd', null, 1, '/'),
                AwCookieFactory::createLax('PasswordSaved', null, 1, '/'),
                AwCookieFactory::createLax('Log', null, 1, '/security/'),
                AwCookieFactory::createLax('Pwd', null, 1, '/security/'),
                AwCookieFactory::createLax('PwdHash', null, 1, '/security/'),
                AwCookieFactory::createLax('SavePwd', null, 1, '/security/'),
                AwCookieFactory::createLax('PasswordSaved', null, 1, '/security/'),
            ];

            foreach ($cookies as $cookie) {
                $response->headers->setCookie($cookie);
            }
        }

        if (0 === strpos($request->headers->get('Accept'), 'application/json')) {
            // mobile app
            $response->headers->set('content-type', 'application/json');
            $response->setContent(json_encode(['success' => true]));
        } else {
            // desktop
            $clearStorageScript = "
            localStorage.removeItem('angular-cache.caches.DatabaseStorage.data./data');
            localStorage.removeItem('angular-cache.caches.DatabaseStorage.data.version');
            localStorage.removeItem('angular-cache.caches.DatabaseStorage.keys');
            localStorage.removeItem('angular-cache.caches.PushStorage.data.data');
            localStorage.removeItem('angular-cache.caches.PushStorage.keys');
            localStorage.removeItem('angular-cache.caches.SessionStorage.data.current');
            localStorage.removeItem('angular-cache.caches.SessionStorage.keys');
            localStorage.removeItem('web_token_day');
            localStorage.removeItem('web_token_resubscribed');
            localStorage.removeItem('web_token_user');
            localStorage.removeItem('booking_request_new');
            ";
            $url = "/";

            if ($impersonated) {
                $url = "/manager/impersonate";
            }

            if ($request->query->has("BackTo")) {
                $url = urlPathAndQuery($request->query->get("BackTo"));
            }
            $response->setContent($twig->render('@AwardWalletMain/redirectPage.html.twig', [
                'url' => $url,
                'script' => $clearStorageScript,
            ]));
        }

        return $response;
    }

    /**
     * @Route("/security/logout.php", name="aw_users_old_logout")
     */
    public function oldLogoutAction(Request $request)
    {
        return new RedirectResponse($this->generateUrl("aw_users_logout", $request->query->all()));
    }
}
