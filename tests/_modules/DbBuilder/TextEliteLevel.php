<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class TextEliteLevel extends AbstractDbEntity
{
    private ?EliteLevel $eliteLevel;

    public function __construct(
        string $valueText,
        ?EliteLevel $eliteLevel = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'ValueText' => $valueText,
        ]));

        $this->eliteLevel = $eliteLevel;
    }

    public function getEliteLevel(): ?EliteLevel
    {
        return $this->eliteLevel;
    }

    public function setEliteLevel(?EliteLevel $eliteLevel): self
    {
        $this->eliteLevel = $eliteLevel;

        return $this;
    }
}
