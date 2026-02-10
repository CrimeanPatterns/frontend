<?php

namespace AwardWallet\MainBundle\Security\Captcha\Provider;

use AwardWallet\MainBundle\Security\Captcha\Validator\CaptchaValidatorInterface;

class GoogleRecaptchaCaptchaProvider implements CaptchaProviderInterface
{
    private CaptchaValidatorInterface $recaptchaValidator;
    private string $siteKey;

    public function __construct(
        CaptchaValidatorInterface $recaptchaValidator,
        string $siteKey
    ) {
        $this->recaptchaValidator = $recaptchaValidator;
        $this->siteKey = $siteKey;
    }

    public function getScriptUrl(string $onLoadCallback): string
    {
        return 'https://www.google.com/recaptcha/api.js?render=explicit&onload=' . $onLoadCallback;
    }

    public function getContainerSelector(): string
    {
        return 'recaptcha-container';
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function getContainerSize(): string
    {
        return 'invisible';
    }

    public function getValidator(): CaptchaValidatorInterface
    {
        return $this->recaptchaValidator;
    }

    public function getAppearance(): ?string
    {
        return null;
    }

    public function getVendor(): string
    {
        return 'google_recaptcha';
    }
}
