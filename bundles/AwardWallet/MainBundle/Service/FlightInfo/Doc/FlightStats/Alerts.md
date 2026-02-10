# Alerts

The **Alerts API** offers access to push-based alerting of flight status information. Alert Messages are produced when an existing alert rule is triggered, and are delivered to the destination specified by the rule.

Base URI https://api.flightstats.com/flex/alerts/rest/

Version v1
  * WADL    https://api.flightstats.com/flex/alerts/rest/application.wadl
  * WSDL    https://api.flightstats.com/flex/alerts/soap/v1/flightAlertsService?wsdl
  * XSD     https://api.flightstats.com/flex/alerts/soap/v1/flightAlertsService?xsd=1

|Entry Points   |Name           |Description                                                            |Response Elements|
|---|---|---|---|
|create/        |Create rule    |Create a flight rule to be monitored for a specific flight.            |request, error, alerts|
|delete/        |Delete rule    |Deletes a flight rule that was previously created given a rule ID.     |request, error, alerts|
|get/           |Retrieve rule  |Returns the flight rule that was previously created given a rule ID.   |request, error, alerts|


---

Source:
  * https://developer.flightstats.com/api-docs
