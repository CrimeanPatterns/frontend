<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Globals\StringHandler;

class LegacyMatcher implements MatcherInterface
{
    public const NAME = 'legacy';

    private const EQUAL = 'equal';
    private const NOT_EQUAL = 'notEqual';
    private const EMPTY = 'empty';
    private const DISPARATE = 'disparate';

    public function getSimilarity(LoungeInterface $lounge1, LoungeInterface $lounge2): float
    {
        $similarityNames = $this->calculateSimilarityNames($lounge1->getName(), $lounge2->getName());
        $similarityLocation = $this->calculateSimilarityLocation($lounge1, $lounge2);
        $similarityNameWithInaccurateLocation = $this->calculateSimilarityNameWithInaccurateLocation($lounge1, $lounge2);

        return \max(
            $this->totalSimilarity(
                [$similarityNames, $similarityNames < 0.2 ? 9 : 2],
                [$similarityLocation, 8]
            ),
            $similarityNameWithInaccurateLocation
        );
    }

    public static function getThreshold(): float
    {
        return 0.7;
    }

    public static function getName(): string
    {
        return static::NAME;
    }

    /**
     * @return float similarity in range [0, 1]
     */
    private function calculateSimilarityNames(string $name1, string $name2): float
    {
        $stopWords = ['lounge', 'airport'];
        $name1 = $this->normalizeString($name1, $stopWords);
        $name2 = $this->normalizeString($name2, $stopWords);

        similar_text($name1, $name2, $percent);

        if ($percent == 100) {
            return 1;
        }

        $similarityStart = 0;

        if (
            (!empty($name2) && mb_strpos($name1, $name2) === 0)
            || (!empty($name1) && mb_strpos($name2, $name1) === 0)
        ) {
            $strlen1 = mb_strlen($name1);
            $strlen2 = mb_strlen($name2);

            if ($strlen1 !== $strlen2) {
                $shortName = $strlen1 < $strlen2 ? $name1 : $name2;
                $shortNameWords = explode(' ', $shortName);
                $shortNameWordsCount = count($shortNameWords);

                // lenght of short name should be at least 40% of lenght of long name + short name should consist of at least 2 words
                $shortNamePercent = min($strlen1, $strlen2) / max($strlen1, $strlen2);

                if ($shortNamePercent >= 0.4 && $shortNameWordsCount >= 2) {
                    $similarityStart = 0.65;
                }
            }
        }

        $simularityText = round($percent / 100, 3);
        $simularityWords = $this->calculateWordSimilarity($name1, $name2);
        $simularityLevenshtein = $this->calculateLevenshteinSimilarity($name1, $name2);

        $bySimilarity = 0;
        $strPos1 = mb_strpos($name1, 'by');
        $strPos2 = mb_strpos($name2, 'by');

        if (($strPos1 !== false && $strPos2 === false) || ($strPos1 === false && $strPos2 !== false)) {
            if ($strPos1 !== false) {
                $withBy = $name1;
                $withoutBy = $name2;
            } else {
                $withBy = $name2;
                $withoutBy = $name1;
            }

            // if one name contains 'by' and another name starts with 'by', they are similar
            if (mb_strpos($withBy, sprintf('%s by ', $withoutBy)) === 0) {
                $bySimilarity = 0.8;
            }
        }

        return \max(
            ($simularityText * 0.1) + ($simularityWords * 0.6) + ($simularityLevenshtein * 0.3),
            $similarityStart,
            $bySimilarity
        );
    }

    /**
     * @return float similarity in range [0, 1]
     */
    private function calculateSimilarityLocation(LoungeInterface $lounge1, LoungeInterface $lounge2): float
    {
        if (!$this->equalsProps($lounge1->getAirportCode(), $lounge2->getAirportCode())) {
            return 0;
        }

        $terminalSimilarity = 0;

        if ($this->equalsProps($lounge1->getTerminal(), $lounge2->getTerminal(), true)) {
            $terminalSimilarity = 1;
        } else {
            $stopWords = ['terminal', 'main', 'domestic'];
            $lounge1Terminal = $this->normalizeString($lounge1->getTerminal(), $stopWords);
            $lounge2Terminal = $this->normalizeString($lounge2->getTerminal(), $stopWords);

            if ($this->equalsProps($lounge1Terminal, $lounge2Terminal, true)) {
                $terminalSimilarity = 0.95;
            }
        }

        $gateSimilarity = $this->calculateSimilarityGates(
            [$lounge1->getGate(), $lounge1->getGate2()],
            [$lounge2->getGate(), $lounge2->getGate2()]
        );

        // compare gate prefixes with terminals
        $gatePrefixSimilarity = 0;
        $lounge1Terminal = $lounge1->getTerminal();
        $lounge1gate1 = is_null($lounge1->getGate()) ? null : $this->parseGate($lounge1->getGate());
        $lounge1gate2 = is_null($lounge1->getGate2()) ? null : $this->parseGate($lounge1->getGate2());
        $lounge2Terminal = $lounge2->getTerminal();
        $lounge2gate1 = is_null($lounge2->getGate()) ? null : $this->parseGate($lounge2->getGate());
        $lounge2gate2 = is_null($lounge2->getGate2()) ? null : $this->parseGate($lounge2->getGate2());

        foreach ([$lounge1gate1, $lounge1gate2] as $lounge1gate) {
            foreach ([$lounge2gate1, $lounge2gate2] as $lounge2gate) {
                if (
                    is_array($lounge1gate)
                    && is_array($lounge2gate)
                    && !StringHandler::isEmpty($lounge1gate['name'])
                    && !StringHandler::isEmpty($lounge2gate['name'])
                    && $lounge1gate['name'] === $lounge2gate['name']
                    && $lounge1gate['number'] === $lounge2gate['number']
                    && (
                        in_array($lounge1gate['name'], [$lounge1Terminal, $lounge2Terminal])
                        || $lounge1Terminal == $lounge2Terminal
                        || preg_match(sprintf('/^(\d%s)|(%s\d)$/ims', $lounge1gate['name'], $lounge1gate['name']), $lounge1Terminal)
                        || preg_match(sprintf('/^(\d%s)|(%s\d)$/ims', $lounge1gate['name'], $lounge1gate['name']), $lounge2Terminal)
                    )
                ) {
                    $gatePrefixSimilarity = 0.85;

                    break 2;
                }
            }
        }

        // if lounges have same name and different terminals, but one of them has no gate, they are similar
        $terminals = [null, 'main', 'domestic'];
        $nonobviousTerminalSimilarity = 0;

        if (
            $this->calculateSimilarityNames($lounge1->getName(), $lounge2->getName()) >= 0.7
            && $lounge1->getTerminal() !== $lounge2->getTerminal()
            && (
                (
                    in_array(strtolower($lounge1->getTerminal()), $terminals)
                    && in_array(strtolower($lounge2->getTerminal()), $terminals)
                )
                || (
                    (
                        in_array(strtolower($lounge1->getTerminal()), $terminals)
                        && preg_match('/^\D+$/', $lounge2->getTerminal())
                    )
                    || (
                        in_array(strtolower($lounge2->getTerminal()), $terminals)
                        && preg_match('/^\D+$/', $lounge1->getTerminal())
                    )
                )
            )
            && (
                (StringHandler::isEmpty($lounge1->getGate()) && StringHandler::isEmpty($lounge1->getGate2()))
                || (StringHandler::isEmpty($lounge2->getGate()) && StringHandler::isEmpty($lounge2->getGate2()))
            )
        ) {
            $nonobviousTerminalSimilarity = 0.69;
        }

        // if lounges have same name and not empty equal terminals, but one of them has no gate, they are similar
        $unknownGatesSimilarity = 0;

        if (
            $this->calculateSimilarityNames($lounge1->getName(), $lounge2->getName()) >= 0.7
            && $lounge1->getTerminal() === $lounge2->getTerminal()
            && !StringHandler::isEmpty($lounge1->getTerminal())
            && (
                (StringHandler::isEmpty($lounge1->getGate()) && StringHandler::isEmpty($lounge1->getGate2()))
                || (StringHandler::isEmpty($lounge2->getGate()) && StringHandler::isEmpty($lounge2->getGate2()))
            )
        ) {
            $unknownGatesSimilarity = 0.6;
        }

        // if one lounge has integer terminal and another has one letter terminal, but they have same gates, they are similar
        $letterTerminalSimilarity = 0;

        if (
            !StringHandler::isEmpty($lounge1->getTerminal())
            && !StringHandler::isEmpty($lounge2->getTerminal())
            && (
                !StringHandler::isEmpty($lounge1->getGate())
                || !StringHandler::isEmpty($lounge1->getGate2())
            )
            && $this->equalsProps($lounge1->getGate(), $lounge2->getGate(), true)
            && $this->equalsProps($lounge1->getGate2(), $lounge2->getGate2(), true)
            && (
                (
                    preg_match('/^\d$/', $lounge1->getTerminal())
                    && preg_match('/^[a-z]$/i', $lounge2->getTerminal())
                )
                || (
                    preg_match('/^\d$/', $lounge2->getTerminal())
                    && preg_match('/^[a-z]$/i', $lounge1->getTerminal())
                )
            )
        ) {
            $letterTerminalSimilarity = 0.78;
        }

        return max(
            $this->totalSimilarity(
                [$terminalSimilarity, 6],
                [$gateSimilarity, 7]
            ),
            $gatePrefixSimilarity,
            $nonobviousTerminalSimilarity,
            $unknownGatesSimilarity,
            $letterTerminalSimilarity
        );
    }

    /**
     * @param array $range1 [?string, ?string]
     * @param array $range2 [?string, ?string]
     * @return float similarity in range [0, 1]
     */
    private function calculateSimilarityGates(array $range1, array $range2): float
    {
        if ($this->equalsProps($range1[0], $range2[0], true) && $this->equalsProps($range1[1], $range2[1], true)) {
            if (StringHandler::isEmpty($range1[0]) && StringHandler::isEmpty($range1[1])) {
                return 0.5;
            }

            return 1;
        }

        $isRange1Empty = $this->compareProps($range1[0], $range1[1]) === self::EMPTY;
        $isRange2Empty = $this->compareProps($range2[0], $range2[1]) === self::EMPTY;

        if (
            ($isRange1Empty && !$isRange2Empty)
            || (!$isRange1Empty && $isRange2Empty)
        ) {
            return 0.5;
        }

        $parsedRange1 = array_filter([
            is_null($range1[0]) ? null : $this->parseGate($this->normalizeString($range1[0])),
            is_null($range1[1]) ? null : $this->parseGate($this->normalizeString($range1[1])),
        ]);
        $parsedRange2 = array_filter([
            is_null($range2[0]) ? null : $this->parseGate($this->normalizeString($range2[0])),
            is_null($range2[1]) ? null : $this->parseGate($this->normalizeString($range2[1])),
        ]);
        $hits = 0;

        foreach ($parsedRange1 as $parsedGate1) {
            foreach ($parsedRange2 as $parsedGate2) {
                if (
                    !in_array($this->compareProps($parsedGate1['name'], $parsedGate2['name']), [self::NOT_EQUAL])
                    && $this->equalsProps($parsedGate1['number'], $parsedGate2['number'])
                ) {
                    $hits++;

                    continue 2;
                }
            }
        }

        switch ($hits) {
            case 0:
                return 0;

            case 1:
                return 0.75;

            default:
                return 0.9;
        }
    }

    private function calculateSimilarityNameWithInaccurateLocation(LoungeInterface $lounge1, LoungeInterface $lounge2): float
    {
        if (!$this->equalsProps($lounge1->getAirportCode(), $lounge2->getAirportCode())) {
            return 0;
        }

        // if at least one lounge has empty terminal and gates, then we take only name similarity as a base
        if (
            (
                StringHandler::isEmpty($lounge1->getTerminal())
                && StringHandler::isEmpty($lounge1->getGate())
                && StringHandler::isEmpty($lounge1->getGate2())
            )
            || (
                StringHandler::isEmpty($lounge2->getTerminal())
                && StringHandler::isEmpty($lounge2->getGate())
                && StringHandler::isEmpty($lounge2->getGate2())
            )
        ) {
            return $this->calculateSimilarityNames($lounge1->getName(), $lounge2->getName());
        }

        return 0;
    }

    private function normalizeString(?string $string, array $stopWords = []): ?string
    {
        if (is_null($string)) {
            return null;
        }

        // remove stop words
        if ($stopWords) {
            $string = preg_replace(sprintf('/\b(%s)\b/iu', implode('|', $stopWords)), ' ', $string);
        }

        return mb_strtolower(trim(preg_replace(
            ['/[^\p{L}\p{N}\s]/u', '/\s{2,}/'],
            ['', ' '],
            $string
        )));
    }

    private function calculateWordSimilarity($string1, $string2): float
    {
        $words1 = explode(' ', $string1);
        $words2 = explode(' ', $string2);
        $commonWords = array_intersect($words1, $words2);

        return count($commonWords) / max(count($words1), count($words2));
    }

    private function calculateLevenshteinSimilarity($string1, $string2): float
    {
        $maxLen = max(mb_strlen($string1), mb_strlen($string2));
        $distance = levenshtein($string1, $string2);

        if ($maxLen === 0) {
            return 0;
        }

        return 1 - ($distance / $maxLen);
    }

    /**
     * @return array|null ['name' => string, 'number' => int]
     */
    private function parseGate(string $gate): ?array
    {
        if (preg_match('/^(.*?)(\d+)$/ims', $gate, $matches)) {
            return [
                'name' => $matches[1],
                'number' => (int) $matches[2],
            ];
        }

        return null;
    }

    private function equalsProps(?string $prop1, ?string $prop2, bool $orEmpty = false): bool
    {
        $res = $this->compareProps($prop1, $prop2);

        return $res === self::EQUAL || ($orEmpty && $res === self::EMPTY);
    }

    private function compareProps(?string $prop1, ?string $prop2): string
    {
        if (StringHandler::isEmpty($prop1) && StringHandler::isEmpty($prop2)) {
            return self::EMPTY;
        } elseif (!StringHandler::isEmpty($prop1) && !StringHandler::isEmpty($prop2)) {
            if (strcasecmp($prop1, $prop2) === 0) {
                return self::EQUAL;
            } else {
                return self::NOT_EQUAL;
            }
        }

        return self::DISPARATE;
    }

    /**
     * @return float similarity in range [0, 1]
     */
    private function totalSimilarity(...$params): float
    {
        $sumWeights = array_sum(array_map(function ($param) {
            return $param[1];
        }, $params));
        $sum = 0;
        $maxSum = 0;

        foreach ($params as $param) {
            [$similarity, $weight] = $param;
            $weight /= $sumWeights;
            $sum += $similarity * $weight;
            $maxSum += $weight;
        }

        return $sum / $maxSum;
    }
}
