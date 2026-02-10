<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use Codeception\Module\JsonNormalizer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

class Same implements PlaceholderProcessorInterface
{
    use PlaceholderProcessorHelperTrait;

    private string $contextKey;

    public function __construct(string $contextKey)
    {
        $this->contextKey = $contextKey;
    }

    public function process($value, string $propertyPath, Context $context)
    {
        $expectedValue = $context[JsonNormalizer::SHARED][$this->contextKey];

        try {
            Assert::assertSame($expectedValue, $value);

            return $this->makeStub();
        } catch (ExpectationFailedException $e) {
            return $this->makeError(
                $e->getMessage(),
                $propertyPath,
                [
                    'value' => $value,
                    'expected_value' => $expectedValue,
                ]
            );
        }
    }

    public static function getCode(): string
    {
        return 'same';
    }
}
