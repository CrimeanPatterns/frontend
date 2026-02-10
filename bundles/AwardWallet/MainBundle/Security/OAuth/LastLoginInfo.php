<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class LastLoginInfo
{
    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $provider;

    /**
     * @var UserInfo
     */
    private $userInfo;

    /**
     * @var Tokens
     */
    private $tokens;

    public function __construct(int $userId, string $provider, UserInfo $userInfo, ?Tokens $tokens)
    {
        $this->userId = $userId;
        $this->provider = $provider;
        $this->userInfo = $userInfo;
        $this->tokens = $tokens;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getUserInfo(): UserInfo
    {
        return $this->userInfo;
    }

    public function getTokens(): ?Tokens
    {
        return $this->tokens;
    }
}
