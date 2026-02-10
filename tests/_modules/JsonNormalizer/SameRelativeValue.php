<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

class SameRelativeValue implements PlaceholderProcessorInterface
{
    use PlaceholderProcessorHelperTrait;

    protected string $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function process($value, string $propertyPath, Context $context)
    {
        if (isset($context[self::class])) {
            $previousValueStruct = $context[self::class][$this->key] ?? null;
        } else {
            $context[self::class] = [];
            $previousValueStruct = null;
        }

        if (!$previousValueStruct) {
            $map = $context[self::class];
            $map[$this->key] = [
                $propertyPath,
                $value,
            ];
            $context[self::class] = $map;

            return $this->makeStub();
        } else {
            [$previousPropertyPath, $previousValue] = $previousValueStruct;

            try {
                Assert::assertSame($previousValue, $value);

                return $this->makeStub();
            } catch (ExpectationFailedException $e) {
                return $this->makeError(
                    $e->getMessage(),
                    $propertyPath,
                    ['previous_property_path' => $previousPropertyPath]
                );
            }
        }
    }

    public static function getCode(): string
    {
        return 'same-relative';
    }
}
