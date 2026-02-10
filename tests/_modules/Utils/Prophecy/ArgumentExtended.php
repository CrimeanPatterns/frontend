<?php

namespace AwardWallet\Tests\Modules\Utils\Prophecy;

use AwardWallet\Tests\Modules\Utils\Prophecy\Argument\Token\ContainsArrayToken;
use Prophecy\Argument;

class ArgumentExtended extends Argument
{
    public static function containsArray(array $needle): ContainsArrayToken
    {
        return new ContainsArrayToken($needle);
    }
}
