<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

class IsString extends AbstractTypeAssertion
{
    public static function getCode(): string
    {
        return 'is-string';
    }

    protected function is($value): bool
    {
        return \is_string($value);
    }
}
