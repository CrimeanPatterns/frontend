# Airports

The **Airports API** provides basic reference information about one or more airports.

Base URI https://api.flightstats.com/flex/airports/rest/

Version v1
  * WADL    https://api.flightstats.com/flex/airports/rest/v1/schema/wadl
  * WSDL    https://api.flightstats.com/flex/airports/soap/v1/airportsService?wsdl
  * XSD     https://api.flightstats.com/flex/airports/rest/v1/schema/xsd
  * JSON    https://api.flightstats.com/flex/airports/rest/v1/schema/json

|Entry Points   |Name                               |Description                                                                        |Response Elements|
|---|---|---|---|
|active/        |Active airports                    |Airport information for active airports                                            |airport|
|all/           |All airports                       |Airport information for all airports, active and inactive.                         |airport|
|/              |Airport on date by code            |Airport information for an airport selected by airport code and date.              |airport|
|cityCode/      |Airports by city code              |Airport information for airports selected by city code.                            |airport|
|countryCode/   |Airports by country code           |Airport information for airports that have had the given country code.             |airport|
|fs/            |Airports by FlightStats code       |Airport information for airports selected by FlightStats code and optionally date. |airport|
|iata/          |Airports by IATA code              |Airport information for airports selected by IATA code and optionally date.        |airport|
|icao/          |Airports by ICAO code              |Airport information for airports selected by ICAO code and optionally date.        |airport|
|withinRadius/  |Airports within radius of location |Airport information for airports within radius of a location.                      |airport|


---

Source:
  * https://developer.flightstats.com/api-docs