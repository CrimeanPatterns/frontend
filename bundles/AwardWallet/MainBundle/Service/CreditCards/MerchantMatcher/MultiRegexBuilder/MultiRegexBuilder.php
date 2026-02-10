<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MultiRegexBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\StringUtils;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @NoDI()
 */
class MultiRegexBuilder
{
    private const START_PART = '#';
    private const END_PART = '#si';

    /**
     * @var Pattern[]
     */
    private array $regexes = [];
    /**
     * @var string[]
     */
    private array $COMMON_START_PATTERNS;
    private string $COMMON_START_PATTERN;

    public function __construct()
    {
        $COMMON_START_PATTERNS = [
            '^',
            '.',
            '\\b',
            '\\s',
            '\\w',
            ' ',
            '_',
            '-',
        ];
        $COMMON_START_PATTERNS = \array_merge(
            $COMMON_START_PATTERNS,
            \range('A', 'Z'),
            \range('0', '9'),
            [
                '&',
                '%',
                '@',
                '!',
                ';',
                ':',
                '~',
                '>',
                '<',
                '=',
                '-',
            ]
        );
        // add modifiers combinators
        $COMMON_START_PATTERNS = \array_merge(
            $COMMON_START_PATTERNS,
            it($COMMON_START_PATTERNS)
            ->drop(1)
            ->flatMap(fn (string $pattern) => [
                "{$pattern}+",
                "{$pattern}*",
                $pattern,
            ])
            ->flatMap(function (string $pattern) {
                $hasEscape = false !== \strpos($pattern, '\\');

                foreach (['(%s)', '(?:%s)'] as $format) {
                    $formatted = \sprintf($format, $pattern);

                    if ($hasEscape) {
                        yield "{$formatted}?";
                    }

                    yield $formatted;
                }

                if (!$hasEscape) {
                    yield "{$pattern}?";
                }

                yield $pattern;
            })
            ->toArray()
        );

        $suffixesNoStartMatch =
            '(?:'
            . it($COMMON_START_PATTERNS)
                ->drop(1) // drop ^
                ->map(fn (string $suffix) => \preg_quote($suffix, '#'))
                ->joinToString('|')
            . ')';

        $prefixesMatch =
            '(?'
            . it($COMMON_START_PATTERNS)
                ->mapIndexed(fn (string $suffix, $idx) =>
                    '|'
                    . \preg_quote($suffix, '#')
                    . "(*:{$idx})"
                )
                ->joinToString()
            . ')';

        $this->COMMON_START_PATTERNS = $COMMON_START_PATTERNS;
        $this->COMMON_START_PATTERN = "#^{$prefixesMatch}{$suffixesNoStartMatch}#i";
    }

    public function addNeedle(string $needle, string $key, array $merchantPatternData): void
    {
        $pattern = \preg_quote($needle, '#');
        $this->regexes[$key] = new Pattern(
            $pattern,
            $pattern,
            $key,
            $merchantPatternData['DetectPriority'],
            $merchantPatternData['Transactions'] ?? 0,
        );
    }

    public function addStartsWithNeedle(string $needle, $key, array $merchantPatternData): void
    {
        $pattern = \preg_quote($needle, '#');
        $this->regexes[$key] = new Pattern(
            '^' . $pattern,
            '^' . $pattern,
            $key,
            $merchantPatternData['DetectPriority'],
            $merchantPatternData['Transactions'] ?? 0,
        );
    }

    public function addPattern(string $pattern, string $shrunkPattern, string $patternDelimiter, string $key, array $merchantPatternData = []): void
    {
        if ($pattern[0] === $patternDelimiter) {
            $pattern = \substr($pattern, 1, \strlen($pattern) - 2);
            $hasAlternateSymbol = \strpos($pattern, '|');
            $this->regexes[$key] = new Pattern(
                $hasAlternateSymbol ?
                    "(?:{$pattern})" :
                    $pattern,
                $shrunkPattern,
                $key,
                $merchantPatternData['DetectPriority'] ?? 0,
                $merchantPatternData['Transactions'] ?? 0,
            );
        }
    }

    /**
     * @return list<string> patterns list
     */
    public function buildMegaPatterns(int $maxLength = 10000, int $maxNestedLevel = 50, bool $prettyPrint = false): array
    {
        return it($this->doBuildMegaPatterns($maxLength, $maxNestedLevel, $prettyPrint))->toArray();
    }

    private static function comparePatternsBySpecificity(Pattern $aPattern, Pattern $bPattern, int $resolutionLen): int
    {
        $a = $aPattern->shrunkPattern;
        $b = $bPattern->shrunkPattern;
        $aLen = \strlen($a);
        $bLen = \strlen($b);
        $aLenRes = (int) ($aLen / $resolutionLen);
        $bLenRes = (int) ($bLen / $resolutionLen);

        // $cmp = \mb_substr($b, 0, 2) <=> \mb_substr($a, 0, 2);

        // if ($cmp) {
        //     return $cmp;
        // }

        if ($aLenRes === $bLenRes) {
            $minLen = \min($aLen, $bLen);

            for ($i = 0; $i < $minLen; ++$i) {
                $charCmp = $a[$i] <=> $b[$i];

                if ($charCmp) {
                    return $charCmp;
                }
            }
        }

        return $aLen <=> $bLen;
    }

    private function doBuildMegaPatterns(int $maxRegexLength, int $maxNestedLevel, bool $prettyPrint): iterable
    {
        if (!$this->regexes) {
            return;
        }

        \usort(
            $this->regexes,
            static fn (Pattern $a, Pattern $b) =>
                ($a->priority <=> $b->priority) ?: // priority ASC
//                            ((int) ($b->popularity / 1000) <=> (int) ($a->popularity / 1000)) ?: // popularity DESC
//                 (($b->pattern[0] === '^') <=> ($a->pattern[0] === '^')) ?: // patterns with ^ go first
//                 ((\mb_substr($a->pattern, 0, 2) === '.*') <=> (\mb_substr($b->pattern, 0, 2) === '.*')) ?: // patterns with .* go last
                self::comparePatternsBySpecificity($b, $a, 4) // more specific patterns with same prefix go first
        );

        yield from it($this->regexes)
            ->reductions(
                static fn (array $acc, Pattern $pattern) => [
                    $pattern,
                    $acc[1] // total length
                    + \strlen($pattern->pattern)
                    + \strlen($pattern->key)
                    + 7, // for (*:key) and possible grouping symbols
                ],
                [null, 0]
            )
            // split by max regex length
            ->groupAdjacentBy(fn (array $a, array $b): int => (int) ($a[1] / $maxRegexLength) <=> (int) ($b[1] / $maxRegexLength))
            ->map(function (array $patternGroupByLength) use ($maxNestedLevel, $prettyPrint) {
                $patternGroupByLength =
                    it($patternGroupByLength)
                    ->map(fn ($array) => $array[0])
                    ->toArray();
                $regexParts = [self::START_PART];
                $regexParts[] = '(?';
                $regexParts = \array_merge(
                    $regexParts,
                    $this->generateRegexParts(
                        $patternGroupByLength,
                        1,
                        $maxNestedLevel,
                        '',
                        $prettyPrint
                    )
                );
                $regexParts[] = ($prettyPrint ? "\n" : '') . ')';
                $regexParts[] = self::END_PART;

                return \implode('', $regexParts);
            });
    }

    private function generateRegexParts(array $patternGroup, int $nestLevel, int $maxNestedLevel, string $prefix, bool $prettyPrint): array
    {
        $patternPrefixGroups =
            it($patternGroup)
            ->map(fn (Pattern $pattern) => [
                $pattern,
                $nestLevel <= $maxNestedLevel ?
                    self::matchPrefixIdx($pattern->pattern, $this->COMMON_START_PATTERN) :
                    -1,
            ])
            ->groupAdjacentBy(static fn (array $a, array $b) => $a[1] <=> $b[1])
            ->toArray();

        $regexParts = [];
        $addBraces = false;
        $isSingleGroup = \count($patternPrefixGroups) === 1;

        if (!$isSingleGroup && $nestLevel !== 1) {
            $addBraces = true;
            $regexParts[] = "{$prefix}(?";
        }

        foreach ($patternPrefixGroups as $patternPrefixGroup) {
            if (\count($patternPrefixGroup) === 1) {
                /** @var Pattern $pattern */
                $pattern = $patternPrefixGroup[0][0];
                $regexParts[] = self::ident($prettyPrint, $nestLevel) . "|{$pattern->pattern}(*:{$pattern->key})";
            } else {
                $prefixIdx = $patternPrefixGroup[0][1];
                $prefixFound = $prefixIdx !== -1;

                if ($prefixFound) {
                    if ($isSingleGroup && $nestLevel !== 1) {
                        $prefixNest = $prefix . $this->COMMON_START_PATTERNS[$prefixIdx];
                        $newNestingLevel = $nestLevel;
                    } else {
                        $prefixNest = self::ident($prettyPrint, $nestLevel) . '|' . $this->COMMON_START_PATTERNS[$prefixIdx];
                        $newNestingLevel = $nestLevel + 1;
                    }

                    $regexParts = \array_merge(
                        $regexParts,
                        $this->generateRegexParts(
                            it($patternPrefixGroup)
                                ->map(function (array $patternData) {
                                    /** @var Pattern $pattern */
                                    [$pattern, $prefixIdx] = $patternData;

                                    if ($prefixIdx !== -1) {
                                        $pattern = clone $pattern;
                                        $pattern->pattern = \substr(
                                            $pattern->pattern,
                                            \strlen($this->COMMON_START_PATTERNS[$prefixIdx])
                                        );
                                    }

                                    return $pattern;
                                })
                                ->toArray(),
                            $newNestingLevel,
                            $maxNestedLevel,
                            $prefixNest,
                            $prettyPrint
                        )
                    );
                } else {
                    if (StringUtils::isNotEmpty($prefix) && !$addBraces) {
                        $regexParts[] = self::ident($prettyPrint, $nestLevel) . "{$prefix}(?";
                    }

                    /** @var Pattern $pattern */
                    foreach ($patternPrefixGroup as [$pattern]) {
                        $regexParts[] = self::ident($prettyPrint, $nestLevel) . "|{$pattern->pattern}(*:{$pattern->key})";
                    }

                    if (StringUtils::isNotEmpty($prefix) && !$addBraces) {
                        $regexParts[] = self::ident($prettyPrint, $nestLevel - 1) . ")";
                    }
                }
            }
        }

        if ($addBraces) {
            $regexParts[] = self::ident($prettyPrint, $nestLevel - 1) . ')';
        }

        return $regexParts;
    }

    private static function matchPrefixIdx(string $pattern, string $commonPattern): int
    {
        if (
            \preg_match($commonPattern, $pattern, $matches)
            && isset($matches['MARK'])
        ) {
            return (int) $matches['MARK'];
        }

        return -1;
    }

    private static function ident(bool $prettyPrint, int $nestLevel): string
    {
        return $prettyPrint ? ("\n" . \str_repeat("    ", \max($nestLevel, 0))) : '';
    }
}
