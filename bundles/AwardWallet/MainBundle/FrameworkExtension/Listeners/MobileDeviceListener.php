<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\AwCookieFactory;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ExternalTracking\ExternalTrackingInterface;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Service\ThemeResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileDeviceListener
{
    public const EXTERNAL_TRACKING_ATTRIBUTE = 'external_tracking';

    private const FORCE_COLOR_SCHEMA_ATTRIBUTE = 'force_color_schema';

    private MobileDeviceManager $mobileDeviceManager;

    private LoggerInterface $logger;

    private AwTokenStorageInterface $tokenStorage;

    private AuthorizationCheckerInterface $authorizationChecker;

    private ThemeResolver $themeResolver;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        MobileDeviceManager $mobileDeviceManager,
        LoggerInterface $logger,
        AuthorizationCheckerInterface $authorizationChecker,
        ThemeResolver $themeResolver
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->mobileDeviceManager = $mobileDeviceManager;
        $this->logger = $logger;
        $this->authorizationChecker = $authorizationChecker;
        $this->themeResolver = $themeResolver;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!StringHandler::isEmpty($version = $request->headers->get(MobileHeaders::MOBILE_VERSION))) {
            $this->logger->info('client_info', [
                'version' => str_replace('+', '_', $version),
                'platform' => $request->headers->get(MobileHeaders::MOBILE_PLATFORM),
                'route' => $request->attributes->get('_route'),
                'locale' => $request->getLocale(),
            ]);
        }

        $colorSchema = $request->query->get('theme');

        if (
            \is_string($colorSchema)
            && $this->themeResolver->validateTheme($colorSchema)
            && (
                $this->authorizationChecker->isGranted('SITE_MOBILE_APP')
                || $this->authorizationChecker->isGranted('SITE_FROM_APP')
            )
        ) {
            $request->attributes->set(self::FORCE_COLOR_SCHEMA_ATTRIBUTE, $colorSchema);
        }

        if (!($user = $this->tokenStorage->getUser())) {
            return;
        }

        if (
            $request->headers->has(MobileHeaders::MOBILE_DEVICE_ID)
            && !in_array($locale = $request->getLocale(), ['', null], true)
        ) {
            $this->mobileDeviceManager->updateDeviceInfo(
                $user->getUserid(),
                $request->headers->get(MobileHeaders::MOBILE_DEVICE_ID),
                $locale,
                $request->headers->get(MobileHeaders::MOBILE_VERSION)
            );
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($requestTheme = $request->attributes->get(self::FORCE_COLOR_SCHEMA_ATTRIBUTE)) {
            $response->headers->setCookie(
                AwCookieFactory::createLax(
                    ThemeResolver::COOKIE_NAME,
                    $requestTheme,
                    time() + 2 * 365 * SECONDS_PER_DAY
                )
            );
        }

        $this->handleExternalTracking($request, $response);

        if ((int) $request->query->get('KeepDesktop', 0)) {
            $response->headers->setCookie(
                AwCookieFactory::createLax(
                    'KeepDesktop',
                    '1',
                    new \DateTime('+30 days'),
                    '/',
                    $request->server->get('host'),
                    false
                )
            );
            $event->setResponse($response);
        }

        if ((int) $request->query->get('fromapp', 0)) {
            $response->headers->setCookie(AwCookieFactory::createLax('fromMobileApp', '1', 0, '/', null, false, false));
            $event->setResponse($response);
        }
    }

    protected function handleExternalTracking(Request $request, Response $response): void
    {
        if (!$request->attributes->has(self::EXTERNAL_TRACKING_ATTRIBUTE)) {
            return;
        }

        /** @var ExternalTrackingInterface[] $trackingList */
        $trackingList = $request->attributes->get(self::EXTERNAL_TRACKING_ATTRIBUTE);

        if (!\is_array($trackingList)) {
            return;
        }

        $response->headers->set(
            MobileHeaders::MOBILE_EXTERNAL_TRACKING,
            \base64_encode(
                it($trackingList)
                ->map(function (ExternalTrackingInterface $externalTracking) { return $externalTracking->getData(); })
                ->toJSON()
            )
        );
    }
}
