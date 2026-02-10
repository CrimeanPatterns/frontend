<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\Predictor\ComparatorInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;

class NameLevenshteinComparator implements ComparatorInterface
{
    public function compare(LoungeNormalized $lounge1, LoungeNormalized $lounge2): float
    {
        $name1 = $lounge1->getNameNormalizedWithoutStopWords();
        $name2 = $lounge2->getNameNormalizedWithoutStopWords();

        if (empty($name1) && empty($name2)) {
            return 1;
        }

        if (empty($name1) || empty($name2)) {
            return 0.5;
        }

        $maxLen = max(mb_strlen($name1), mb_strlen($name2));
        $minLen = min(mb_strlen($name1), mb_strlen($name2));
        $distance = levenshtein($name1, $name2);

        // Better normalization formula taking into account both max length and min length
        $lengthRatio = $minLen / $maxLen;
        $distanceRatio = 1 - ($distance / $maxLen);

        // Combine the ratios to get a better similarity measure
        $standardSimilarity = ($distanceRatio * 0.7) + ($lengthRatio * 0.3);

        $name1NoSpaces = preg_replace('/\s+/', '', $name1);
        $name2NoSpaces = preg_replace('/\s+/', '', $name2);
        $maxLenNoSpaces = max(mb_strlen($name1NoSpaces), mb_strlen($name2NoSpaces));
        $minLenNoSpaces = min(mb_strlen($name1NoSpaces), mb_strlen($name2NoSpaces));
        $distanceNoSpaces = levenshtein($name1NoSpaces, $name2NoSpaces);

        $lengthRatioNoSpaces = $minLenNoSpaces / $maxLenNoSpaces;
        $distanceRatioNoSpaces = 1 - ($distanceNoSpaces / $maxLenNoSpaces);
        $noSpacesSimilarity = ($distanceRatioNoSpaces * 0.7) + ($lengthRatioNoSpaces * 0.3);
        $similarity = max($standardSimilarity, $noSpacesSimilarity);

        if ($similarity > 0.8) {
            $similarity = 0.8 + ($similarity - 0.8) * 2;
        }

        return max(0, min(1, $similarity));
    }
}
