<?php

namespace AwardWallet\MainBundle\Security\Captcha\Resolver;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Security\Captcha\CloudflareTurnstileGeoDetector;
use AwardWallet\MainBundle\Security\Captcha\Provider\CaptchaProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class MobileCaptchaResolver implements CaptchaResolverInterface
{
    private ApiVersioningService $versioning;
    private CloudflareTurnstileGeoDetector $chinaDetector;
    private CaptchaProviderInterface $cloudflareTurnstileProvider;
    private CaptchaProviderInterface $siteRecaptchaProvider;
    private CaptchaProviderInterface $recaptchaV2NotARobotProvider;
    private CaptchaProviderInterface $recaptchaV2AndroidProvider;

    /**
     * DO NOT USE AuthorizationChecker here, because it may be used in context where token storage is empty.
     */
    public function __construct(
        CaptchaProviderInterface $cloudflareTurnstileProvider,
        CaptchaProviderInterface $siteRecaptchaProvider,
        CaptchaProviderInterface $recaptchaV2NotARobotProvider,
        CaptchaProviderInterface $recaptchaV2AndroidProvider,
        ApiVersioningService $versioning,
        CloudflareTurnstileGeoDetector $chinaDetector
    ) {
        $this->versioning = $versioning;
        $this->chinaDetector = $chinaDetector;
        $this->cloudflareTurnstileProvider = $cloudflareTurnstileProvider;
        $this->siteRecaptchaProvider = $siteRecaptchaProvider;
        $this->recaptchaV2NotARobotProvider = $recaptchaV2NotARobotProvider;
        $this->recaptchaV2AndroidProvider = $recaptchaV2AndroidProvider;
    }

    public function resolve(Request $request): CaptchaProviderInterface
    {
        $isNative = $this->versioning->supports(MobileVersions::NATIVE_APP);
        $platform = \strtolower($request->headers->get(MobileHeaders::MOBILE_PLATFORM));
        $isAndroidV2Key = $isNative && (MobileVersions::ANDROID === $platform);
        $isNotARobotKey = $isNative && (
            (MobileVersions::IOS === $platform)
            || $this->versioning->supports(MobileVersions::ANDROID_WEBVIEW_CAPTCHA)
        );

        if ($isNotARobotKey) {
            return $this->recaptchaV2NotARobotProvider;
        }

        if ($isAndroidV2Key) {
            return $this->recaptchaV2AndroidProvider;
        }

        if ($this->chinaDetector->detect($request)) {
            return $this->cloudflareTurnstileProvider;
        }

        return $this->siteRecaptchaProvider;
    }
}
