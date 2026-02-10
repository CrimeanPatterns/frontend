<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class DateField extends AbstractField
{
    private string $format = 'medium';

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public static function create(string $property, ?string $label = null, ?string $format = null, bool $custom = false): self
    {
        $field = new self($property, $label, $custom);

        if (!is_null($format)) {
            $field->setFormat($format);
        }

        return $field;
    }
}
