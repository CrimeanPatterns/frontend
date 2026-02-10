<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator;

class Util
{
    public static function clean($value)
    {
        if (is_string($value) && !empty($value)) {
            $value = htmlspecialchars_decode($value);
            $value = str_replace('&nbsp;', '', $value);
            $value = trim($value);
        }

        return $value;
    }

    public static function getNumber(string $value, bool $strict = false): string
    {
        if (preg_match("/^(\d+)k$/ims", $value, $matches)) {
            $value = $matches[1] . '000';
        }

        if (preg_match("/" .
            ($strict ? "^" : "") . "([\d\.\, ]+)" .
            ($strict ? "$" : "") . "/ims", $value, $matches)) {
            return floatval(trim(str_replace([',', ' '], '', $matches[1])));
        }

        return $value;
    }
}
