<?php

namespace AwardWallet\MainBundle\Security;

/**
 * Symfony version of StringSanitizer.
 *
 * @see https://github.com/symfony/html-sanitizer/blob/ae23fc8d06bdc763251ef479602587a0d3892705/TextSanitizer/StringSanitizer.php
 */
class StringSanitizer
{
    private const LOWERCASE = [
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'abcdefghijklmnopqrstuvwxyz',
    ];

    private const REPLACEMENTS = [
        [
            // "&#34;" is shorter than "&quot;"
            '&quot;',

            // Fix several potential issues in how browsers interpret attributes values
            '+',
            '=',
            '@',
            '`',

            // Some DB engines will transform UTF8 full-width characters their classical version
            // if the data is saved in a non-UTF8 field
            '＜',
            '＞',
            '＋',
            '＝',
            '＠',
            '｀',
        ],
        [
            '&#34;',

            '&#43;',
            '&#61;',
            '&#64;',
            '&#96;',

            '&#xFF1C;',
            '&#xFF1E;',
            '&#xFF0B;',
            '&#xFF1D;',
            '&#xFF20;',
            '&#xFF40;',
        ],
    ];

    /**
     * Applies a transformation to lowercase following W3C HTML Standard.
     *
     * @see https://w3c.github.io/html-reference/terminology.html#case-insensitive
     */
    public static function htmlLower(string $string): string
    {
        return strtr($string, self::LOWERCASE[0], self::LOWERCASE[1]);
    }

    /**
     * Encodes the HTML entities in the given string for safe injection in a document's DOM.
     */
    public static function encodeHtmlEntities(string $string): string
    {
        return str_replace(
            self::REPLACEMENTS[0],
            self::REPLACEMENTS[1],
            htmlspecialchars($string, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
        );
    }
}
