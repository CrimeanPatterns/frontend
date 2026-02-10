<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\Predictor\ComparatorInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;

class GateComparator implements ComparatorInterface
{
    public function compare(LoungeNormalized $lounge1, LoungeNormalized $lounge2): float
    {
        $lounge1Gates = [$lounge1->getGate1Structured(), $lounge1->getGate2Structured()];
        $lounge2Gates = [$lounge2->getGate1Structured(), $lounge2->getGate2Structured()];

        // Filter out empty gates
        $filteredLounge1Gates = array_values(array_filter($lounge1Gates, function ($gate) {
            return !StringHandler::isEmpty($gate['normalized']);
        }));
        $filteredLounge2Gates = array_values(array_filter($lounge2Gates, function ($gate) {
            return !StringHandler::isEmpty($gate['normalized']);
        }));

        // If both lounges have no gates, similarity is 0.8
        if (count($filteredLounge1Gates) === 0 && count($filteredLounge2Gates) === 0) {
            return 0.8;
        }

        // If one lounge has gates and another doesn't, similarity is 0.7
        if (count($filteredLounge1Gates) === 0 || count($filteredLounge2Gates) === 0) {
            return 0.7;
        }

        // Check if both lounges have the same gates (in any order)
        if ($this->haveSameGates($filteredLounge1Gates, $filteredLounge2Gates)) {
            return 1.0;
        }

        // Check if gates intersect
        if ($this->gatesIntersect($filteredLounge1Gates, $filteredLounge2Gates)) {
            return 0.95;
        }

        if (count($filteredLounge1Gates) === 1 && count($filteredLounge2Gates) === 2) {
            if ($this->isInRange($filteredLounge1Gates[0], $filteredLounge2Gates)) {
                return 0.9;
            }
        }

        if (count($filteredLounge2Gates) === 1 && count($filteredLounge1Gates) === 2) {
            if ($this->isInRange($filteredLounge2Gates[0], $filteredLounge1Gates)) {
                return 0.9;
            }
        }

        // Calculate best gate pair similarity
        $maxSimilarity = 0;

        foreach ($filteredLounge1Gates as $gate1) {
            foreach ($filteredLounge2Gates as $gate2) {
                $similarity = $this->calculateGatePairSimilarity($gate1, $gate2);
                $maxSimilarity = max($maxSimilarity, $similarity);
            }
        }

        return $maxSimilarity;
    }

    /**
     * Check if two sets of gates are the same (ignoring order).
     */
    private function haveSameGates(array $gates1, array $gates2): bool
    {
        // Must have same count
        if (count($gates1) !== count($gates2)) {
            return false;
        }

        // If both have exactly 2 gates, check for all permutations
        if (count($gates1) === 2 && count($gates2) === 2) {
            // Check regular order
            $matchCase1 = $this->gatesEqual($gates1[0], $gates2[0]) && $this->gatesEqual($gates1[1], $gates2[1]);
            // Check swapped order
            $matchCase2 = $this->gatesEqual($gates1[0], $gates2[1]) && $this->gatesEqual($gates1[1], $gates2[0]);

            return $matchCase1 || $matchCase2;
        }

        // Single gate case
        if (count($gates1) === 1 && count($gates2) === 1) {
            return $this->gatesEqual($gates1[0], $gates2[0]);
        }

        return false;
    }

    /**
     * Check if two gate arrays intersect (have at least one common gate).
     */
    private function gatesIntersect(array $gates1, array $gates2): bool
    {
        foreach ($gates1 as $gate1) {
            foreach ($gates2 as $gate2) {
                if ($this->gatesEqual($gate1, $gate2)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if two gates are exactly equal.
     */
    private function gatesEqual(array $gate1, array $gate2): bool
    {
        return isset($gate1['number'], $gate2['number'])
               && $gate1['number'] === $gate2['number']
               && $gate1['prefix'] === $gate2['prefix'];
    }

    /**
     * Calculate similarity between two gates.
     */
    private function calculateGatePairSimilarity(array $gate1, array $gate2): float
    {
        // If gates don't have numbers, rely on normalized value only
        if (!isset($gate1['number']) || !isset($gate2['number'])) {
            return $gate1['normalized'] === $gate2['normalized'] ? 1.0 : 0.3;
        }

        // If numbers are equal
        if ($gate1['number'] === $gate2['number']) {
            // With same prefix, perfect match
            if ($gate1['prefix'] === $gate2['prefix']) {
                return 1.0;
            }

            // Different prefixes reduce similarity
            return 0.8;
        }

        // Calculate difference in gate numbers
        $difference = abs($gate1['number'] - $gate2['number']);

        // Adjacent gates (+/- 1)
        if ($difference === 1) {
            // Same prefix, high similarity
            if ($gate1['prefix'] === $gate2['prefix']) {
                return 0.9;
            }

            // Different prefix, moderate similarity
            return 0.7;
        }

        return 0;
    }

    /**
     * Check if a gate is in the range defined by two other gates.
     */
    private function isInRange(array $gate, array $gates): bool
    {
        // Need at least two gates to define a range
        if (count($gates) < 2 || !isset($gate['number'])) {
            return false;
        }

        // Get minimum and maximum gate numbers from the gates array
        $min = PHP_INT_MAX;
        $max = PHP_INT_MIN;

        foreach ($gates as $rangeGate) {
            if (isset($rangeGate['number'])) {
                $min = min($min, $rangeGate['number']);
                $max = max($max, $rangeGate['number']);
            }
        }

        // Same prefixes have higher importance
        $samePrefixForAll = true;

        foreach ($gates as $rangeGate) {
            if (isset($rangeGate['prefix']) && $gate['prefix'] !== $rangeGate['prefix']) {
                $samePrefixForAll = false;

                break;
            }
        }

        // If prefixes are different, we require a stricter range match
        $inRange = $gate['number'] > $min && $gate['number'] < $max;

        // If prefixes are the same, we can be more lenient and include the boundaries
        if ($samePrefixForAll) {
            $inRange = $gate['number'] >= $min && $gate['number'] <= $max;
        }

        return $inRange;
    }
}
