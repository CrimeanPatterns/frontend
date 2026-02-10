<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

interface PlaceholderProcessorInterface
{
    public function process($value, string $propertyPath, Context $context);

    public static function getCode(): string;
}
