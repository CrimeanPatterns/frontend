<?php

namespace AwardWallet\MainBundle\Flight;

class FlightInfo
{
    /***
     * @var string like 'JFK'
     */
    public $depAirCode;
    /**
     * @var string like 'LAX'
     */
    public $arrAirCode;
    /**
     * @var int unitxtime
     */
    public $depDate;
    /**
     * @var int unitxtime
     */
    public $arrDate;
}
