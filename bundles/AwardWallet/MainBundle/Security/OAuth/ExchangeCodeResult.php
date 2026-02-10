<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class ExchangeCodeResult
{
    /**
     * @var UserInfo
     */
    private $userInfo;
    /**
     * @var Tokens|null
     */
    private $tokens;
    /**
     * @var string|null
     */
    private $error;
    private ?bool $mailboxAccess;

    public function __construct(?UserInfo $userInfo, ?Tokens $tokens, ?string $error, ?bool $haveMailboxAccess = null)
    {
        $this->userInfo = $userInfo;
        $this->tokens = $tokens;
        $this->error = $error;
        $this->mailboxAccess = $haveMailboxAccess;
    }

    public function getUserInfo(): ?UserInfo
    {
        return $this->userInfo;
    }

    public function getTokens(): ?Tokens
    {
        return $this->tokens;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getMailboxAccess(): ?bool
    {
        return $this->mailboxAccess;
    }
}
