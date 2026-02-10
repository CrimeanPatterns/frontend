<?php

namespace AwardWallet\MainBundle\Tests;

use AwardWallet\MainBundle\Security\BotIpDetector;

class BotIpDetectorMock extends BotIpDetector
{
    public function getBotIps(): array
    {
        return [];
    }
}
