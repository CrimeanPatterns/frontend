<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Monolog\Logger;

class ExcludeUserErrorProcessor
{
    private const PREFIX = 'AwardWallet\\MainBundle\\FrameworkExtension\\Exceptions\\UserErrorException:';

    /**
     * @return array
     */
    public function __invoke(array $record)
    {
        if (substr($record['message'], 0, strlen(self::PREFIX)) === self::PREFIX) {
            $record['level'] = Logger::INFO;
            $record['level_name'] = Logger::getLevelName($record['level']);
        }

        return $record;
    }
}
