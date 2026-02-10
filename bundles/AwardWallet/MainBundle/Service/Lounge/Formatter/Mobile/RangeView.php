<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class RangeView extends AbstractView
{
    public string $start;

    public string $end;

    public function __construct(string $start, string $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function __toString()
    {
        return sprintf('%s - %s', $this->start, $this->end);
    }
}
