<?php

namespace AwardWallet\MainBundle\Globals;

class StringHandler
{
    public const ALLOWED_TAG_ATTRIBUTES = [
        'br' => [],
        'b' => [],
        'strong' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'sup' => [],
        'sub' => [],
        'p' => [],
    ];

    public static function getRandomString($start, $end, $length)
    {
        $result = '';

        for ($n = 0; $n < $length; $n++) {
            $result .= chr(rand($start, $end));
        }

        return $result;
    }

    public static function strLimit($str, $len, $suff = '...')
    {
        if (mb_strlen($str, 'utf-8') > $len) {
            $str = mb_substr($str, 0, $len, 'utf-8') . $suff;
        }

        return $str;
    }

    public static function getRandomCode($length, $upperCase = false)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';

        if ($upperCase) {
            $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @param int $length bytes length
     * @return string binary string
     */
    public static function getPseudoRandomString($length)
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    public static function nameToText($str)
    {
        return ucwords(trim(strtolower(preg_replace('/([A-Z0-9\/]([a-z])*)/', " $1", $str))));
    }

    /**
     * Whether provider string is empty.
     *
     * @param string $var
     */
    public static function isEmpty($var): bool
    {
        return (null === $var) || ('' === $var);
    }

    public static function getRandomName()
    {
        $fn = ['Isabella', 'Jacob', 'Sophia', 'Emma', 'Michael', 'Ethan', 'William', 'Alexander', 'Noah', 'Daniel', 'Liam', 'John', 'Jackson', 'Samuel', 'Joseph', 'James',
            'Elijah', 'Logan', 'Matthew', 'David', 'Andrew', 'Christopher', 'Mason', 'Joshua', 'Anthony', 'Aiden', 'Lucas', 'Evan', 'Gavin', 'Nicholas', 'Brandon', 'Carter', 'Justin',
            'Julian', 'Robert', 'Aaron', 'Kevin', ];
        $mn = ['Joy', 'Hope', 'Faith', 'Noelle', 'Grace', 'Nicole'];
        $ln = ['Smith', 'Johnson', 'Williams', 'Jones', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'White', 'Harris', 'Martin', 'Thompson',
            'Garcia', 'Martinez', 'Robinson', 'Clark', 'Rodriguez', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen', 'Young', 'Hernandez', 'King', 'Wright', 'Lopez', 'Hill', 'Scott', 'Green',
            'Adams', 'Baker', 'Gonzalez', 'Nelson', 'Carter', 'Perez', 'Roberts', 'Turner', 'Phillips', 'Campbell', 'Parker', 'Stewart', 'Howard', 'Watson', ];

        return [
            'FirstName' => $fn[array_rand($fn)],
            'LastName' => $ln[array_rand($ln)],
            'MiddleName' => $mn[array_rand($mn)],
        ];
    }

    public static function getRandomUserNames($count)
    {
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $n = self::getRandomName();
            $result[] = $n['FirstName'] . " " . $n['LastName'];
        }

        return $result;
    }

    public static function uuid()
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function base64_encode_url(string $text)
    {
        return rtrim(strtr(base64_encode($text), '+/=', '._-'), '-');
    }

    public static function base64_decode_url(string $text)
    {
        return base64_decode(strtr($text, '._-', '+/='));
    }

    public static function splitWithoutBreakWord(string $subject, int $maxlength): array
    {
        if (empty($subject)) {
            return [];
        }

        $words = explode(' ', $subject);
        $result = [0 => ''];
        $currentLength = 0;
        $index = 0;

        foreach ($words as $word) {
            $wordLength = mb_strlen($word) + 1;

            if (($currentLength + $wordLength) <= $maxlength) {
                $result[$index] .= $word . ' ';
                $currentLength += $wordLength;
            } else {
                $currentLength = $wordLength;
                $result[++$index] = $word;
            }
        }

        return array_filter($result, function ($value) {
            return '' !== $value;
        });
    }

    public static function var2TrackingModify(string $link, array $args): string
    {
        $origin = [];
        $parts = parse_url($link);

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $get);

            if (!empty($get['var2'])) {
                $vars = array_filter(explode('.', $get['var2']));

                if (false === strpos($get['var2'], '~') && 1 === count($vars)) {
                    if (false !== strpos($vars[0], 'accountlist')) {
                        $origin['source'] = 'accountlist';
                    } elseif (false !== strpos($vars[0], '101')) {
                        $origin['source'] = '101';
                    } elseif (false !== strpos($vars[0], 'bya')) {
                        $origin['source'] = 'bya';
                    } elseif (false !== strpos($vars[0], 'marketplace')) {
                        $origin['exit'] = 'marketplace';
                    }
                } else {
                    foreach ($vars as $key => $value) {
                        $values = explode('~', $value);

                        if (2 === count($values)) {
                            [$k, $v] = $values;
                            $origin[$k] = $v;
                        }
                    }
                }
            }

            $args = array_merge($origin, $args);
        }

        $pairs = [];

        foreach ($args as $key => $val) {
            $pairs[] = $key . '~' . $val;
        }
        $get['var2'] = implode('.', $pairs);

        return $parts['scheme'] . '://' . $parts['host'] . ($parts['path'] ?? '') . '?' . http_build_query($get, null, '&', PHP_QUERY_RFC3986);
    }

    public static function replaceVarInLink(string $link, array $args = [], bool $removeOld = false): string
    {
        $parts = parse_url($link);

        if (!array_key_exists('scheme', $parts)) {
            getSymfonyContainer()->get('logger')->info('replaceVarInLink undefined scheme', ['link' => $link, 'args' => $args]);

            $parts['scheme'] = 'https';
        }

        if (!array_key_exists('host', $parts)) {
            $parts['host'] = 'awardwallet.com';
            $parts['path'] = '/' . ltrim($parts['path'] ?? '', '/');
        }
        $base = $parts['scheme'] . '://' . $parts['host'] . ($parts['path'] ?? '');

        if ($removeOld) {
            if (empty($args)) {
                return $base;
            }
            $parts['query'] = '';
        }

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $get);
            $args = array_merge($get, $args);
        }

        return $base . '?' . http_build_query($args, null, '&', PHP_QUERY_RFC3986);
    }

    public static function supCopyrightSymbols(string $content): string
    {
        return preg_replace_callback([
            '/<.*?>(*SKIP)(*FAIL)|[^\>]®/',
            '/<.*?>(*SKIP)(*FAIL)|[^\>]&reg;/',
            '/<.*?>(*SKIP)(*FAIL)|[^\>]&#174;/',
            '/<.*?>(*SKIP)(*FAIL)|[^\>]&#x00AE;/',
        ], function ($match) {
            return str_ireplace(['®', '&reg;', '&#174;', '&#x00AE;'], '<sup>®</sup>', $match[0]);
        }, $content);
    }

    public static function cleanHtmlText(
        ?string $text,
        ?string $allowTags = null
    ): ?string {
        if (empty($text)) {
            return $text;
        }

        if (null === $allowTags) {
            $allowTags = '<' . implode('><', array_keys(self::ALLOWED_TAG_ATTRIBUTES)) . '>';
        }

        $text = strip_tags($text, $allowTags);
        $text = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si", '<$1$2>', $text); // remove all attribute

        return trim($text);
    }

    public static function stripTagsAttributes(
        ?string $html,
        array $allowedTagsAttributes = self::ALLOWED_TAG_ATTRIBUTES
    ): ?string {
        if (empty($html)) {
            return $html;
        }

        if (empty($allowedTagsAttributes)) {
            return strip_tags($html);
        }

        $html = strip_tags($html, '<' . implode('><', array_keys($allowedTagsAttributes)) . '>');

        $dom = new \DOMDocument();
        $xmlUtf8 = '<?xml encoding="UTF-8">';
        $dom->loadHTML($xmlUtf8 . $html, LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $allowAttrMaps = array_map(static fn ($tag) => array_flip($tag), $allowedTagsAttributes);

        foreach ($dom->getElementsByTagName('*') as $element) {
            if (!array_key_exists($element->tagName, $allowedTagsAttributes)) {
                throw new \Exception('Incorrect tag in content, tag <' . $element->tagName . '>');
            }

            if ($element->hasAttributes() && 0 === count($allowedTagsAttributes[$element->tagName])) {
                while ($element->attributes->length) {
                    $element->removeAttribute($element->attributes->item(0)->name);
                }

                continue;
            }

            foreach (iterator_to_array($element->attributes) as $attr) {
                if (!array_key_exists($attr->name, $allowAttrMaps[$element->tagName])) {
                    $element->removeAttributeNode($attr);
                }
            }
        }

        $html = str_replace('javascript:', '#', rtrim($dom->saveHTML(), "\n"));
        $html = html_entity_decode($html);

        return str_replace($xmlUtf8, '', $html);
    }

    public static function mb_ucfirst(string $string): string
    {
        return \mb_strtoupper(\mb_substr($string, 0, 1)) . \mb_substr($string, 1);
    }

    public static function getIntArrayFromString(string $list, string $separator = ','): array
    {
        $list = explode($separator, $list);
        $list = array_map('trim', $list);
        $list = array_map('intval', $list);
        $list = array_unique($list);

        return array_filter($list);
    }
}
