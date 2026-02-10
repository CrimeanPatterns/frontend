# Connections

Connections returns connecting flights between two airports. It is very useful when flying in or out of smaller, regional airports such as Newark, NJ; Fort Lauderdale, FL; or Spokane, WA.

Base URI https://api.flightstats.com/flex/connections/rest/

Version v2
  * WADL    https://api.flightstats.com/flex/connections/rest/v2/schema/wadl
  * XSD     https://api.flightstats.com/flex/connections/rest/v2/schema/xsd
  * JSON    https://api.flightstats.com/flex/connections/rest/v2/schema/json

|Entry Points       |Name               |Description                                                    |Response Elements|
|---|---|---|---|
|firstflightin/     |First Flight In    |Connections arriving as early as possible before a given time  |Scheduled Flight|
|firstflightout/    |First Flight Out   |Connections leaving as early as possible after a given time    |Scheduled Flight|
|lastflightin/      |Last Flight In     |Connections arriving as late as possible before a given time   |Scheduled Flight|
|firstflightout/    |Last Flight out    |Connections leaving as late as possible after a given time     |Scheduled Flight|


---

Source:
  * https://developer.flightstats.com/api-docs

