<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;

class CallableStep extends AbstractStep
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function check(Credentials $credentials): bool
    {
        return ($this->callable)($credentials);
    }

    protected function doCheck(Credentials $credentials): void
    {
    }
}
