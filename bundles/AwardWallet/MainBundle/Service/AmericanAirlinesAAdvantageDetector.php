<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 *
 * detector for American Airlines AAdvantage
 * because in Account.ProviderID - NULL, you need to determine by the text in Account.ProgramName
 */
class AmericanAirlinesAAdvantageDetector
{
    public const REGEX = [
        '/American Airlines/i',
        '/AAdvantage/i',
        '/^AA$/i',
        '/AA Advantage/i',
        '/^American$/i',
        '/^Advantage$/i',
    ];

    public const SQL_REGEX = [
        'American Airlines',
        'AAdvantage',
        '^AA$',
        'AA Advantage',
        '^American$',
        '^Advantage$',
    ];

    public static function getSQLFilter(?string $alias = null): string
    {
        $alias = $alias ? $alias . '.' : '';
        $filters = [];

        foreach (self::SQL_REGEX as $regex) {
            $filters[] = "{$alias}ProgramName REGEXP '{$regex}'";
        }

        return sprintf('(%s)', implode(' OR ', $filters));
    }

    public static function isMatchByName(string $name): bool
    {
        if (empty($name)) {
            return false;
        }

        foreach (self::REGEX as $regex) {
            if (preg_match($regex, $name) > 0) {
                return true;
            }
        }

        return false;
    }
}
