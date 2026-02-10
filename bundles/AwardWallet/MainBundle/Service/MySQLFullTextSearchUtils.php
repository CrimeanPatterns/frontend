<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MySQLFullTextSearchUtils
{
    public static function simpleBooleanModeFilter(string $needle, bool $allWordsMustBePresent = true): string
    {
        return
            self::createTokens(self::filterQueryForFullTextSearch($needle))
            ->map(fn (string $part) => ($allWordsMustBePresent ? "+" : '') . "{$part}*")
            ->joinToString(' ');
    }

    public static function createTokens(string $query): IteratorFluent
    {
        return
            it(\mb_split(' ', $query))
            ->map(fn (string $token) => \trim($token))
            ->filter(fn (string $token) => StringUtils::isNotEmpty($token));
    }

    public static function filterQueryForFullTextSearch(string $query): string
    {
        $search = \preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $query);
        $search = \preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $search);
        $search = \preg_replace('/\s+/', ' ', $search);

        return $search;
    }
}
