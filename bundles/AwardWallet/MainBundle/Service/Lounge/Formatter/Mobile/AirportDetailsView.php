<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class AirportDetailsView extends AbstractLoungeDetailsView
{
    public string $icon;

    public string $header;

    public string $airportCode;

    public function __construct(string $header, string $airportCode)
    {
        parent::__construct();
        $this->icon = 'airport';
        $this->header = $header;
        $this->airportCode = $airportCode;
    }
}
