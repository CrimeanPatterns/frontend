<?php

namespace AwardWallet\MainBundle\Globals;

class DeprecationUtils
{
    /**
     * @param string $name distinguishable name for logs aggregation
     */
    public static function alert($name)
    {
        getSymfonyContainer()->get('logger')->warning(
            'deprecation_alert',
            [
                'stack' => StackTraceUtils::getFilteredStackTrace(['file', 'line'], 1), // skip self::alert() frame
                'name' => $name,
            ]
        );
    }
}
