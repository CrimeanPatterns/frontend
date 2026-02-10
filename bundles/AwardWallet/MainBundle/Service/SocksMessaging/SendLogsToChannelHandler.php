<?php

namespace AwardWallet\MainBundle\Service\SocksMessaging;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Monolog\Handler\AbstractHandler;

/**
 * @NoDI
 */
class SendLogsToChannelHandler extends AbstractHandler
{
    private string $channel;

    private Client $client;

    public function __construct(int $level, string $channel, Client $client)
    {
        parent::__construct($level, true);
        $this->channel = $channel;
        $this->client = $client;
    }

    public function handle(array $record)
    {
        $this->client->publish($this->channel, ["type" => "log", "message" => $record['message']]);

        return false;
    }
}
