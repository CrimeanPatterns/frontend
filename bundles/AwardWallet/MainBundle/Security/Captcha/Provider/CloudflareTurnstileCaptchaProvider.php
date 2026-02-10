<?php

namespace AwardWallet\MainBundle\Security\Captcha\Provider;

use AwardWallet\MainBundle\Security\Captcha\Validator\CaptchaValidatorInterface;

class CloudflareTurnstileCaptchaProvider implements CaptchaProviderInterface
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
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js?compat=recaptcha&render=explicit&onload=' . $onLoadCallback;
    }

    public function getContainerSelector(): string
    {
        return '#recaptcha-container';
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function getContainerSize(): string
    {
        return 'normal';
    }

    public function getValidator(): CaptchaValidatorInterface
    {
        return $this->recaptchaValidator;
    }

    public function getAppearance(): ?string
    {
        return 'interaction-only';
    }

    public function getVendor(): string
    {
        return 'cloudflare_turnstile';
    }
}
