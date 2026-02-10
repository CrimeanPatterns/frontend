<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\Predictor\ComparatorInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\Helper;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;

class TerminalComparator implements ComparatorInterface
{
    /**
     * Common terminal equivalents grouped by semantic meaning.
     */
    private const TERMINAL_EQUIVALENTS = [
        // Main/Primary terminals
        ['1', 'main', 'central', 'domestic', 'a', 'primary', 'principal', 'core', 'one', 'first', 'm'],
        // International terminals
        ['i', 'international', 'int', 'intl', 'foreign', 'overseas', 'global'],
        // Numbered terminals and their variants
        ['2', 'two', 'second', 'b'],
        ['3', 'three', 'third', 'c'],
        ['4', 'four', 'fourth', 'd'],
        ['5', 'five', 'fifth', 'e'],
        // Departure/Arrival areas
        ['departure', 'departures', 'dep', 'outbound', 'outgoing'],
        ['arrival', 'arrivals', 'arr', 'inbound', 'incoming'],
        // Domestic/Regional terminals
        ['regional', 'commuter', 'local', 'domestic', 'national'],
        // Premium areas
        ['vip', 'premium', 'priority', 'executive', 'elite', 'diplomatic', 'luxury', 'first', 'business', 'gold'],
    ];

    /**
     * Geographic direction groups for terminals.
     */
    private const GEO_GROUPS = [
        ['north', 'n', 'northeast', 'ne', 'northwest', 'nw', 'northern'],
        ['south', 's', 'southeast', 'se', 'southwest', 'sw', 'southern'],
        ['east', 'e', 'northeast', 'ne', 'southeast', 'se', 'eastern'],
        ['west', 'w', 'northwest', 'nw', 'southwest', 'sw', 'western'],
    ];

    /**
     * Prefixes to remove for cleaner terminal matching.
     */
    private const PREFIXES_TO_REMOVE = [
        '/terminal/',
        '/^t(?=\d)/',
        '/^term/',
        '/^pier/',
        '/^gate/',
        '/^concourse/',
        '/^hall/',
        '/^building/',
        '/^wing/',
        '/^area/',
        '/zone/',
        '/section/',
    ];

    public function compare(LoungeNormalized $lounge1, LoungeNormalized $lounge2): float
    {
        $terminal1 = $lounge1->getTerminalNormalized();
        $terminal2 = $lounge2->getTerminalNormalized();

        if (Helper::equals($terminal1, $terminal2)) {
            return 1.0;
        }

        // Both terminals are empty
        if (empty($terminal1) && empty($terminal2)) {
            return 0.8;
        }

        // Only one terminal is empty
        if (empty($terminal1) || empty($terminal2)) {
            return 0.7;
        }

        // Normalize and clean terminal strings
        $norm1 = $this->normalizeTerminal($terminal1);
        $norm2 = $this->normalizeTerminal($terminal2);

        if (empty($norm1) || empty($norm2)) {
            return 0.7;
        }

        // After normalization, check for exact match
        if ($norm1 === $norm2 && !empty($norm1)) {
            return 0.95;
        }

        // Check for semantically equivalent terminals
        $equivalenceScore = $this->checkEquivalentTerminals($norm1, $norm2);

        if ($equivalenceScore > 0) {
            return $equivalenceScore;
        }

        // Check for alphanumeric similarity when terminals are short
        if (strlen($norm1) <= 3 && strlen($norm2) <= 3) {
            $similarityScore = $this->calculateShortTerminalSimilarity($norm1, $norm2);

            if ($similarityScore > 0) {
                return $similarityScore;
            }
        }

        // Check for partial matches and contained substrings
        $partialMatchScore = $this->checkPartialMatch($norm1, $norm2);

        if ($partialMatchScore > 0) {
            return $partialMatchScore;
        }

        return 0.0;
    }

    /**
     * Normalize terminal string for comparison.
     */
    private function normalizeTerminal(string $terminal): string
    {
        // Convert to lowercase and remove non-alphanumeric characters
        $cleaned = mb_strtolower(trim(preg_replace('/[^a-z0-9]/i', '', $terminal)));

        // Remove common prefixes
        return preg_replace(self::PREFIXES_TO_REMOVE, '', $cleaned);
    }

    /**
     * Check if terminals are semantically equivalent.
     */
    private function checkEquivalentTerminals(string $term1, string $term2): float
    {
        // Check against our dictionary of equivalent terminals
        foreach (self::TERMINAL_EQUIVALENTS as $variants) {
            if (in_array($term1, $variants) && in_array($term2, $variants)) {
                return 0.9;
            }
        }

        // Check geographic direction groups (slightly lower confidence)
        foreach (self::GEO_GROUPS as $group) {
            if (in_array($term1, $group) && in_array($term2, $group)) {
                return 0.8;
            }
        }

        // Check for mixed letter/number equivalents (e.g., "1" and "A")
        $letterNumberPairs = [
            ['1', 'a'], ['2', 'b'], ['3', 'c'], ['4', 'd'], ['5', 'e'],
        ];

        foreach ($letterNumberPairs as $pair) {
            if (($term1 === $pair[0] && $term2 === $pair[1])
                || ($term1 === $pair[1] && $term2 === $pair[0])) {
                return 0.75;
            }
        }

        return 0.0;
    }

    /**
     * Calculate similarity for short terminal names.
     */
    private function calculateShortTerminalSimilarity(string $term1, string $term2): float
    {
        // For very short strings like "1A" and "1B"
        if (strlen($term1) >= 2 && strlen($term2) >= 2) {
            // If they share the same first character and are both short
            if ($term1[0] === $term2[0]) {
                return 0.6;
            }

            // If one contains the other completely
            if (strpos($term1, $term2) !== false || strpos($term2, $term1) !== false) {
                return 0.7;
            }
        }

        // Simple Levenshtein for short terminals
        if (levenshtein($term1, $term2) === 1 && strlen($term1) > 1 && strlen($term2) > 1) {
            return 0.5;
        }

        return 0.0;
    }

    /**
     * Check for partial matches between terminals.
     */
    private function checkPartialMatch(string $term1, string $term2): float
    {
        // If one is a substring of the other
        if (strpos($term1, $term2) !== false) {
            return 0.4 + (0.1 * (strlen($term2) / strlen($term1)));
        }

        if (strpos($term2, $term1) !== false) {
            return 0.4 + (0.1 * (strlen($term1) / strlen($term2)));
        }

        // Special case for numeric terminals with close values
        if (is_numeric($term1) && is_numeric($term2)) {
            return $term1 == $term2 ? 1.0 : 0.0;
        }

        return 0.0;
    }
}
