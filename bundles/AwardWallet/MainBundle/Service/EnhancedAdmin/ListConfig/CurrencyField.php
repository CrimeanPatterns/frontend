<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class CurrencyField extends AbstractField
{
    private string $currencyCode = 'USD';

    private bool $round = true;

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setRound(bool $round): self
    {
        $this->round = $round;

        return $this;
    }

    public function getRound(): bool
    {
        return $this->round;
    }

    public static function create(
        string $property,
        ?string $label = null,
        string $currencyCode = 'USD',
        bool $round = true,
        bool $custom = false
    ): self {
        $field = new self($property, $label, $custom);
        $field->setCurrencyCode($currencyCode);
        $field->setRound($round);

        return $field;
    }
}
