<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ListView;

class TripPointView
{
    public string $kind = 'tripPoint';
    public string $dep;
    public string $arr;
    public string $hint;

    public function __construct(string $dep, string $arr, string $hint)
    {
        $this->dep = $dep;
        $this->arr = $arr;
        $this->hint = $hint;
    }
}
