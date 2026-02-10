<?php

namespace AwardWallet\MainBundle\Service\AppBot;

class AppBot
{
    private Adapter\AdapterInterface $adapter;
    private string $botName = 'AW Stat';

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param string|array $message
     */
    public function send(
        string $channelName,
        $message,
        ?string $botName = null,
        ?string $icon = null
    ) {
        return $this->adapter->send($channelName, $message, $botName ?? $this->botName, $icon);
    }

    public function uploadFile(string $filename, array $options = []): ?array
    {
        return $this->adapter->uploadFile($filename, $options);
    }
}
