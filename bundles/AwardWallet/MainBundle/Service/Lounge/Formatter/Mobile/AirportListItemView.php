<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class AirportListItemView extends AbstractBlockView
{
    public string $name;

    public function __construct(string $name)
    {
        parent::__construct('airportListItem');
        $this->name = $name;
    }
}
