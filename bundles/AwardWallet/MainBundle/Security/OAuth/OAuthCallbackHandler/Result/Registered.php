<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result;

class Registered implements CallbackResultInterface
{
    /**
     * @var string|null
     */
    private $targetUrl;
    /**
     * @var string
     */
    private $email;

    public function __construct(string $email, ?string $targetUrl)
    {
        $this->targetUrl = $targetUrl;
        $this->email = $email;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
