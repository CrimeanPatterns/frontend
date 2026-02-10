<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Repositories\InvitesRepository;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\CustomHeadersListener;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use AwardWallet\MainBundle\Manager\LogoManager;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Security\Captcha\Resolver\DesktopCaptchaResolver;
use AwardWallet\MainBundle\Security\LoginRedirector;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Service\CreditCards\Advertise;
use AwardWallet\MainBundle\Service\MobileAppRedirector;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public const SESSION_LOGIN_USERNAME = 'suggested-login-username';
    public const SUPERFLY_LEAD_ID = 218;

    private MobileAppRedirector $mobileAppRedirector;

    public function __construct(MobileAppRedirector $mobileAppRedirector)
    {
        $this->mobileAppRedirector = $mobileAppRedirector;
    }

    /**
     * @Route("/index.php", name="aw_home_php", options={"expose"=false})
     * @Route("/index.html", name="aw_home_html", options={"expose"=false})
     */
    public function redirectToMainPage(Request $request)
    {
        return $this->redirectToRoute('aw_home', $request->query->all());
    }

    /**
     * @Route("/m", name="aw_home_mobile", options={"expose"=false})
     */
    public function redirectToMobile(Request $request)
    {
        return $this->redirectToRoute('aw_home_mobile');
    }

    /**
     * @Route("/csp-report")
     */
    public function cspReportAction(LoggerInterface $logger)
    {
        $json = file_get_contents('php://input');
        $json = json_decode($json, true);

        if (!empty($json) && is_array($json) && array_key_exists('csp-report', $json)) {
            $logger->info('CspReport', $json['csp-report']);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/",
     *     name="aw_home",
     *     defaults={"_canonical"="aw_home", "_alternate"="aw_home_locale"},
     *     options={"expose"=true}
     * )
     * @Route(
     *     "/",
     *     name="aw_business_home",
     *     host="%business_host%",
     *     options={"expose"=true}
     * )
     * @Route(
     *     "/register",
     *     name="aw_register",
     *     defaults={"_canonical"="aw_home_locale", "_alternate"="aw_home_locale"},
     * )
     * @Route(
     *     "/login",
     *     name="aw_login",
     *     defaults={"_canonical"="aw_home_locale", "_alternate"="aw_home_locale"},
     *     options={"expose"=true}
     * )
     * @Route(
     *     "/restore",
     *     name="aw_restore",
     *     defaults={"_locale"="en", "_canonical"="aw_home_locale", "_alternate"="aw_home_locale"},
     * )
     * @Route(
     *     "/registerBusiness",
     *     name="aw_register_business",
     *     defaults={"_locale"="en", "_canonical"="aw_home_locale", "_alternate"="aw_home_locale"},
     * )
     * @Route(
     *     "/{_locale}/",
     *     name="aw_home_locale",
     *     requirements={"_locale" = "%route_locales%"},
     *     defaults={"_locale"="en", "_canonical"="aw_home_locale", "_alternate"="aw_home_locale"},
     *     options={"expose"=true}
     * )
     * @Route(
     *     "/{_locale}/login",
     *     name="aw_login_locale",
     *     requirements={"_locale" = "%route_locales%"},
     *     defaults={"_locale"="en", "_canonical"="aw_home_locale", "_alternate"="aw_home_locale"}
     * )
     * @Route(
     *     "/{_locale}/register",
     *     name="aw_register_locale",
     *     requirements={"_locale" = "%route_locales%"},
     *     defaults={"_locale"="en", "_canonical"="aw_home_locale", "_alternate"="aw_home_locale"}
     * )
     * @Route(
     *     "/{_locale}/restore",
     *     name="aw_restore_locale",
     *     requirements={"_locale"="%route_locales%"},
     *     defaults={"_locale"="en", "_canonical"="aw_home_locale", "_alternate"="aw_home_locale"}
     * )
     * @Route(
     *     "/{_locale}/registerBusiness",
     *     name="aw_register_business_locale",
     *     requirements={"_locale"="%route_locales%"},
     *     defaults={"_locale"="en", "_canonical"="aw_home_locale", "_alternate"="aw_home_locale"}
     * )
     */
    public function indexAction(
        Request $request,
        LoginRedirector $loginRedirector,
        ParameterRepository $parameterRepository,
        ProviderRepository $providerRepository,
        InvitesRepository $invitesRepository,
        DesktopCaptchaResolver $captchaResolver,
        BlogPostInterface $blogPost,
        Advertise $advertise,
        Counter $counter,
        LogoManager $logoManager
    ) {
        $response = $this->processHomePageResponse($request, $loginRedirector);

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $logo = $logoManager->getLogo();

        if (in_array($request->get('_route'), ['aw_restore', 'aw_restore_locale'])) {
            $response->headers->set('X-Robots-Tag', CustomHeadersListener::XROBOTSTAG_NOINDEX);
        }

        if ($this->isGranted('SITE_BUSINESS_AREA') || !empty($logo->shortName)) {
            return $this->render('@AwardWalletMain/Home/business.html.twig', [
                'miles' => $parameterRepository->getMilesCount(),
                'programs' => $providerRepository->getLPCount($_SERVER['DOCUMENT_ROOT'] . '/..'),
                'users' => intval($counter->getUsersCount() / 1000),
                'token' => GetFormToken(true),
                'invite' => $this->getInvite($request, $invitesRepository),
                'captcha_provider' => $captchaResolver->resolve($request),
                'blogposts' => $blogPost->fetchLastPost(2),
                'adsCreditCards' => $advertise->getListForLanding(),
                'load_avatar_facebook_app' => false,
                'username' => $request->getSession()->get(self::SESSION_LOGIN_USERNAME),
                'isFacebookPixel' => self::SUPERFLY_LEAD_ID === $request->query->getInt('ref'),
            ], $response);
        }

        return $this->render('@AwardWalletMain/Home/index.html.twig', [
            'miles' => $parameterRepository->getMilesCount(),
            'programs' => $providerRepository->getLPCount($_SERVER['DOCUMENT_ROOT'] . '/..'),
            'invite' => $this->getInvite($request, $invitesRepository),
            'captcha_provider' => $captchaResolver->resolve($request),
            'username' => $request->getSession()->get(self::SESSION_LOGIN_USERNAME),
        ], $response);
    }

    /**
     * @Route(
     *     "/{_locale}",
     *     requirements={"_locale" = "%route_locales%"},
     *     defaults={"_locale"="en"},
     *     options={"expose"=true}
     * )
     */
    public function indexLocaleRedirectAction(Request $request)
    {
        return $this->redirectToRoute('aw_home_locale', [
            '_locale' => $request->getLocale(),
        ]);
    }

    /**
     * @Route("/referer", methods={"POST"})
     */
    public function refererAction(
        Request $request,
        AwTokenStorageInterface $awTokenStorage,
        LoggerInterface $logger,
        $host
    ): JsonResponse {
        $referer = urldecode($request->request->get('referer'));
        $session = $request->getSession();

        if (0 === stripos($referer, 'http')
            && false !== filter_var($referer, FILTER_VALIDATE_URL)
            && null === $awTokenStorage->getUser()
            && (
                !$session->has(ReferalListener::REFERER_SESSION_KEY)
                || $host !== parse_url($referer, PHP_URL_HOST)
                || $host === parse_url(
                    $session->get(ReferalListener::REFERER_SESSION_KEY),
                    PHP_URL_HOST
                )
            )
        ) {
            $logger->info('setReferer PRE HomeController', [
                'get' => $session->get(ReferalListener::REFERER_SESSION_KEY, 'undefined'),
                'host' => $host,
                'parseUrl' => parse_url(
                    $session->get(ReferalListener::REFERER_SESSION_KEY),
                    PHP_URL_HOST
                ),
                'referer' => $referer,
                'ip' => $request->getClientIp(),
            ]);

            $session->set(ReferalListener::REFERER_SESSION_KEY, $referer);

            $logger->info('setReferer POST HomeController', [
                'get' => $session->get(ReferalListener::REFERER_SESSION_KEY, 'undefined'),
                'host' => $host,
                'parseUrl' => parse_url(
                    $session->get(ReferalListener::REFERER_SESSION_KEY),
                    PHP_URL_HOST
                ),
                'referer' => $referer,
                'ip' => $request->getClientIp(),
            ]);

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['success' => false]);
    }

    private function getKeepDesktopCookie(string $value, Request $request): Cookie
    {
        return AwCookieFactory::createLax('KeepDesktop', $value, time() + 3600 * 24 * 30, '/', $request->server->get('HTTP_HOST'), false);
    }

    private function getBackTo(Request $request): ?string
    {
        $backTo = $request->query->get('BackTo');

        if (!is_string($backTo)) {
            return null;
        }

        return $backTo;
    }

    private function getInvite(Request $request, InvitesRepository $invitesRepository): ?Invites
    {
        $invite = null;
        $invId = $request->getSession()->get('invId');
        $inviteCode = $request->getSession()->get('InviteCode');

        if ($invId) {
            $invite = $invitesRepository->find($invId);
        } elseif ($inviteCode) {
            $invite = $invitesRepository->findOneBy(['code' => $inviteCode]);
        }

        if ($invite && !$invite->getApproved()) {
            return $invite;
        }

        return null;
    }

    private function shouldRedirectToMobile(Request $request)
    {
        return $this->isGranted('SITE_MOBILE_VERSION_SUITABLE')
            && !(
                $request->query->get('refCode')
                || $request->query->get('invId')
                || $request->query->get('softMobileRedirect')
            );
    }

    private function processHomePageResponse(Request $request, LoginRedirector $loginRedirector): Response
    {
        if (strlen($request->getRequestUri()) > 1 && '' === trim($request->getRequestUri(), '/')) {
            return $this->redirectToRoute('aw_home');
        }

        $response = new Response();
        $isQueryMobile = $request->query->get('mobile');
        $backTo = $this->getBackTo($request);

        if (is_numeric($isQueryMobile)) {
            if (1 === (int) $isQueryMobile) {
                $response = new RedirectResponse($this->generateUrl('aw_home_mobile'));
                $response->headers->setCookie($this->getKeepDesktopCookie(0, $request));

                return $response;
            }

            $request->query->set('KeepDesktop', 1);
            $response->headers->setCookie($this->getKeepDesktopCookie(1, $request));
        } elseif ('0' === $request->cookies->get('KeepDesktop') && $this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('aw_home_mobile');
        } elseif ($this->shouldRedirectToMobile($request)) {
            $qs = "";

            if (is_string($backTo)) {
                $qs = "?BackTo=" . urlencode($backTo);
            }

            if (in_array($request->get('_route'), ['aw_login', 'aw_login', 'aw_login_locale', 'aw_login_locale'])) {
                return $this->mobileAppRedirector->createRedirect(
                    '/m/login' . $qs,
                    Request::create($backTo)
                );
            } elseif (in_array($request->get('_route'), ['aw_register', 'aw_register_locale', 'aw_register_locale'])) {
                return new RedirectResponse('/m/registration' . $qs);
            }
        }

        $targetUrl = is_string($backTo) ? urlPathAndQuery($backTo) : null;

        if ($targetUrl !== '/') {
            $request->getSession()->set(UserManager::SESSION_KEY_AUTHORIZE_SUCCESS_URL, $targetUrl);
        }

        if ($this->isGranted('ROLE_USER')) {
            return new RedirectResponse($loginRedirector->getLoginTargetPage($request->query->all()));
        }

        if (($ref = $request->query->getInt('ref')) > 0) {
            $request->getSession()->set(UserManager::SESSION_KEY_REFERRAL, $ref);
        }

        return $response;
    }
}
