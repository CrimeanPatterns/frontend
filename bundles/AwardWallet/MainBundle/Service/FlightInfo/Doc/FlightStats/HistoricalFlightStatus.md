# Historical Flight Status

Historical Flight Status provides flight information for flights completed more than 48 hours ago. Information includes scheduled, estimated and actual departure/arrival times, equipment type, delay calculations, terminal, gate and baggage carousel.

Base URI https://api.flightstats.com/flex/flightstatus/historical/rest/

Version v2
  * WADL    https://api.flightstats.com/flex/flightstatus/historical/rest/v3/schema/wadl
  * XSD     https://api.flightstats.com/flex/flightstatus/historical/rest/v3/schema/xsd
  * JSON    https://api.flightstats.com/flex/flightstatus/historical/rest/v3/schema/json

|Entry Points   |Name                                   |Description                                                                                                                    |Response Elements|
|---|---|---|---|
|flight/        |Historical Flight Status by Flight     |Historical Flight Status for a specific flight identified by Carrier, Flight, and Date, or by unique FlightStats identifier.   |Historical Flight Status|
|airport/       |Historical Flight Status by Airport    |Historical Flight Status for flights that arrived at or departed from a specific airport.                                      |Historical Flight Status|
|route/         |Historical Flight Status by Route      |Historical Flight Status for flights identified by departure airport, arrival airport, and date.                               |Historical Flight Status|


---

Source:
  * https://developer.flightstats.com/api-docs
