<?php

namespace AwardWallet\MainBundle\Service\RA\Hotel;

class Config
{
    private bool $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function useDebugApi(): bool
    {
        return false;
    }

    public function enabledApiCallback(): bool
    {
        return $this->isProd() && !$this->useDebugApi();
    }

    private function isProd(): bool
    {
        return !$this->debug;
    }
}
