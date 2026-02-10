<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights;

class Code
{
    public string $code;
    public string $time;
    public ?int $plusDays;

    public function __construct(
        string $code,
        string $time,
        ?int $plusDays = null
    ) {
        $this->code = $code;
        $this->time = $time;
        $this->plusDays = $plusDays;
    }
}
