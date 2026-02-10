<?php

namespace AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\HttpFoundation\Request;

class SymfonyContext
{
    private Usr $user;
    private Request $request;
    private ?int $impersonatorUserId;

    public function __construct(Usr $user, Request $request, ?int $impersonatorUserId = null)
    {
        $this->user = $user;
        $this->request = $request;
        $this->impersonatorUserId = $impersonatorUserId;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getImpersonatorUserId(): ?int
    {
        return $this->impersonatorUserId;
    }
}
