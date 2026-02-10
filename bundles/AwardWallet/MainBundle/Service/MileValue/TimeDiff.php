<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class TimeDiff
{
    public static function format(int $diff): string
    {
        $days = (int) floor($diff / 86400);
        $diff -= $days * 86400;
        $hours = (int) floor($diff / 3600);
        $diff -= $hours * 3600;
        $minutes = (int) round($diff / 60);

        $result = "";

        if ($days > 0) {
            $result .= $days . "d";
        }

        if ($hours > 0 && $days < 2) {
            $result .= $hours . "h";
        }

        if ($minutes > 0 && $days === 0 && $hours < 12) {
            $result .= $minutes . "m";
        }

        return $result;
    }
}
