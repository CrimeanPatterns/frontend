<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Environment
{
    /**
     * @var string
     */
    private $ip;

    public function __construct(string $ip)
    {
        $this->ip = $ip;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public static function fromRequest(HttpRequest $request)
    {
        return new self($request->getClientIp());
    }
}
