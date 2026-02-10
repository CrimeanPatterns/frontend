<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\MainBundle\Security\Utils;
use AwardWallet\MainBundle\Service\TransHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SiteVoter extends AbstractVoter
{
    /**
     * Wheter was attempt to escape impersonation sandbox.
     *
     * @var bool
     */
    protected $isImpersonationSandboxEscaped = false;

    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    /**
     * @var DefaultBookerParameter
     */
    private $defaultBooker;

    public function __construct(
        ContainerInterface $container,
        ApiVersioningService $apiVersioning,
        DefaultBookerParameter $defaultBooker
    ) {
        parent::__construct($container);

        $this->apiVersioning = $apiVersioning;
        $this->defaultBooker = $defaultBooker;
    }

    public function isBooking()
    {
        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
        ) {
            $requestMatcher = new RequestMatcher();
            $requestMatcher->matchPath('^/awardBooking');

            return $requestMatcher->matches($request);
        }

        return false;
    }

    public function isMobile()
    {
        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
        ) {
            $requestMatcher = new RequestMatcher();
            $requestMatcher->matchPath('^/(mobile|m/api)');

            return $requestMatcher->matches($request);
        }

        return false;
    }

    /**
     * Whether request from awardwallet mobile app.
     *
     * @return bool
     */
    public function isMobileApp(?TokenInterface $token = null, $object = null, $platforms = ['ios', 'android'])
    {
        return
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
            && $request->headers->has(MobileHeaders::MOBILE_PLATFORM)
            && in_array(strtolower($request->headers->get(MobileHeaders::MOBILE_PLATFORM)), (array) $platforms, true);
    }

    public function isMobileAppAndroid(TokenInterface $token, $object)
    {
        return $this->isMobileApp($token, $object, 'android');
    }

    public function isMobileAppIos(TokenInterface $token, $object)
    {
        return $this->isMobileApp($token, $object, 'ios');
    }

    public function isMobileAppReactNative(TokenInterface $token, $object)
    {
        return $this->isMobileApp($token, $object)
            && $this->apiVersioning->supports(MobileVersions::NATIVE_FORM_EXTENSION);
    }

    /**
     * Whether request.
     */
    public function isMobileVersionSuitable()
    {
        return
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
            && !$this->isBusiness()
            && !$request->cookies->has('KeepDesktop')
            && !$request->query->has('KeepDesktop')
            && UserAgentUtils::isMobileBrowser($request->headers->get('user_agent'));
    }

    public function isMobileBrowser()
    {
        return
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
            && UserAgentUtils::isMobileBrowser($request->headers->get('user_agent'));
    }

    public function isDevMode()
    {
        /** @var \AppKernel $kernel */
        $kernel = $this->container->get('kernel');

        return in_array($kernel->getEnvironment(), ['dev', 'test', 'codeception', 'acceptance']);
    }

    public function isProdMode()
    {
        /** @var \AppKernel $kernel */
        $kernel = $this->container->get('kernel');

        return in_array($kernel->getEnvironment(), ['prod', 'codeception', 'staging', 'acceptance']);
    }

    public function isLocalProd()
    {
        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
        ) {
            $requestMatcher = new RequestMatcher();
            $requestMatcher->matchHost('\.dev|local$');

            return $requestMatcher->matches($request);
        }

        return false;
    }

    public function isImpersonated(TokenInterface $token, $object)
    {
        return Utils::tokenHasRole($token, 'ROLE_IMPERSONATED');
    }

    public function isAwPlus(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        if ($this->isBusiness()) {
            if ($user instanceof Usr) {
                $usrRep = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
                $business = $usrRep->getBusinessByUser($user, [ACCESS_ADMIN]);

                if (!empty($business) && !$business->getBusinessInfo()->isBlocked()) {
                    return true;
                }
            }

            return false;
        }

        return Utils::tokenHasRole($token, 'ROLE_AWPLUS') || ($user instanceof Usr && $user->isAwPlus());
    }

    public function isImpersonatedAsSuper(TokenInterface $token)
    {
        return Utils::tokenHasRole($token, 'ROLE_IMPERSONATED_FULLY');
    }

    public function isNotImpersonated(TokenInterface $token, $object)
    {
        $impersonated = $this->isImpersonated($token, $object);

        if ($impersonated) {
            $this->isImpersonationSandboxEscaped = true;

            return false;
        }

        return true;
    }

    public function isBusinessAdmin(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        if ($this->isBusiness()) {
            if ($user instanceof Usr) {
                $business = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user, [ACCESS_ADMIN]);

                if (!empty($business)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isBookingAdmin(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        if ($this->isBusiness()) {
            if ($user instanceof Usr) {
                $business = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user, [ACCESS_ADMIN]);

                if (!empty($business) && $business->isBooker()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isBookingManager(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        if ($this->isBusiness()) {
            if ($user instanceof Usr) {
                $usrRep = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
                $business = $usrRep->getBusinessByUser($user, [ACCESS_ADMIN]);

                if (!empty($business) && $business->isBooker()) {
                    return true;
                }
                $business = $usrRep->getBusinessByUser($user, [ACCESS_BOOKING_MANAGER]);

                if (!empty($business) && $business->isBooker()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isBookerArea(TokenInterface $token, $object)
    {
        if ($this->isBusiness()) {
            $user = $token->getUser();

            if ($user instanceof Usr) {
                $business = $this->getBusinessUser($token);

                if (!empty($business) && $business->isBooker()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isAwBooker(TokenInterface $token, $object)
    {
        $business = $this->getBusinessUser($token);

        return $this->isBookerArea($token, $object)
            && $business instanceof Usr
            && $business->getUserid() === $this->defaultBooker->get();
    }

    public function isNewDesign(TokenInterface $token)
    {
        // @TODO: remove unused voter
        return true;
    }

    public function isBetaUser(TokenInterface $token)
    {
        return $token->getUser() instanceof Usr && $token->getUser()->getInbeta() && $token->getUser()->getBetaapproved();
    }

    public function isFromApp()
    {
        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
        ) {
            return
                $request->query->has('fromapp')
                || $request->query->get('fromapponce', 0)
                || $request->cookies->get('fromMobileApp', 0);
        }

        return false;
    }

    public function isFullImpersonate()
    {
        return ($this->container->get("security.authorization_checker")->isGranted('ROLE_STAFF_ROOT') || $this->isDevMode())
           && !empty($this->container->get('request_stack')->getMasterRequest()->server->get("whiteListedIp"));
    }

    public function isImpersonationSandboxEscaped(): bool
    {
        return $this->isImpersonationSandboxEscaped;
    }

    public function setIsImpersonationSandboxEscaped(bool $isImpersonationSandboxEscaped): self
    {
        $this->isImpersonationSandboxEscaped = $isImpersonationSandboxEscaped;

        return $this;
    }

    public function isUpdater3k(TokenInterface $token, $object): bool
    {
        $user = $token->getUser();

        if (!($user instanceof Usr)) {
            return false;
        }

        // load roles from database to enable quick switch off
        // otherwise roles will be cached in session until next logon
        return $user->isUpdater3k();
    }

    public function isEnableTranshelper(TokenInterface $token)
    {
        if (
            !(
                ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
                && ($request = $requestStack->getMasterRequest())
            )
        ) {
            return false;
        }

        $user = $token->getUser();

        return $this->container->get(TransHelper::class)->isEnabled(
            $request,
            $user instanceof Usr ? $user : null
        );
    }

    public function is2faEnabled(TokenInterface $token): bool
    {
        $user = $token->getUser();

        return ($user instanceof Usr) && $user->enabled2Factor();
    }

    public function isDebugMode(): bool
    {
        return $this->container->getParameter('kernel.debug');
    }

    public function isManager2faRequired()
    {
        return $this->container->getParameter('manager.2fa_required');
    }

    protected function getAttributes()
    {
        return [
            'SITE_BUSINESS_AREA' => [$this, 'isBusiness'],
            'SITE_BOOKER_AREA' => [$this, 'isBookerArea'],
            'SITE_BOOKING_AREA' => [$this, 'isBooking'],
            'SITE_MOBILE_AREA' => [$this, 'isMobile'],
            'SITE_DEV_MODE' => [$this, 'isDevMode'],
            'SITE_DEBUG_MODE' => [$this, 'isDebugMode'],
            'SITE_PROD_MODE' => [$this, 'isProdMode'],
            'SITE_LOCAL_PROD_MODE' => [$this, 'isLocalProd'],
            'SITE_ND_SWITCH' => [$this, 'isNewDesign'],
            'USER_IMPERSONATED' => [$this, 'isImpersonated'],
            'USER_AWPLUS' => [$this, 'isAwPlus'],
            'USER_IMPERSONATED_AS_SUPER' => [$this, 'isImpersonatedAsSuper'],
            'NOT_USER_IMPERSONATED' => [$this, 'isNotImpersonated'],
            'USER_BUSINESS_ADMIN' => [$this, 'isBusinessAdmin'],
            'USER_BOOKING_ADMIN' => [$this, 'isBookingAdmin'],
            'USER_BOOKING_MANAGER' => [$this, 'isBookingManager'],
            'USER_BOOKING_REFERRAL' => [$this, 'isBookerArea'],
            'USER_BOOKING_PARTNER' => [$this, 'isBookerArea'],
            'USER_BOOKING_AW' => [$this, 'isAwBooker'],
            'SITE_MOBILE_APP' => [$this, 'isMobileApp'],
            'SITE_MOBILE_APP_ANDROID' => [$this, 'isMobileAppAndroid'],
            'SITE_MOBILE_APP_IOS' => [$this, 'isMobileAppIos'],
            'SITE_MOBILE_APP_REACT_NATIVE' => [$this, 'isMobileAppReactNative'],
            'SITE_MOBILE_VERSION_SUITABLE' => [$this, 'isMobileVersionSuitable'],
            'SITE_MOBILE_BROWSER' => [$this, 'isMobileBrowser'],
            'SITE_FROM_APP' => [$this, 'isFromApp'],
            'FULL_IMPERSONATE' => [$this, 'isFullImpersonate'],
            'UPDATER_3K' => [$this, 'isUpdater3k'],
            'USER_ENABLE_TRANSHELPER' => [$this, 'isEnableTranshelper'],
            'USER_2FA_ENABLED' => [$this, 'is2faEnabled'],
            'SITE_MANAGER_2FA_REQUIRED' => [$this, 'isManager2faRequired'],
        ];
    }

    protected function getClass()
    {
        return null;
    }
}
