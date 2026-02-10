# Ratings

The **Ratings API** provides on-time and delay-based ratings.

Base URI https://api.flightstats.com/flex/ratings/rest/

Version v1
  * WADL    https://api.flightstats.com/flex/ratings/rest/application.wadl
  * WSDL    https://api.flightstats.com/flex/ratings/soap/v1/ratingsService?wsdl
  * XSD     https://api.flightstats.com/flex/ratings/soap/v1/ratingsService?xsd=1

|Entry Points   |Name               |Description                                |Response Elements|
|---|---|---|---|
|flight/        |Ratings for flight |Ratings information for a given flight.    |request, appendix, error, ratings|
|route/         |Ratings for route  |Ratings information for a given route.     |request, appendix, error, ratings|


---

Source:
  * https://developer.flightstats.com/api-docs
