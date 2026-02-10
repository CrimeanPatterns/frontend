# Delay Index

The **Delay Index API** offers access to current departure performance at airports.

Base URI https://api.flightstats.com/flex/delayindex/rest/

Version v1
  * WADL    https://api.flightstats.com/flex/delayindex/rest/application.wadl
  * WSDL    https://api.flightstats.com/flex/delayindex/soap/v1/delayIndexService?wsdl
  * XSD     https://api.flightstats.com/flex/delayindex/soap/v1/delayIndexService?xsd=1

|Entry Points   |Name                                               |Description                                        |Response Elements|
|---|---|---|---|
|airports/      |Delay Indexes for specified Airport(s)             |Delay Index information for the given airport(s)   |request, error, delayIndex|
|country/       |Delay Index by Country code                        |Delay Indexes for airports in the given Country    |request, error, delayIndex|
|region/        |Delay Indexes by Region                            |Delay Indexes for airports in the given Region     |request, error, delayIndex|
|state/         |Delay Indexes by State code (US and Canada only)   |Delay Indexes for airports in the given State      |request, error, delayIndex|


---

Source:
  * https://developer.flightstats.com/api-docs
