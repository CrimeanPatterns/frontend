<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Monolog\Logger;

class LoggingLevel
{
    public function getLevel(): int
    {
        if (php_sapi_name() === 'cli') {
            return Logger::INFO;
        }

        return Logger::INFO;
    }
}
