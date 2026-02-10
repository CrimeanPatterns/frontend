# Airlines

The **Airlines API** provides basic reference information about one or more airlines.

Base URI https://api.flightstats.com/flex/airlines/rest/

Version v1
  * WADL    https://api.flightstats.com/flex/airlines/rest/v1/schema/wadl
  * WSDL    https://api.flightstats.com/flex/airlines/soap/v1/airlinesService?wsdl
  * XSD     https://api.flightstats.com/flex/airlines/rest/v1/schema/xsd
  * JSON    https://api.flightstats.com/flex/airlines/rest/v1/schema/json

|Entry Points   |Name                           |Description                                                                        |Response Elements|
|---|---|---|---|
|active/        |Active airlines                |Airline information for active airlines                                            |airlines|
|all/           |All airlines                   |Airline information for all airlines, active and inactive                          |airlines|
|fs/            |Airlines by FlightStats code   |Airline information for airlines selected by FlightStats code and optionally date  |airlines|
|iata/          |Airlines by IATA code          |Airline information for airlines selected by IATA code and optionally date         |airlines|
|icao/          |Airlines by ICAO code          |Airline information for airlines selected by ICAO code and optionally date         |airlines|

## Requests

### All airlines (active and inactive)

`GET /v1/json/all`

Returns a listing of all airlines, including those that are not currently active

|Parameter          |Req|Description|
|---|---|---|
|appId              |*  |Application ID |
|appKey             |*  |Application key|
|extendedOptions    |   |Extended options for modifying standard API behavior to fit special use cases. Options: 'useHttpErrors'.|


### Active airlines

`GET /v1/json/active`

Returns a listing of currently active airlines

|Parameter          |Req|Description|
|---|---|---|
|appId              |*  |Application ID |
|appKey             |*  |Application key|
|extendedOptions    |   |Extended options for modifying standard API behavior to fit special use cases. Options: 'useHttpErrors'.|


### Active airlines for date

`GET /v1/json/active/{year}/{month}/{day}`

Returns a listing of active airlines on the given date

|Parameter          |Req|Description|
|---|---|---|
|appId              |*  |Application ID |
|appKey             |*  |Application key|
|year               |*  |Four-digit year|
|month              |*  |Month (1 to 12)|
|day                |*  |Day of month   |
|extendedOptions    |   |Extended options for modifying standard API behavior to fit special use cases. Options: 'useHttpErrors'.|


### Airline by FlightStats code

`GET /v1/json/fs/{code}`

Returns the airline with the given FlightStats code, a globally unique code that is consistent across time

|Parameter          |Req|Description|
|---|---|---|
|appId              |*  |Application ID|
|appKey             |*  |Application key|
|code               |*  |FlightStats code, globally unique across time      |
|extendedOptions    |   |Extended options for modifying standard API behavior to fit special use cases. Options: 'useHttpErrors'.|


### Airlines by IATA code

`GET /v1/json/iata/{iataCode}`

Returns a listing of airlines that have had the given IATA code

|Parameter          |Req|Description|
|---|---|---|
|appId              |*  |Application ID|
|appKey             |*  |Application key|
|iataCode           |*  |IATA code      |
|extendedOptions    |   |Extended options for modifying standard API behavior to fit special use cases. Options: 'useHttpErrors'.|


### Airline by IATA code on date

`GET /v1/json/iata/{iataCode}/{year}/{month}/{day}`

Returns the airline that had the IATA code on the given date

|Parameter          |Req|Description|
|---|---|---|
|appId              |*  |Application ID |
|appKey             |*  |Application key|
|iataCode           |*  |IATA code      |
|year               |*  |Four-digit year|
|month              |*  |Month (1 to 12)|
|day                |*  |Day of month   |
|extendedOptions    |   |Extended options for modifying standard API behavior to fit special use cases. Options: 'useHttpErrors'.|


### Airlines by ICAO code

`GET /v1/json/icao/{icaoCode}`

Returns a listing of airlines that have had the given ICAO code

|Parameter          |Req|Description|
|---|---|---|
|appId              |*  |Application ID|
|appKey             |*  |Application key|
|icaoCode           |*  |ICAO code      |
|extendedOptions    |   |Extended options for modifying standard API behavior to fit special use cases. Options: 'useHttpErrors'.|


###  Airline by ICAO code on date

`GET /v1/json/icao/{icaoCode}/{year}/{month}/{day}`

Returns the airline that had the ICAO code on the given date

|Parameter          |Req|Description|
|---|---|---|
|appId              |*  |Application ID |
|appKey             |*  |Application key|
|icaoCode           |*  |ICAO code      |
|year               |*  |Four-digit year|
|month              |*  |Month (1 to 12)|
|day                |*  |Day of month   |
|extendedOptions    |   |Extended options for modifying standard API behavior to fit special use cases. Options: 'useHttpErrors'.|


## Responses

### Airlines

|Element                                    |Cardinality|Description|
|---|---|---|
|<fs>AA<fs>                                 |**1..1**   |The FlightStats code for the carrier, globally unique across time (String).|
|<iata>AA<iata>                             |0..1       |The IATA code for the carrier (String).                                    |
|<icao>AAL<icao>                            |0..1       |The ICAO code for the carrier (String).                                    |
|<name>American Airlines<name>              |**1..1**   |The name of the carrier (String).                                          |
|<phoneNumber>1-800-433-7300</phoneNumber>  |0..1       |The primary customer service phone number for the carrier (String).        |
|<active>true</active>                      |**1..1**   |Boolean value indicating if the airline is currently active (Boolean).     |
|<category>A</category>                     |0..1       |**NEW** The category of operation of the airline                           |

**Categories**

|Value  |Description                            |Passenger  |Cargo  |
|---|---|---|---|
|A      |Scheduled Passenger Carrier            |Y          |N      |
|B      |Non-Scheduled Passenger Carrier        |Y          |N      |
|C      |Scheduled Cargo Carrier                |N          |Y      |
|D      |Non-scheduled Cargo Carrier            |N          |Y      |
|I      |Scheduled Passenger/Cargo Carrier      |Y          |Y      |
|J      |Non-scheduled Passenger/Cargo Carrier  |Y          |Y      |
|K      |Railway Service                        |Y          |Y      |

Fields marked as **NEW** will only be returned if the extended option "includeNewFields" is used. See the [Flex API Version Policy][#] page for details.

**Example**

```JSON
    airlines: {
        active: true,
        fs: "AA",
        iata: "AA",
        icao: "AAL",
        name: "American Airlines",
        phoneNumber: "1-800-433-7300"
    }
```


---

Source:
  * https://developer.flightstats.com/api-docs
  * https://developer.flightstats.com/api-docs/airlines/v1
  * https://developer.flightstats.com/api-docs/airlines/v1/airlineresponse