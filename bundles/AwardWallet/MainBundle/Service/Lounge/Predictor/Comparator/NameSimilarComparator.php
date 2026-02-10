<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\Predictor\ComparatorInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;

class NameSimilarComparator implements ComparatorInterface
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

        similar_text($name1, $name2, $similarTextPercent);
        $standardSimilarity = $similarTextPercent / 100;

        $name1NoSpaces = preg_replace('/\s+/', '', $name1);
        $name2NoSpaces = preg_replace('/\s+/', '', $name2);

        similar_text($name1NoSpaces, $name2NoSpaces, $noSpacesSimilarityPercent);
        $noSpacesSimilarity = $noSpacesSimilarityPercent / 100;

        return max($standardSimilarity, $noSpacesSimilarity);
    }
}
