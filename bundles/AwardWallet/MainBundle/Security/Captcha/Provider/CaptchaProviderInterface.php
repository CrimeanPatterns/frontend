<?php

namespace AwardWallet\MainBundle\Security\Captcha\Provider;

use AwardWallet\MainBundle\Security\Captcha\Validator\CaptchaValidatorInterface;

interface CaptchaProviderInterface
{
    public function getScriptUrl(string $onLoadCallback): string;

    public function getContainerSize(): string;

    public function getContainerSelector(): string;

    public function getSiteKey(): string;

    public function getAppearance(): ?string;

    public function getValidator(): CaptchaValidatorInterface;

    public function getVendor(): string;
}
