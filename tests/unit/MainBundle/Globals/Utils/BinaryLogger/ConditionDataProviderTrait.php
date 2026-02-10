<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals\Utils\BinaryLogger;

trait ConditionDataProviderTrait
{
    public function conditionDataProvider(): array
    {
        return [
            'condition is true' => [true],
            'condition is false' => [false],
        ];
    }
}
