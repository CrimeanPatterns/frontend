<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class DateTimeField extends AbstractField
{
    private string $dateFormat = 'medium';

    private string $timeFormat = 'medium';

    public function setDateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setTimeFormat(string $timeFormat): self
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }

    public function getTimeFormat(): string
    {
        return $this->timeFormat;
    }

    public static function create(
        string $property,
        ?string $label = null,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
        bool $custom = false
    ): self {
        $field = new self($property, $label, $custom);

        if (!is_null($dateFormat)) {
            $field->setDateFormat($dateFormat);
        }

        if (!is_null($timeFormat)) {
            $field->setTimeFormat($timeFormat);
        }

        return $field;
    }
}
