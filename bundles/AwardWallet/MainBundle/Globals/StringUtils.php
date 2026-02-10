<?php

namespace AwardWallet\MainBundle\Globals;

class StringUtils extends StringHandler
{
    public static function notEmptyOrNull(?string $string): ?string
    {
        if (self::isEmpty($string)) {
            return null;
        } else {
            return $string;
        }
    }

    public static function isNotEmpty($var): bool
    {
        return !self::isEmpty($var);
    }

    public static function isAllEmpty(...$vars): bool
    {
        foreach ($vars as $var) {
            if (self::isNotEmpty($var)) {
                return false;
            }
        }

        return true;
    }

    public static function isAllNotEmpty(...$vars): bool
    {
        foreach ($vars as $var) {
            if (self::isEmpty($var)) {
                return false;
            }
        }

        return true;
    }
}
