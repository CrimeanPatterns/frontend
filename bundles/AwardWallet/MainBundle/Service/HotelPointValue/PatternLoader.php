<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class PatternLoader
{
    public const PATTERNS_SYNTAX_HELP = 'one per line, start with "+" - include, "-" - exclude, "#" - comment, "/" - regex pattern (example, /words?/i), otherwise - include string';

    private const PATTERN_REGEX = '~^/([^/]+)/([ismu]{0,4})$~';

    public static function load(string $patterns): array
    {
        $result = [];
        $include = [];
        $exclude = [];

        foreach (array_map('trim', explode("\n", $patterns)) as $n => $pattern) {
            switch (substr($pattern, 0, 1)) {
                case '#':
                    // comment, skip
                    break;

                case '/':
                    // regex
                    $result[] = static::purgePattern($pattern, $n + 1);

                    break;

                case '-':
                    $exclude[] = trim(substr($pattern, 1));

                    break;

                case '+':
                    $include[] = trim(substr($pattern, 1));

                    break;

                default:
                    if ($pattern != '') {
                        $include[] = $pattern;
                    }

                    break;
            }
        }

        if (count($include) > 0) {
            $result[] = sprintf('/(%s)/uims', implode(')|(', array_map('preg_quote', $include)));
        }

        if (count($exclude) > 0) {
            $result[] = sprintf('/^((?!(%s)).)*$/uims', implode(')|(', array_map('preg_quote', $exclude)));
        }

        return $result;
    }

    /**
     * @return string|null - error or null on success
     */
    public static function validate(string $patterns): ?string
    {
        try {
            self::load($patterns);
        } catch (PatternLoadException $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    public static function matchLoaded(string $candidate, array $loadedPatterns): bool
    {
        $matched = null;

        foreach ($loadedPatterns as $pattern) {
            if (!($matched = (bool) preg_match($pattern, $candidate))) {
                return false;
            }
        }

        return $matched === true;
    }

    /**
     * @throws PatternLoadException
     */
    private static function purgePattern(string $pattern, int $number): string
    {
        if (preg_match(self::PATTERN_REGEX, $pattern, $matches)) {
            return $matches[0];
        }

        throw new PatternLoadException(sprintf('Invalid pattern: "%s" on line %d', $pattern, $number));
    }
}
