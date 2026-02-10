<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\StringHandler;

/**
 * @NoDI()
 */
class Helper
{
    public const EQUAL = 'equal';
    public const NOT_EQUAL = 'notEqual';
    public const EMPTY = 'empty';
    public const DISPARATE = 'disparate';

    public static function equals(?string $string1, ?string $string2, bool $orEmpty = false): bool
    {
        $result = static::compare($string1, $string2);

        return $result === self::EQUAL || ($orEmpty && $result === self::EMPTY);
    }

    public static function compare(?string $string1, ?string $string2): string
    {
        if (StringHandler::isEmpty($string1) && StringHandler::isEmpty($string2)) {
            return self::EMPTY;
        } elseif (!StringHandler::isEmpty($string1) && !StringHandler::isEmpty($string2)) {
            if (strcasecmp($string1, $string2) === 0) {
                return self::EQUAL;
            } else {
                return self::NOT_EQUAL;
            }
        }

        return self::DISPARATE;
    }
}
