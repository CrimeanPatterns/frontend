<?php

namespace AwardWallet\MainBundle\Service\AppBot\Adapter;

use Psr\Log\LoggerInterface;

class NullAdapter implements AdapterInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function send(string $channelName, $message, ?string $botname = null, ?string $icon = null): bool
    {
        $this->logger->info("[AppBot] [$channelName] $message");

        return true;
    }
}
