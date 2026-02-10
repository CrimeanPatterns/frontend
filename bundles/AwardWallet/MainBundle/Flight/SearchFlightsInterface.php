<?php

namespace AwardWallet\MainBundle\Flight;

interface SearchFlightsInterface
{
    /**
     * @return FlightInfo[]
     */
    public function searchFlights(SearchFlightsRequest $request);
}
