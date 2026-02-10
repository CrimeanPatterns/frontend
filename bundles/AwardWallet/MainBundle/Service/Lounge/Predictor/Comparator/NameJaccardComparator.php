<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\Predictor\ComparatorInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;

class NameJaccardComparator implements ComparatorInterface
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

        $words1 = preg_split('/\s+/', $name1);
        $words2 = preg_split('/\s+/', $name2);
        $uniqueWords = array_unique(array_merge($words1, $words2));
        $intersectionWords = array_intersect($words1, $words2);
        $jaccardSimilarity = count($uniqueWords) > 0
            ? count($intersectionWords) / count($uniqueWords)
            : 0;

        $name1NoSpaces = preg_replace('/\s+/', '', $name1);
        $name2NoSpaces = preg_replace('/\s+/', '', $name2);
        $ngrams1 = array_keys($this->createCharNGrams($name1NoSpaces, 3));
        $ngrams2 = array_keys($this->createCharNGrams($name2NoSpaces, 3));
        $uniqueNgrams = array_unique(array_merge($ngrams1, $ngrams2));
        $intersectionNgrams = array_intersect($ngrams1, $ngrams2);
        $jaccardNgramSimilarity = count($uniqueNgrams) > 0
            ? count($intersectionNgrams) / count($uniqueNgrams)
            : 0;

        return max($jaccardSimilarity, $jaccardNgramSimilarity);
    }

    private function createCharNGrams(string $str, int $n): array
    {
        $ngrams = [];
        $len = mb_strlen($str);

        for ($i = 0; $i <= $len - $n; $i++) {
            $ngram = mb_substr($str, $i, $n);
            $ngrams[$ngram] = true;
        }

        return $ngrams;
    }
}
