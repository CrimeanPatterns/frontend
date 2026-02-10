<?php

namespace AwardWallet\MainBundle\Security\Captcha\Validator;

interface CaptchaValidatorInterface
{
    public function validate(?string $captchaCode, ?string $remoteIp): bool;
}
