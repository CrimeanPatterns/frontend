#!/usr/bin/env bash

set -euxo pipefail

curl -H 'Content-Type: application/json' --user $USER:$PASS https://api-dev.fareportallabs.com/air/api/search/searchflightavailability -d '{"ResponseVersion":"VERSION41","FlightSearchRequest":{"Adults":1,"Child":"0","InfantInLap":"0","InfantOnSeat":"0","Seniors":"0","TypeOfTrip":"ONEWAYTRIP","SegmentDetails":[{"DepartureDate":"2020-04-02","DepartureTime":"0100","Origin":"BOI","Destination":"ANC"}]}}'