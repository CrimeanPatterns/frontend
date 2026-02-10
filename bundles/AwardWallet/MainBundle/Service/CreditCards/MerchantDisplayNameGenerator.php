<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\Common\Parsing\Html;

class MerchantDisplayNameGenerator
{
    public static function create(string $name): string
    {
        $displayName = strtolower(Html::cleanXMLValue($name));
        $displayName = self::cleanText($displayName);
        $displayName = self::titleCase($displayName);

        return trim(preg_replace('/\#\d{2}\d+/', '', $displayName));
    }

    private static function cleanText(string $name): string
    {
        // take the input string, trim whitespace from the ends, single out all repeating whitespace
        $string = str_replace('*', ' ', trim($name));
        $string = preg_replace('/[ ]+/', ' ', trim($string));

        $string = trim(preg_replace('/account number x{1,}\d{3,4}$/i', '', $string));
        $string = trim(preg_replace('/virtual account number \d{4}$/i', '', $string));
        $string = trim(preg_replace('/account number \d{4}$/i', '', $string));

        if (2 === substr_count($string, '"') && preg_match('/^".*"$/i', $string)) {
            $string = trim($string, '"');
        }

        $string = preg_replace('/^#([0-9 ]+)/i', '', $string);

        if (1 === substr_count($string, '#')) {
            $string = ltrim($string, '# ');
        }
        $string = preg_replace('/^\$([0-9,. ]+)/i', '', $string);
        // $string = preg_replace('/\d{5,}/i', '', $string);

        if (0 !== strlen($clean = ltrim($string, "$%+,./ "))) {
            $string = $clean;
        }

        if (0 !== strlen($clean = trim($string, "{}'`-"))) {
            $string = $clean;
        }

        if (0 !== strlen($clean = trim(preg_replace('/[ ]+/', ' ', str_replace('_', ' ', $string))))) {
            $string = $clean;
        }

        return $string;
    }

    // source: https://gist.github.com/JonnyNineToes/7161300
    private static function titleCase($string)
    {
        // reference http://grammar.about.com/od/tz/g/Title-Case.htm
        // The below array contains the most commonly non-capitalized words in title casing - I'm not so sure about the commented ones that follow it...
        $minorWords = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'nor', 'of', 'on', 'or', 'per', 'the', 'to', 'with']; // but, is, if, then, else, when, from, off, out, over, into,

        // explode string into array of words
        $pieces = explode(' ', $string);

        $pieces = self::removeIgnoredWords($pieces);
        $pieces = self::removeTruncatedWords($pieces);

        // for each element in array...
        $pieces = array_values($pieces);

        for ($p = 0; $p <= (count($pieces) - 1); $p++) {
            if (self::isAcronym($pieces[$p])) {
                $pieces[$p] = strtoupper($pieces[$p]);

                continue;
            }

            // check if the whole word is capitalized (as in acronyms), if it is not...
            if (strtoupper($pieces[$p]) != $pieces[$p]) {
                // reduce all characters to lower case
                $pieces[$p] = strtolower($pieces[$p]);

                // if the value of the element doesn't match any of the elements in the minor words array, and the index is not equal to zero, or the numeric key of the last element...
                if (!in_array($pieces[$p], $minorWords) || ($p === 0 || $p === (count($pieces) - 1))) {
                    // ...capitalize it.
                    $pieces[$p] = ucfirst($pieces[$p]);
                }
                // check for hyphenated words?... apparently, even title casing, it's okay for a the second word to be lower case...
            }
        }
        // re-connect all words in array with a space
        $string = implode(' ', $pieces);

        // return title-cased string
        return $string;
    }

    private static function removeIgnoredWords(array $pieces): array
    {
        return array_filter($pieces, fn (string $word) => !in_array($word, ['recurring', 'payment']));
    }

    private static function removeTruncatedWords(array $pieces): array
    {
        if (count($pieces) < 2) {
            return $pieces;
        }

        $last = end($pieces);

        if (preg_match('#^[a-z]$#ims', $last)) {
            array_pop($pieces);
        }

        return $pieces;
    }

    private static function isAcronym(string $word): string
    {
        return strlen($word) <= 4 && strpos($word, '&') !== false;
    }
}
