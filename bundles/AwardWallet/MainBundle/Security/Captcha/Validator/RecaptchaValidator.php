<?php

namespace AwardWallet\MainBundle\Security\Captcha\Validator;

use Psr\Log\LoggerInterface;

class RecaptchaValidator implements CaptchaValidatorInterface
{
    private string $secret;
    private LoggerInterface $logger;
    private \HttpDriverInterface $driver;
    private bool $enabled;
    private string $url;
    private string $providerName;

    public function __construct(string $providerName, string $url, string $secret, LoggerInterface $logger, \HttpDriverInterface $driver, $enabled)
    {
        $this->secret = $secret;
        $this->logger = $logger;
        $this->driver = $driver;
        $this->enabled = $enabled;
        $this->url = $url;
        $this->providerName = $providerName;
    }

    public function validate(?string $captchaCode, ?string $remoteIp): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $response = $this->driver->request(new \HttpDriverRequest(
            $this->url,
            'POST',
            [
                'secret' => $this->secret,
                'response' => $captchaCode,
                'remoteip' => $remoteIp,
            ]
        ));
        $data = @json_decode($response->body, true);
        $valid = is_array($data) && !empty($data['success']);
        $logContext = [
            "CaptchaCode" => $captchaCode,
            "RemoteIP" => $remoteIp,
            "Response" => $data,
            "valid" => $valid,
            "captcha_provider" => $this->providerName,
        ];
        $this->logger->info("validated recaptcha", $logContext);

        if (!$valid) {
            $this->logger->warning("invalid recaptcha", $logContext);
        }

        return $valid;
    }
}
