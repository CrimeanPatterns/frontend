<?php

namespace AwardWallet\MainBundle\Service\Lounge\DTO;

class ValueDTO
{
    private ?string $value;

    private bool $inaccurate;

    public function __construct(?string $value, bool $inaccurate = true)
    {
        $this->value = $value;
        $this->inaccurate = $inaccurate;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function isInaccurate(): bool
    {
        return $this->inaccurate;
    }
}
