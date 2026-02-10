<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class FloatField extends AbstractField
{
    private int $precision = 2;

    public function setPrecision(int $precision): self
    {
        $this->precision = $precision;

        return $this;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public static function create(string $property, ?string $label = null, int $precision = 2, bool $custom = false): self
    {
        $field = new self($property, $label, $custom);
        $field->setPrecision($precision);

        return $field;
    }
}
