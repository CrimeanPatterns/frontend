<?php
/**
 * Created by norman.
 * Date: 09.09.13
 * Time: 14:16.
 */

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleListener implements EventSubscriberInterface
{
    public const COOKIE = 'Locale2';
    public const DEFAULT_LOCALE = 'en_US';

    private $defaultLocale;
    private $locales;
    private $publicLocalesMobile;
    private $publicLocalesDesktop;
    private $secureCookie;
    private $locale;
    private $domain;
    private $tokenStorage;
    private $authorizationChecker;
    private $container;

    private $isMobileRequest = false;
    private $preferredLocale;
    private $cookieLocale;
    private $userLocale;
    private $requestLocale;
    private $allowedLocales;

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    public function __construct(ContainerInterface $container,
        TokenStorageInterface $tokenStorage,
        AuthorizationChecker $authorizationChecker,
        $defaultLocale = self::DEFAULT_LOCALE,
        $locales = [self::DEFAULT_LOCALE],
        $publicLocalesDesktop = [self::DEFAULT_LOCALE],
        $publicLocalesMobile = [self::DEFAULT_LOCALE],
        $protocol = 'https',
        $domain = null,
        TranslatorInterface $translator,
        ApiVersioningService $apiVersioning
    ) {
        $this->container = $container;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->defaultLocale = $defaultLocale;
        $this->locales = $locales;
        $this->publicLocalesDesktop = array_filter($publicLocalesDesktop, function ($l) use ($locales) {return in_array($l, $locales); });
        $this->publicLocalesMobile = array_filter($publicLocalesMobile, function ($l) use ($locales) {return in_array($l, $locales); });
        $this->secureCookie = ($protocol == 'https');
        $this->domain = $domain;
        $this->translator = $translator;
        $this->apiVersioning = $apiVersioning;
    }

    public function getLocale(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // for users on desktop
        $this->allowedLocales = $this->publicLocalesDesktop;

        // for users on mobile
        $requestMatcher = new RequestMatcher('^/m/api/');

        if ($requestMatcher->matches($request)) {
            // mobile API
            $this->isMobileRequest = true;
            $this->allowedLocales = $this->publicLocalesMobile;
        }

        $this->preferredLocale = $request->getPreferredLanguage($this->allowedLocales);
        $this->cookieLocale = $request->cookies->get(self::COOKIE);
        $this->userLocale = null;
        $this->requestLocale = $request->get('_locale', $request->get('locale'));

        if (empty($this->requestLocale)) {
            $token = $this->tokenStorage->getToken();

            if (null === $token || !($token->getUser() instanceof Usr)) {
                $this->cookieLocale = self::DEFAULT_LOCALE;
            }
        }

        //        $this->container->setParameter('locales', $allowedLocales);

        $this->decideLocale($request, false);
    }

    public function getAuthLocale(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        // for users in TRANSLATOR group
        $token = $this->tokenStorage->getToken();

        /** @var Usr $user */
        if (isset($token) && $user = $token->getUser()) {
            if ($user instanceof Usr) {
                $request = $event->getRequest();
                $this->userLocale = $request->getSession()->get('locale', $user->getLanguage());

                if ($this->authorizationChecker->isGranted('ROLE_TRANSLATOR', $user)) {
                    $this->allowedLocales = $this->locales;
                    $this->preferredLocale = $request->getPreferredLanguage($this->allowedLocales);
                }
                $this->decideLocale($request, true);
            }
        }
    }

    public function setLocale(FilterResponseEvent $event)
    {
        // disable on subrequests
        if (!$event->isMasterRequest()) {
            return;
        }

        // disable on errors
        if (!($event->getResponse() && $event->getResponse()->getStatusCode() < 400)) {
            return;
        }

        if ($this->locale) {
            $headers = $event->getResponse()->headers;
            $headers->setCookie(AwCookieFactory::createLax(self::COOKIE, $this->locale, new \DateTime('+1 year'), "/", $this->domain, $this->secureCookie, false));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['getLocale', 9], // pre-session locale decide, for registration
                ['getAuthLocale', 6], // post-session locale decide, for users
            ],
            KernelEvents::RESPONSE => 'setLocale',
        ];
    }

    /**
     * @param bool $startSession
     */
    protected function decideLocale(Request $request, $startSession = false)
    {
        $request->attributes->set('_aw_allowed_locales', $this->allowedLocales);

        if ($this->isMobileRequest) {
            // locale-cookies has no effect in mobile
            if ($this->requestLocale && in_array($this->requestLocale, $this->allowedLocales)) {
                // request locale has max priority
                $locale = $this->requestLocale;
            } elseif (
                $this->userLocale && in_array($this->userLocale, $this->allowedLocales)
                && $this->apiVersioning->supports(MobileVersions::REGIONAL_SETTINGS)
            ) {
                // check user
                $locale = $this->userLocale;
            } else {
                $locale = $this->preferredLocale;
            }
        } else {
            if ($this->requestLocale && in_array($this->requestLocale, $this->allowedLocales)) {
                // request locale has max priority
                $locale = $this->requestLocale;
            } elseif ($this->userLocale && in_array($this->userLocale, $this->allowedLocales)) {
                // check user
                $locale = $this->userLocale;
            } elseif ($this->cookieLocale && in_array($this->cookieLocale, $this->allowedLocales)) {
                // check cookie
                $locale = $this->cookieLocale;
            } else {
                // auto locale
                $locale = $this->preferredLocale;
            }
        }

        if ($startSession) {
            // TODO: move session start to proper place
            $request->getSession()->start();
        }

        if ($this->cookieLocale != $locale) {
            $this->locale = $locale;
        } else {
            $this->locale = false;
        }

        if ($this->requestLocale != $locale) {
            $this->translator->setLocale($locale); // TODO need find the right variant, this bad
            $request->setLocale($locale);
        } else {
            $this->translator->setLocale($this->requestLocale); // TODO need find the right variant, this bad
            $request->setLocale($this->requestLocale);
        }

        // TODO: remove after regional settings implementation on mobile
        if (
            $this->isMobileRequest
            && $this->apiVersioning->supports(MobileVersions::TIMELINE_BLOCKS_V2)
            && !$this->apiVersioning->supports(MobileVersions::REGIONAL_SETTINGS)
        ) {
            $this->container->get(LocalizeService::class)->setLocale($locale);
        }
    }
}
