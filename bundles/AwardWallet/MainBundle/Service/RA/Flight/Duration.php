<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Duration
{
    public static function parseSeconds(string $duration): ?int
    {
        if (strpos($duration, ':') === false) {
            return null;
        }

        $parts = explode(':', $duration);

        return (int) $parts[0] * 3600 + (int) $parts[1] * 60;
    }
}
