# Schedules

The **Schedules API** provides access to the schedule information for upcoming flights. It is designed to work in conjunction with the FlightStatus APIs to provide continuity between scheduled flights in the future and real-time flight data in the present. It allows convenient search methods by flight, route and airport.

Base URI https://api.flightstats.com/flex/schedules/rest/

Version v1
  * WADL    https://api.flightstats.com/flex/schedules/rest/application.wadl
  * WSDL    https://api.flightstats.com/flex/schedules/soap/v1/scheduledFlightsService?wsdl
  * XSD     https://api.flightstats.com/flex/schedules/soap/v1/scheduledFlightsService?xsd=1

|Entry Points   |Name                                       |Description                                                                            |Response Elements|
|---|---|---|---|
|flight/        |Scheduled flights                          |Scheduled Flight information for direct flights by carrier and flight number.          |request, appendix, error, flights|
|route/         |Scheduled flights by route                 |Flight Schedules information for direct flights between two locations.                 |request, appendix, error, flights|
|airport/       |Scheduled flights by airport               |Flight Schedules information for flights arriving or departing an airport.             |request, appendix, error, flights|

|Entry Points   |Name                                       |Description                                                                            |Response Elements|
|---|---|---|---|
|direct/        |Direct scheduled flights                   |Flight Schedules information for direct flights by airport or flight.                  |request, appendix, error, flights|
|connecting/    |Direct and connecting scheduled flights    |Flight Schedules information for direct and connecting flights between two locations.  |request, appendix, error, flights|


---

Source:
  * https://developer.flightstats.com/api-docs
