<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use Codeception\Module\JsonNormalizer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

class Equal implements PlaceholderProcessorInterface
{
    use PlaceholderProcessorHelperTrait;

    private string $contextKey;

    public function __construct(string $contextKey)
    {
        $this->contextKey = $contextKey;
    }

    public function process($value, string $propertyPath, Context $context)
    {
        try {
            Assert::assertEquals($context[JsonNormalizer::SHARED][$this->contextKey], $value);

            return $this->makeStub();
        } catch (ExpectationFailedException $e) {
            return $this->makeError($e->getMessage(), $propertyPath);
        }
    }

    public static function getCode(): string
    {
        return 'equal';
    }
}
