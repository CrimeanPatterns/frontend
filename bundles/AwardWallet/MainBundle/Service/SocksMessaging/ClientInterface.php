<?php

namespace AwardWallet\MainBundle\Service\SocksMessaging;

interface ClientInterface
{
    public function publish($channel, $data);

    public function presence($channel);

    public function broadcast($channel, $data);

    public function generateChannelSign($client, $channel, $info = null);

    public function getClientData();
}
