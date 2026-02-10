<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @NoDI()
 */
class RegistratorResult
{
    /**
     * @var bool
     */
    private $success;
    /**
     * @var Usr|null
     */
    private $user;
    /**
     * @var string|null
     */
    private $targetUrl;

    public function __construct(bool $success, ?Usr $user, ?string $targetUrl)
    {
        $this->success = $success;
        $this->user = $user;
        $this->targetUrl = $targetUrl;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }
}
