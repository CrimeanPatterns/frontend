<?php

namespace AwardWallet\MainBundle\Service\RA;

class RAFlightRouteSearchVolumeList extends \TBaseList
{
    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);
        $this->Query->Fields["DepartureAirport"] = strtoupper($this->Query->Fields["DepartureAirport"]);
        $this->Query->Fields["ArrivalAirport"] = strtoupper($this->Query->Fields["ArrivalAirport"]);
    }
}
