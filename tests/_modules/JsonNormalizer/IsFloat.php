<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

class IsFloat extends AbstractTypeAssertion
{
    public static function getCode(): string
    {
        return 'is-float';
    }

    protected function is($value): bool
    {
        return is_float($value);
    }
}
