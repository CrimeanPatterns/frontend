# Weather

The **Weather API** provides information about current and future weather conditions affecting airports and flights. Weather information is currently available only for locations in the United States.

Base URI https://api.flightstats.com/flex/weather/rest/

Version v1
  * WADL    https://api.flightstats.com/flex/weather/rest/application.wadl
  * WSDL    https://api.flightstats.com/flex/weather/soap/v1/weatherService?wsdl
  * XSD     https://api.flightstats.com/flex/weather/rest/application.wadl?xsd0.xsd

|Entry Points   |Name                               |Description                                                                                                                                                                                                |Response Elements|
|---|---|---|---|
|all/           |All weather products for airport   |Retrieve all weather products (METAR, TAF, and Zone Forecast) for the airport.                                                                                                                             |request, appendix, error, METAR response, TAF response, Zone Forecast response|
|metar/         |METAR for airport                  |Retrieve the most current available METAR weather report for the aerodrome around a given airport. METAR reports describe current conditions and are updated about once an hour.                           |request, appendix, error, METAR response|
|taf/           |TAF for airport                    |Retrieve the most current available Terminal Aerodrome Forecast (TAF) for the airport. TAFs forecast weather conditions for the area within a 5 mile radius from the center of the airport runway complex. |request, appendix, error, TAF response|
|zf/            |Zone Forecast for airport          |Retrieve the most current available zone forecast for the airport. Zone forecasts can cover several days, and apply to a more extensive area around the airport than TAFs.                                 |request, appendix, error, TAF response|


---

Source:
  * https://developer.flightstats.com/api-docs
