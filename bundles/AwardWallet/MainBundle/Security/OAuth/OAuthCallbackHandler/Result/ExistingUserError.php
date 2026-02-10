<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result;

class ExistingUserError extends AbstractError
{
    /**
     * @var string
     */
    private $email;

    public function __construct(string $email, string $errorText)
    {
        parent::__construct($errorText);

        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
