<?php

namespace AwardWallet\MainBundle\Flight;

class SearchFlightsRequest
{
    /**
     * @var string like 'JFK'
     */
    public $depAirCode;
    /**
     * @var string like 'LAX'
     */
    public $arrAirCode;
    /**
     * @var int unixtime
     */
    public $date;
}
