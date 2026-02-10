<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights;

class Dates extends Block
{
    public string $weekDay;

    public function __construct(string $name, string $weekDay, Code $from, Code $to)
    {
        parent::__construct(self::KIND_DATES, null, $name, [
            'from' => $from,
            'to' => $to,
        ]);
        $this->weekDay = $weekDay;
    }
}
