<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result;

class MissingMailboxAccess implements CallbackResultInterface
{
    private ?string $loginHint;
    private bool $registered;
    private string $email;

    public function __construct(?string $loginHint, string $email, bool $registered)
    {
        $this->loginHint = $loginHint;
        $this->registered = $registered;
        $this->email = $email;
    }

    public function getLoginHint(): ?string
    {
        return $this->loginHint;
    }

    /**
     * @return bool - true if user was registered, false if user was logged in
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
