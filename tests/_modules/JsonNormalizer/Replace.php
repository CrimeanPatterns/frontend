<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use Codeception\Module\JsonNormalizer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Replace implements PlaceholderProcessorInterface
{
    use PlaceholderProcessorHelperTrait;

    private string $expectedValue;
    /**
     * @var array<string, string>
     */
    private array $replacementsMap;

    /**
     * @param array<string, string> $replacementsMap
     */
    public function __construct(string $expectedValue, array $replacementsMap)
    {
        $this->expectedValue = $expectedValue;
        $this->replacementsMap = $replacementsMap;
    }

    public function process($value, string $propertyPath, Context $context)
    {
        $originalValue = $value;
        $replacements = it($this->replacementsMap)
            ->mapKeys(fn (string $contextKey) => $context[JsonNormalizer::SHARED][$contextKey])
            ->toArrayWithKeys();
        $value = \str_replace(
            \array_keys($replacements),
            \array_values($replacements),
            $value
        );

        try {
            Assert::assertSame($this->expectedValue, $value);

            return $this->makeStub();
        } catch (ExpectationFailedException $e) {
            return $this->makeError(
                $e->getMessage(),
                $propertyPath,
                [
                    'replaced_value' => $value,
                    'original_value' => $originalValue,
                ]
            );
        }
    }

    public static function getCode(): string
    {
        return 'replace';
    }
}
