<?php

namespace AwardWallet\MainBundle\Flight;

/**
 * @see https://extranets.us.amadeus.com
 */
class Client
{
    /**
     * The main Amadeus WS namespace.
     *
     * @var string
     */
    public const AMD_HEAD_NAMESPACE = 'http://xml.amadeus.com/ws/2009/01/WBS_Session-2.0.xsd';

    /**
     * Response data.
     */
    private $_data;

    /**
     * Response headers.
     */
    private $_headers;

    /**
     * Hold the client object.
     */
    private $_client;

    /**
     * Indicates debug mode on/off.
     */
    private $_debug = false;

    /**
     * @param $wsdl  string   Path to the WSDL file
     * @param $debug boolean  Enable/disable debug mode
     */
    public function __construct($wsdl, $debug = false)
    {
        $this->_debug = $debug;
        $this->_client = new \SoapClient($wsdl, [
            'trace' => $debug,
            'soap_version' => SOAP_1_1,
        ]);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Security_Authenticate
     * Autheticates with Amadeus.
     *
     * @param string  $source   sourceOffice string
     * @param string  $origin   originator string
     * @param string  $password password binaryData
     * @param int $passlen  length of binaryData
     * @param string  $org      organizationId string
     */
    public function securityAuthenticate($source, $origin, $password, $passlen, $org)
    {
        $params = [];
        $params['Security_Authenticate']['userIdentifier']['originIdentification']['sourceOffice'] = $source;
        $params['Security_Authenticate']['userIdentifier']['originatorTypeCode'] = 'U';
        $params['Security_Authenticate']['userIdentifier']['originator'] = $origin;
        $params['Security_Authenticate']['dutyCode']['dutyCodeDetails']['referenceQualifier'] = 'DUT';
        $params['Security_Authenticate']['dutyCode']['dutyCodeDetails']['referenceIdentifier'] = 'SU';
        $params['Security_Authenticate']['systemDetails']['organizationDetails']['organizationId'] = $org;
        $params['Security_Authenticate']['passwordInfo']['dataLength'] = $passlen;
        $params['Security_Authenticate']['passwordInfo']['dataType'] = 'E';
        $params['Security_Authenticate']['passwordInfo']['binaryData'] = $password;

        return $this->soapCall('Security_Authenticate', $params, null,
            new \SoapHeader(Client::AMD_HEAD_NAMESPACE, 'SessionId', null), $this->_headers);
    }

    public function soapCall($source, $params, $something = null, $soapheader = false, &$headers = false)
    {
        if (!$headers || $headers == $this->_headers) {
            $headers = $this->_headers;
        }

        if (!isset($this->_headers['Session'])) {
            $this->_headers['Session'] = (object) ['SequenceNumber' => 0];
        }

        ++$this->_headers['Session']->SequenceNumber;

        if (!$soapheader) {
            $soapheader = new \SoapHeader(Client::AMD_HEAD_NAMESPACE, 'Session', $this->_headers['Session']);
        }

        $this->_data = $this->_client->__soapCall($source, $params, $something, $soapheader, $headers);

        $this->debugDump($params, $this->_data);

        return $this->_data;
    }

    /**
     * Security_SignOut
     * Signs out from Amadeus.
     */
    public function securitySignout()
    {
        $sessionId = $this->_headers['Session']->SessionId;

        $params = [];
        $params['Security_SignOut']['SessionId'] = $sessionId;

        return $this->soapCall('Security_SignOut', $params);
    }

    /**
     * Command_Cryptic.
     *
     * @param string $string The string to be sent
     */
    public function commandCryptic($string)
    {
        $params = [];
        $params['Command_Cryptic']['longTextString']['textStringDetails'] = $string;
        $params['Command_Cryptic']['messageAction']['messageFunctionDetails']['messageFunction'] = 'M';

        return $this->soapCall('Command_Cryptic', $params, null,
            new \SoapHeader(Client::AMD_HEAD_NAMESPACE, 'Session', $this->_headers['Session']), $this->_headers);
    }

    /**
     * Air_MultiAvailability
     * Check airline availability by Flight.
     *
     * @param string $deprt_date Departure date
     * @param string $deprt_loc  Departure location
     * @param string $arrive_loc Arrival location
     * @param string $service    Class of service
     * @param string $air_code   Airline code
     * @param string $air_num    Airline number
     */
    public function airFlightAvailability($deprt_date, $deprt_loc, $arrive_loc, $service, $air_code, $air_num)
    {
        $params = [];
        $params['Air_MultiAvailability']['messageActionDetails']['functionDetails']['actionCode'] = 44;
        $params['Air_MultiAvailability']['requestSection']['availabilityProductInfo']['availabilityDetails']['departureDate'] = $deprt_date;
        $params['Air_MultiAvailability']['requestSection']['availabilityProductInfo']['departureLocationInfo']['cityAirport'] = $deprt_loc;
        $params['Air_MultiAvailability']['requestSection']['availabilityProductInfo']['arrivalLocationInfo']['cityAirport'] = $arrive_loc;
        $params['Air_MultiAvailability']['requestSection']['optionClass']['productClassDetails']['serviceClass'] = $service;
        $params['Air_MultiAvailability']['requestSection']['airlineOrFlightOption']['flightIdentification']['airlineCode'] = $air_code;
        $params['Air_MultiAvailability']['requestSection']['airlineOrFlightOption']['flightIdentification']['number'] = $air_num;
        $params['Air_MultiAvailability']['requestSection']['availabilityOptions']['productTypeDetails']['typeOfRequest'] = 'TN';

        return $this->soapCall('Air_MultiAvailability', $params);
    }

    /**
     * Air_MultiAvailability
     * Check airline availability by Service.
     *
     * @param string $deprt_date Departure date
     * @param string $deprt_loc  Departure location
     * @param string $arrive_loc Arrival location
     * @param string $service    Class of service
     */
    public function airServiceAvailability($deprt_date, $deprt_loc, $arrive_loc, $service)
    {
        $params = [];
        $params['Air_MultiAvailability']['messageActionDetails']['functionDetails']['actionCode'] = 44;
        $params['Air_MultiAvailability']['requestSection']['availabilityProductInfo']['availabilityDetails']['departureDate'] = $deprt_date;
        $params['Air_MultiAvailability']['requestSection']['availabilityProductInfo']['departureLocationInfo']['cityAirport'] = $deprt_loc;
        $params['Air_MultiAvailability']['requestSection']['availabilityProductInfo']['arrivalLocationInfo']['cityAirport'] = $arrive_loc;
        $params['Air_MultiAvailability']['requestSection']['availabilityOptions']['productTypeDetails']['typeOfRequest'] = 'TN';
        $params['Air_MultiAvailability']['requestSection']['cabinOption']['cabinDesignation']['cabinClassOfServiceList'] = $service;

        return $this->soapCall('Air_MultiAvailability', $params);
    }

    /**
     * Fare_MasterPricerTravelBoardSearch
     * Search for lowest fare.
     *
     * @param string $deprt_date  Departure date
     * @param string $deprt_loc   Departure location
     * @param string $arrive_loc  Arrival location
     * @param array  $travellers  Travellers array
     * @param string $return_date Return date
     */
    public function fareMasterPricerTravelBoardSearch($deprt_date, $deprt_loc, $arrive_loc, $travellers, $return_date = null)
    {
        $source = 'Fare_MasterPricerTravelBoardSearch';

        $params = [];
        $j = 0;
        $params[$source]['numberOfUnit']['unitNumberDetail'][$j]['numberOfUnits'] = $travellers['A'] + $travellers['C'];
        $params[$source]['numberOfUnit']['unitNumberDetail'][$j]['typeOfUnit'] = 'PX';
        $params[$source]['numberOfUnit']['unitNumberDetail'][$j + 1]['numberOfUnits'] = 200;
        $params[$source]['numberOfUnit']['unitNumberDetail'][$j + 1]['typeOfUnit'] = 'RC';
        $params[$source]['paxReference'][$j]['ptc'] = 'ADT';

        for ($i = 1; $i <= $travellers['A']; $i++) {
            $params[$source]['paxReference'][$j]['traveller'][]['ref'] = $i;
        }

        if ($travellers['C'] > 0) {
            $j++;
            $params[$source]['paxReference'][$j]['ptc'] = 'CNN';

            for (; $i <= $travellers['C'] + $travellers['A']; $i++) {
                $params[$source]['paxReference'][$j]['traveller'][]['ref'] = $i;
            }
        }

        if ($travellers['I'] > 0) {
            $j++;
            $k = 0;
            $params[$source]['paxReference'][$j]['ptc'] = 'INF';

            for (; $i <= $travellers['I'] + $travellers['C'] + $travellers['A']; $i++) {
                $params[$source]['paxReference'][$j]['traveller'][$k]['ref'] = $i;
                $params[$source]['paxReference'][$j]['traveller'][$k]['infantIndicator'] = 1;
                $k++;
            }
        }

        $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'] = 'ADI';
        $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'] = 'TAC';
        $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'] = 'RU';
        $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'] = 'RP';

        $params[$source]['itinerary'][0]['requestedSegmentRef']['segRef'] = 1;
        $params[$source]['itinerary'][0]['departureLocalization']['depMultiCity']['locationId'] = $deprt_loc;
        $params[$source]['itinerary'][0]['arrivalLocalization']['arrivalMultiCity']['locationId'] = $arrive_loc;
        $params[$source]['itinerary'][0]['timeDetails']['firstDateTimeDetail']['date'] = $deprt_date;

        if ($return_date) {
            $params[$source]['itinerary'][1]['requestedSegmentRef']['segRef'] = 2;
            $params[$source]['itinerary'][1]['departureLocalization']['depMultiCity']['locationId'] = $arrive_loc;
            $params[$source]['itinerary'][1]['arrivalLocalization']['arrivalMultiCity']['locationId'] = $deprt_loc;
            $params[$source]['itinerary'][1]['timeDetails']['firstDateTimeDetail']['date'] = $return_date;
        }

        return $this->soapCall($source, $params);
    }

    /**
     * Fare_MetaPricerTravelBoardSearch
     * Search for lowest fare.
     *
     * @param string $deprt_date  Departure date
     * @param string $deprt_loc   Departure location
     * @param string $arrive_loc  Arrival location
     * @param array  $travellers  Travellers array
     * @param string $return_date Return date
     */
    public function fareMetaPricerTravelBoardSearch($deprt_date, $deprt_loc, $arrive_loc, $travellers, $return_date = null)
    {
        $source = 'Fare_MetaPricerTravelBoardSearch';

        $params = [];
        $j = 0;

        $params[$source]['numberOfUnit']['unitNumberDetail'][$j]['numberOfUnits'] = $travellers['A'] + $travellers['C'];
        $params[$source]['numberOfUnit']['unitNumberDetail'][$j]['typeOfUnit'] = 'PX';
        $params[$source]['numberOfUnit']['unitNumberDetail'][$j + 1]['numberOfUnits'] = 200;
        $params[$source]['numberOfUnit']['unitNumberDetail'][$j + 1]['typeOfUnit'] = 'RC';
        $params[$source]['paxReference'][$j]['ptc'] = 'ADT';

        for ($i = 1; $i <= $travellers['A']; $i++) {
            $params[$source]['paxReference'][$j]['traveller'][]['ref'] = $i;
        }

        if ($travellers['C'] > 0) {
            $j++;
            $params[$source]['paxReference'][$j]['ptc'] = 'CNN';

            for (; $i <= $travellers['C'] + $travellers['A']; $i++) {
                $params[$source]['paxReference'][$j]['traveller'][]['ref'] = $i;
            }
        }

        if ($travellers['I'] > 0) {
            $j++;
            $k = 0;
            $params[$source]['paxReference'][$j]['ptc'] = 'INF';

            for (; $i <= $travellers['I'] + $travellers['C'] + $travellers['A']; $i++) {
                $params[$source]['paxReference'][$j]['traveller'][$k]['ref'] = $i;
                $params[$source]['paxReference'][$j]['traveller'][$k]['infantIndicator'] = 1;
                $k++;
            }
        }

        // $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'] = 'ADI';
        // $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'] = 'TAC';
        $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'][] = 'CUC';
        $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'][] = 'RP';
        $params[$source]['fareOptions']['pricingTickInfo']['pricingTicketing']['priceType'][] = 'RU';

        $params[$source]['fareOptions']['conversionRate']['conversionRateDetail']['currency'] = 'USD';

        // Predefined carriers currently
        $params[$source]['travelFlightInfo']['companyIdentity'][0]['carrierQualifier'] = 'F';
        $params[$source]['travelFlightInfo']['companyIdentity'][0]['carrierId'][] = 'AC';
        $params[$source]['travelFlightInfo']['companyIdentity'][0]['carrierId'][] = 'AA';
        $params[$source]['travelFlightInfo']['companyIdentity'][0]['carrierId'][] = 'DL';
        $params[$source]['travelFlightInfo']['companyIdentity'][0]['carrierId'][] = 'OA';
        $params[$source]['travelFlightInfo']['companyIdentity'][0]['carrierId'][] = 'US';
        $params[$source]['travelFlightInfo']['companyIdentity'][0]['carrierId'][] = 'BA';
        $params[$source]['travelFlightInfo']['companyIdentity'][0]['carrierId'][] = 'KL';

        $params[$source]['itinerary'][0]['requestedSegmentRef']['segRef'] = 1;
        $params[$source]['itinerary'][0]['departureLocalization']['departurePoint']['locationId'] = $deprt_loc;
        $params[$source]['itinerary'][0]['arrivalLocalization']['arrivalPointDetails']['locationId'] = $arrive_loc;
        $params[$source]['itinerary'][0]['timeDetails']['firstDateTimeDetail']['date'] = $deprt_date;

        if ($return_date) {
            $params[$source]['itinerary'][1]['requestedSegmentRef']['segRef'] = 2;
            $params[$source]['itinerary'][1]['departureLocalization']['departurePoint']['locationId'] = $deprt_loc;
            $params[$source]['itinerary'][1]['arrivalLocalization']['arrivalPointDetails']['locationId'] = $arrive_loc;
            $params[$source]['itinerary'][1]['timeDetails']['firstDateTimeDetail']['date'] = $return_date;
        }

        return $this->soapCall($source, $params);
    }

    /**
     * pnrAddMultiElements
     * Make reservation call.
     *
     * @param array $travellers Travellers array
     */
    public function pnrAddMultiElements($travellers)
    {
        $adults = count($travellers['A']);
        $children = count($travellers['C']);
        $infants = count($travellers['I']);
        $total_passengers = $adults + $children + $infants;
        $params = [];
        $params['PNR_AddMultiElements']['pnrActions']['optionCode'] = 0;

        $i = 0;
        $inf = 0;

        foreach ($travellers['A'] as $adult) {
            $trav = 0;
            $params['PNR_AddMultiElements']['travellerInfo'][$i]['elementManagementPassenger']['reference']['qualifier'] = 'PR';
            $params['PNR_AddMultiElements']['travellerInfo'][$i]['elementManagementPassenger']['reference']['number'] = $i + 1;
            $params['PNR_AddMultiElements']['travellerInfo'][$i]['elementManagementPassenger']['segmentName'] = 'NM';

            $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['traveller']['surname'] = $adult['surname'];
            $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['traveller']['quantity'] = 1;
            $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['passenger'][$trav]['firstName'] = $adult['first_name'];
            $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['passenger'][$trav]['type'] = 'ADT';

            if ($infants > 0) {
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['passenger'][$trav]['infantIndicator'] = 2;
                $trav++;
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['passenger'][$trav]['firstName'] = $travellers['I'][$inf]['first_name'];
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['passenger'][$trav]['type'] = 'INF';
                $infants--;
                $inf++;
            }
            $i++;
        }

        if ($children > 0) {
            foreach ($travellers['C'] as $child) {
                $trav = 0;
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['elementManagementPassenger']['reference']['qualifier'] = 'PR';
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['elementManagementPassenger']['reference']['number'] = $i + 1;
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['elementManagementPassenger']['segmentName'] = 'NM';

                $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['traveller']['surname'] = $child['surname'];
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['traveller']['quantity'] = 1;
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['passenger'][$trav]['firstName'] = $child['first_name'];
                $params['PNR_AddMultiElements']['travellerInfo'][$i]['passengerData']['travellerInformation']['passenger'][$trav]['type'] = 'CHD';

                $i++;
            }
        }

        $j = 0;
        $params['PNR_AddMultiElements']['dataElementsMaster']['marker1'] = null;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['reference']['qualifier'] = 'OT';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['reference']['number'] = 1;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['segmentName'] = 'RF';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['freetextDetail']['subjectQualifier'] = 3;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['freetextDetail']['type'] = 'P22';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['longFreetext'] = 'Received From Whoever';

        $j++;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['reference']['qualifier'] = 'OT';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['reference']['number'] = 2;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['segmentName'] = 'TK';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['ticketElement']['ticket']['indicator'] = 'OK';

        $j++;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['reference']['qualifier'] = 'OT';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['reference']['number'] = 3;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['segmentName'] = 'ABU';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['freetextDetail']['subjectQualifier'] = 3;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['freetextDetail']['type'] = 2;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['longFreetext'] = 'MR ESTEBAN LORENZO, BUCKINGHAM PALACE, LONDON, N1 1BP, UK';

        $j++;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['reference']['qualifier'] = 'OT';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['reference']['number'] = 4;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['elementManagementData']['segmentName'] = 'AP';
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['freetextDetail']['subjectQualifier'] = 3;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['freetextDetail']['type'] = 5;
        $params['PNR_AddMultiElements']['dataElementsMaster']['dataElementsIndiv'][$j]['freetextData']['longFreetext'] = '012345 678910';

        return $this->soapCall('PNR_AddMultiElements', $params);
    }

    /**
     * Air_SellFromRecommendation
     * Set travel segments.
     *
     * @param string $from     Boarding point
     * @param string $to       Destination
     * @param array  $segments Travel Segments
     */
    public function airSellFromRecommendation($from, $to, $segments)
    {
        $params = [];
        $params['Air_SellFromRecommendation']['messageActionDetails']['messageFunctionDetails']['messageFunction'] = 183;
        $params['Air_SellFromRecommendation']['messageActionDetails']['messageFunctionDetails']['additionalMessageFunction'] = 'M1';
        $params['Air_SellFromRecommendation']['itineraryDetails']['originDestinationDetails']['origin'] = $from;
        $params['Air_SellFromRecommendation']['itineraryDetails']['originDestinationDetails']['destination'] = $to;
        $params['Air_SellFromRecommendation']['itineraryDetails']['message']['messageFunctionDetails']['messageFunction'] = 183;

        $i = 0;

        foreach ($segments as $segment) {
            $params['Air_SellFromRecommendation']['itineraryDetails']['segmentInformation'][$i]['travelProductInformation']['flightDate']['departureDate'] = $segment['dep_date'];
            $params['Air_SellFromRecommendation']['itineraryDetails']['segmentInformation'][$i]['travelProductInformation']['boardPointDetails']['trueLocationId'] = $segment['dep_location'];
            $params['Air_SellFromRecommendation']['itineraryDetails']['segmentInformation'][$i]['travelProductInformation']['offpointDetails']['trueLocationId'] = $segment['dest_location'];
            $params['Air_SellFromRecommendation']['itineraryDetails']['segmentInformation'][$i]['travelProductInformation']['companyDetails']['marketingCompany'] = $segment['company'];
            $params['Air_SellFromRecommendation']['itineraryDetails']['segmentInformation'][$i]['travelProductInformation']['flightIdentification']['flightNumber'] = $segment['flight_no'];
            $params['Air_SellFromRecommendation']['itineraryDetails']['segmentInformation'][$i]['travelProductInformation']['flightIdentification']['bookingClass'] = $segment['class'];
            $params['Air_SellFromRecommendation']['itineraryDetails']['segmentInformation'][$i]['relatedproductInformation']['quantity'] = $segment['passengers'];
            $params['Air_SellFromRecommendation']['itineraryDetails']['segmentInformation'][$i]['relatedproductInformation']['statusCode'] = 'NN';
            $i++;
        }

        return $this->soapCall('Air_SellFromRecommendation', $params);
    }

    /**
     * Fare_PricePNRWithBookingClass.
     *
     * @param string $code Carrier code
     */
    public function farePricePNRWithBookingClass($code = null)
    {
        $params = [];
        $params['Fare_PricePNRWithBookingClass']['overrideInformation']['attributeDetails'][0]['attributeType'] = 'RLO';
        $params['Fare_PricePNRWithBookingClass']['overrideInformation']['attributeDetails'][1]['attributeType'] = 'RP';
        $params['Fare_PricePNRWithBookingClass']['overrideInformation']['attributeDetails'][2]['attributeType'] = 'RU';
        // $params['Fare_PricePNRWithBookingClass']['overrideInformation']['validatingCarrier']['carrierInformation']['carrierCode'] = $code;

        return $this->soapCall('Fare_PricePNRWithBookingClass', $params);
    }

    /**
     * Ticket_CreateTSTFromPricing.
     *
     * @param int $types Number of passenger types
     */
    public function ticketCreateTSTFromPricing($types)
    {
        $params = [];

        for ($i = 0; $i < $types; $i++) {
            $params['Ticket_CreateTSTFromPricing']['psaList'][$i]['itemReference']['referenceType'] = 'TST';
            $params['Ticket_CreateTSTFromPricing']['psaList'][$i]['itemReference']['uniqueReference'] = $i + 1;
        }

        return $this->soapCall('Ticket_CreateTSTFromPricing', $params);
    }

    /**
     * PNR_AddMultiElements
     * Final save operation.
     */
    public function pnrAddMultiElementsFinal()
    {
        $params = [];
        $params['PNR_AddMultiElements']['pnrActions']['optionCode'] = 11;

        return $this->soapCall('PNR_AddMultiElements', $params);
    }

    /**
     * PNR_Retrieve
     * Get PNR by id.
     *
     * @param string $pnr_id PNR ID
     */
    public function pnrRetrieve($pnr_id)
    {
        $params = [];
        $params['PNR_Retrieve']['retrievalFacts']['retrieve']['type'] = 2;
        $params['PNR_Retrieve']['retrievalFacts']['reservationOrProfileIdentifier']['reservation']['controlNumber'] = $pnr_id;

        return $this->soapCall('PNR_Retrieve', $params);
    }

    /**
     * Recusively dump the variable.
     *
     * @param string $varname Name of the variable
     * @param mixed  $varval  Vriable to be dumped
     */
    private function dumpVariable($varname, $varval)
    {
        if (!is_array($varval) && !is_object($varval)) {
            echo $varname . ' = ' . $varval . "<br>\n";
        } else {
            echo $varname . " = data()<br>\n";

            foreach ($varval as $key => $val) {
                $this->dumpVariable($varname . "['" . $key . "']", $val);
            }
        }
    }

    /**
     * Dump the variables in debug mode.
     *
     * @param array $params The parameters used
     * @param array $data   The response data
     */
    private function debugDump($params, $data)
    {
        if ($this->_debug) {
            // Request and Response
            $this->dumpVariable('', $params);
            $this->dumpVariable('data', $data);

            // Trace output
            echo "<br />Request Trace:<br />";
            var_dump($this->_client->__getLastRequest());
            echo "<br />Response Trace:<br />";
            var_dump($this->_client->__getLastResponse());
        }
    }
}
