<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

class IsScalar extends AbstractTypeAssertion
{
    public static function getCode(): string
    {
        return 'is-scalar';
    }

    protected function is($value): bool
    {
        return \is_scalar($value);
    }
}
