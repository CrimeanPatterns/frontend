<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Tokens
{
    /**
     * @var string
     */
    private $accessToken;
    /**
     * @var string|null
     */
    private $refreshToken;

    public function __construct(string $accessToken, ?string $refreshToken)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }
}
