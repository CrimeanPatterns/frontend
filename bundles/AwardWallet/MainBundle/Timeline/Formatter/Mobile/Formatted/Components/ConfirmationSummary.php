<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components;

class ConfirmationSummary
{
    public ?string $confno;

    public int $segmentCount;

    public function __construct(?string $confno, int $segmentCount)
    {
        $this->confno = $confno;
        $this->segmentCount = $segmentCount;
    }
}
