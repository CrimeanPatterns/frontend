<?php

namespace AwardWallet\MainBundle\Service\SocksMessaging;

class NullClient implements ClientInterface
{
    public function __construct(string $cometServerUrl, string $cometServerSecret)
    {
    }

    public function publish($channel, $data)
    {
    }

    public function presence($channel)
    {
        return [[
            'body' => [
                'data' => [],
            ],
        ]];
    }

    public function broadcast($channel, $data)
    {
    }

    public function generateChannelSign($client, $channel, $info = null)
    {
        return "fake";
    }

    public function getClientData()
    {
        return [
            'url' => "http://some.host",
            'authEndpoint' => "http://some.other.host",
            'user' => 123,
            'timestamp' => time(),
            'info' => "blah",
            'token' => "wah",
            'debug' => false,
        ];
    }
}
