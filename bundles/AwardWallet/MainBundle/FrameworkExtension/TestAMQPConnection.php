<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use PhpAmqpLib\Connection\AMQPConnection;

class TestAMQPConnection extends AMQPConnection
{
    public function __construct()
    {
    }

    public function channel($channel_id = null)
    {
        return new TestAMQPChannel();
    }
}
