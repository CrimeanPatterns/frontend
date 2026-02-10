<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer\JSONPath;

class FoundValue
{
    private $value;
    private array $key;

    public function __construct($value, array $path)
    {
        $this->value = $value;
        $this->key = $path;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getKey(): array
    {
        return $this->key;
    }
}
