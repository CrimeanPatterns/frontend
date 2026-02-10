<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result;

abstract class AbstractError implements CallbackErrorInterface
{
    /**
     * @var string
     */
    private $errorText;

    public function __construct(string $errorText)
    {
        $this->errorText = $errorText;
    }

    public function getTextError(): ?string
    {
        return $this->errorText;
    }
}
