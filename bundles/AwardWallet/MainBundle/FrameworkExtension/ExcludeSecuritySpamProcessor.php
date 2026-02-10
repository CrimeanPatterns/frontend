<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Monolog\Logger;

class ExcludeSecuritySpamProcessor
{
    /**
     * @return array
     */
    public function __invoke(array $record)
    {
        if ($record['message'] === 'Populated the TokenStorage with an anonymous Token.') {
            $record['level'] = Logger::DEBUG;
            $record['level_name'] = Logger::getLevelName($record['level']);
        }

        return $record;
    }
}
