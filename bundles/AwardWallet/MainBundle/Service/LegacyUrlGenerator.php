<?php

namespace AwardWallet\MainBundle\Service;

class LegacyUrlGenerator
{
    private string $channel;

    private string $host;

    public function __construct(string $channel, string $host)
    {
        $this->channel = $channel;
        $this->host = $host;
    }

    public function generateAbsoluteUrl(string $path): string
    {
        $path = \ltrim($path, '/');

        return "{$this->channel}://{$this->host}/{$path}";
    }
}
