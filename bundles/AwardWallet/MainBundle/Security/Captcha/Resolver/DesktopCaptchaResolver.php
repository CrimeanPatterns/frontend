<?php

namespace AwardWallet\MainBundle\Security\Captcha\Resolver;

use AwardWallet\MainBundle\Security\Captcha\CloudflareTurnstileGeoDetector;
use AwardWallet\MainBundle\Security\Captcha\Provider\CaptchaProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class DesktopCaptchaResolver implements CaptchaResolverInterface
{
    private CaptchaProviderInterface $turnstileCaptchaProvider;
    private CaptchaProviderInterface $googleRecaptchaCaptcha;
    private CloudflareTurnstileGeoDetector $chinaDetector;

    /**
     * DO NOT USE AuthorizationChecker here, because it may be used in context where token storage is empty.
     */
    public function __construct(
        CaptchaProviderInterface $turnstileCaptchaProvider,
        CaptchaProviderInterface $googleRecaptchaCaptcha,
        CloudflareTurnstileGeoDetector $chinaDetector
    ) {
        $this->turnstileCaptchaProvider = $turnstileCaptchaProvider;
        $this->googleRecaptchaCaptcha = $googleRecaptchaCaptcha;
        $this->chinaDetector = $chinaDetector;
    }

    public function resolve(Request $request): CaptchaProviderInterface
    {
        return $this->chinaDetector->detect($request) ?
            $this->turnstileCaptchaProvider :
            $this->googleRecaptchaCaptcha;
    }
}
