<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result;

class LoggedIn implements CallbackResultInterface
{
    /**
     * @var string
     */
    private $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
