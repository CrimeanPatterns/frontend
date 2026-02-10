<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

class TestAMQPConnectionFactory
{
    public function __construct()
    {
    }

    public function createConnection()
    {
        return new TestAMQPConnection();
    }
}
