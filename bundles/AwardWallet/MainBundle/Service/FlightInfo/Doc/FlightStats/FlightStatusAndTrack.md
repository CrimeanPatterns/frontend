# Flight Status & Track

Flight Status gives you access to current flight information, including scheduled, estimated and actual departure/arrival times, equipment type, delay calculations, terminal, gate and baggage carousel.
Flight Track gives you access to information on an active flight, including position (lat/long), previous positions, altitude, bearing, speed and route.
Flight Position provides recent positional information on flights in a defined area.

Base URI https://api.flightstats.com/flex/flightstatus/rest/

Version    v2
  * WADL                        https://api.flightstats.com/flex/flightstatus/rest/application.wadl
  * Flight Service WSDL         https://api.flightstats.com/flex/flightstatus/soap/v2/flightService?wsdl
  * Flight Service XSD          https://api.flightstats.com/flex/flightstatus/soap/v2/flightService?xsd=1
  * Airport Service WSDL        https://api.flightstats.com/flex/flightstatus/soap/v2/airportService?wsdl
  * Airport Service XSD         https://api.flightstats.com/flex/flightstatus/soap/v2/airportService?xsd=1
  * Route Service WSDL          https://api.flightstats.com/flex/flightstatus/soap/v2/routeService?wsdl
  * Route Service XSD           https://api.flightstats.com/flex/flightstatus/soap/v2/routeService?xsd=1
  * Flights Near Service WSDL   https://api.flightstats.com/flex/flightstatus/soap/v2/flightsNearService?wsdl
  * Flights Near Service XSD    https://api.flightstats.com/flex/flightstatus/soap/v2/flightsNearService?xsd=1

|Entry Points       |Name                       |Description                                                                                                        |Response Elements|
|---|---|---|---|
|flight/status/     |Flight Status by Flight    |Flight Status for a specific flight identified by Carrier, Flight, and Date, or by unique FlightStats identifier.  |request, appendix, error, flightStatus     |
|flight/tracks/     |Flight Track by Flight     |Flight Track for a specific flight identified by Carrier, Flight, and Date, or by unique FlightStats identifier.   |request, appendix, error, flightTrack      |
|airport/status/    |Flight Status by Airport   |Flight Status for flights arriving at or departing from a specific airport.                                        |request, appendix, error, flightStatus     |
|airport/tracks/    |Flight Track by Airport    |Flight Track for flights arriving at or departing from a specific airport.                                         |request, appendix, error, flightTrack      |
|route/status/      |Flight Status by Route     |Flight Status for flights identified by departure airport, arrival airport, and date.                              |request, appendix, error, flightStatus     |
|flightsNear/       |Flights Near (an area)     |Positions for flights currently within an area (identifed by point and radius, or by geographic boundaries).       |request, appendix, error, flightPosition   |


---

Source:
  * https://developer.flightstats.com/api-docs
