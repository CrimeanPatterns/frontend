<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use Codeception\Module\JsonNormalizer;

class RegexMatcher implements PlaceholderProcessorInterface
{
    use PlaceholderProcessorHelperTrait;

    protected string $regex;
    protected array $contextPlaceholders = [];

    public function __construct(string $regex, array $contextPlaceholders = [])
    {
        $this->regex = $regex;
        $this->contextPlaceholders = $contextPlaceholders;
    }

    public function process($value, string $propertyPath, Context $context)
    {
        $regex = $this->regex;

        if ($this->contextPlaceholders) {
            foreach ($this->contextPlaceholders as $placeholder) {
                $placeholderValue = $context[JsonNormalizer::SHARED][$placeholder] ?? null;

                if ($placeholderValue !== null) {
                    $regex = \str_replace('{' . $placeholder . '}', $placeholderValue, $regex);
                }
            }
        }

        return \preg_match($regex, $value) ?
            $this->makeStub() :
            $this->makeError(
                "Does not match regex: {$regex}" . ($this->regex !== $regex ? " (original: {$this->regex})" : ""),
                $propertyPath,
                [
                    'value' => $value,
                ]
            );
    }

    public static function getCode(): string
    {
        return 'regex';
    }
}
