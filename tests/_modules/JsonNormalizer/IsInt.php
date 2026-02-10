<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

class IsInt extends AbstractTypeAssertion
{
    public static function getCode(): string
    {
        return 'is-int';
    }

    protected function is($value): bool
    {
        return \is_int($value);
    }
}
