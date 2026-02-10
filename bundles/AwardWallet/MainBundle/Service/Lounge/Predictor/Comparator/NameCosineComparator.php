<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\Predictor\ComparatorInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;

class NameCosineComparator implements ComparatorInterface
{
    public function compare(LoungeNormalized $lounge1, LoungeNormalized $lounge2): float
    {
        $name1 = $lounge1->getNameNormalizedWithoutStopWords();
        $name2 = $lounge2->getNameNormalizedWithoutStopWords();

        if (empty($name1) && empty($name2)) {
            return 1.0;
        }

        if (empty($name1) || empty($name2)) {
            return 0.5;
        }

        $cosineSimilarity = $this->calculateCosineSimilarity($name1, $name2);
        $name1NoSpaces = preg_replace('/\s+/', '', $name1);
        $name2NoSpaces = preg_replace('/\s+/', '', $name2);
        // use 3-grams for better similarity detection
        $charNGramSimilarity = $this->calculateCharNGramSimilarity($name1NoSpaces, $name2NoSpaces, 3);

        return max($cosineSimilarity, $charNGramSimilarity);
    }

    /**
     * Calculate cosine similarity between two strings.
     */
    private function calculateCosineSimilarity(string $str1, string $str2): float
    {
        // Split strings into words and count term frequencies
        $words1 = preg_split('/\s+/', $str1);
        $words2 = preg_split('/\s+/', $str2);

        // Create term frequency vectors
        $vector1 = array_count_values($words1);
        $vector2 = array_count_values($words2);

        // Get all unique terms
        $uniqueTerms = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));

        // Calculate dot product
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        foreach ($uniqueTerms as $term) {
            $tf1 = $vector1[$term] ?? 0;
            $tf2 = $vector2[$term] ?? 0;

            $dotProduct += $tf1 * $tf2;
            $magnitude1 += $tf1 * $tf1;
            $magnitude2 += $tf2 * $tf2;
        }

        // Prevent division by zero
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        // Calculate cosine similarity
        return $dotProduct / (sqrt($magnitude1) * sqrt($magnitude2));
    }

    private function calculateCharNGramSimilarity(string $str1, string $str2, int $n = 3): float
    {
        $createNGrams = function (string $str, int $n) {
            $ngrams = [];
            $len = mb_strlen($str);

            for ($i = 0; $i <= $len - $n; $i++) {
                $ngram = mb_substr($str, $i, $n);

                if (!isset($ngrams[$ngram])) {
                    $ngrams[$ngram] = 0;
                }

                $ngrams[$ngram]++;
            }

            return $ngrams;
        };

        $ngrams1 = $createNGrams($str1, $n);
        $ngrams2 = $createNGrams($str2, $n);

        $uniqueNGrams = array_unique(array_merge(array_keys($ngrams1), array_keys($ngrams2)));

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        foreach ($uniqueNGrams as $ngram) {
            $val1 = $ngrams1[$ngram] ?? 0;
            $val2 = $ngrams2[$ngram] ?? 0;

            $dotProduct += $val1 * $val2;
            $magnitude1 += $val1 * $val1;
            $magnitude2 += $val2 * $val2;
        }

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($magnitude1) * sqrt($magnitude2));
    }
}
