<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class OpeningHoursItemView extends AbstractView
{
    /**
     * @var string[]
     */
    public array $days;

    /**
     * @var RangeView[]|string
     */
    public $openingHours;

    public function __construct(array $days, $openingHours)
    {
        $this->days = $days;
        $this->openingHours = $openingHours;
    }

    public function __toString()
    {
        return sprintf(
            '%s: %s',
            implode(', ', $this->days),
            is_string($this->openingHours)
            ? $this->openingHours
            : implode(', ', array_map(fn ($item) => (string) $item, $this->openingHours))
        );
    }
}
