<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\Predictor\ComparatorInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;

class NameContainsComparator implements ComparatorInterface
{
    public function compare(LoungeNormalized $lounge1, LoungeNormalized $lounge2): float
    {
        $name1 = $lounge1->getNameNormalized();
        $name2 = $lounge2->getNameNormalized();

        if (empty($name1) && empty($name2)) {
            return 1.0;
        }

        if (empty($name1) || empty($name2)) {
            return 0.5;
        }

        $len1 = mb_strlen($name1);
        $len2 = mb_strlen($name2);

        if (mb_strpos($name1, $name2) !== false) {
            // Calculate similarity based on how much of the longer string is covered
            // Shorter string fully contained in longer one
            return 0.5 + (0.5 * $len2 / $len1);
        }

        if (mb_strpos($name2, $name1) !== false) {
            // Calculate similarity based on how much of the longer string is covered
            return 0.5 + (0.5 * $len1 / $len2);
        }

        $name1NoSpaces = preg_replace('/\s+/', '', $name1);
        $name2NoSpaces = preg_replace('/\s+/', '', $name2);
        $len1NoSpaces = mb_strlen($name1NoSpaces);
        $len2NoSpaces = mb_strlen($name2NoSpaces);

        if (mb_strpos($name1NoSpaces, $name2NoSpaces) !== false) {
            return 0.4 + (0.5 * $len2NoSpaces / $len1NoSpaces);
        }

        if (mb_strpos($name2NoSpaces, $name1NoSpaces) !== false) {
            return 0.4 + (0.5 * $len1NoSpaces / $len2NoSpaces);
        }

        return 0.0;
    }
}
