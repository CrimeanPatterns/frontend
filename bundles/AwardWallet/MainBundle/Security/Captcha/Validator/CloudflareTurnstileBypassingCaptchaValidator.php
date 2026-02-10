<?php

namespace AwardWallet\MainBundle\Security\Captcha\Validator;

use Psr\Log\LoggerInterface;

class CloudflareTurnstileBypassingCaptchaValidator implements CaptchaValidatorInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function validate(?string $captchaCode, ?string $remoteIp): bool
    {
        $this->logger->warning("bypassing captcha from China", [
            "CaptchaCode" => null,
            "RemoteIP" => $remoteIp,
            "Response" => null,
            "valid" => false,
        ]);

        return true;
    }
}
