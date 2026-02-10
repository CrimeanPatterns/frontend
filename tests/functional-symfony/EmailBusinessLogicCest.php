<?php

namespace AwardWallet\Tests\FunctionalSymfony;

/**
 * @group frontend-functional
 */
class EmailBusinessLogicCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const callbackURL = '/api/awardwallet/emailbusiness';
    public const fileEML = __DIR__ . '/../_data/taylor.eml';

    private $login;
    private $businessUserID;
    private $businessEmail;
    private $userEmail;
    private $recLoc;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->login = 'taylorTest' . $I->grabRandomString();
        $this->businessUserID = $I->createBusinessUserWithBookerInfo($this->login, [], []);
        $this->recLoc = strtoupper(bin2hex(openssl_random_pseudo_bytes(3)));
        $this->businessEmail = 'business' . $I->grabRandomString(5) . '@bla.com';
        $this->userEmail = 'user' . $I->grabRandomString(5) . '@bla.com';
    }

    public function sendEmailToMember(\TestSymfonyGuy $I)
    {
        $uaID1 = $I->createFamilyMember($this->businessUserID, "John", "Smith");
        $uaID2 = $I->createFamilyMember($this->businessUserID, "Jeremy", "Roethel");
        $uaID3 = $I->createFamilyMember($this->businessUserID, "Julieta", "Cuadro");
        // makeRequest
        $name = "John Smith";
        //		$request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);

        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('OK');
        $I->canSeeInDatabase('Trip', ['UserAgentID' => $uaID1, 'UserID' => $this->businessUserID]);
        $I->canSeeInDatabase('TripSegment', ['MarketingAirlineConfirmationNumber' => $this->recLoc]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID2, 'UserID' => $this->businessUserID]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID3, 'UserID' => $this->businessUserID]);
    }

    public function sendEmailAdminMemberToNotExistMember(\TestSymfonyGuy $I)
    {
        $uaID1 = $I->createFamilyMember($this->businessUserID, "John", "Smith", null, "keleighton@taylorcorp.com"); // hardCode. look at Util.php TRAVEL_ADMINS
        $uaID2 = $I->createFamilyMember($this->businessUserID, "Jeremy", "Roethel");
        $uaID3 = $I->createFamilyMember($this->businessUserID, "Julieta", "Cuadro");
        // makeRequest
        $name = "Jeremy Smith";
        $this->businessEmail = "keleighton@taylorcorp.com";
        // $request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);

        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('fail');
        $I->dontSeeInDatabase('TripSegment', ['MarketingAirlineConfirmationNumber' => $this->recLoc]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID1, 'UserID' => $this->businessUserID]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID2, 'UserID' => $this->businessUserID]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID3, 'UserID' => $this->businessUserID]);
    }

    public function sendEmailAdminMemberToMember(\TestSymfonyGuy $I)
    {
        $uaID1 = $I->createFamilyMember($this->businessUserID, "John", "Smith", null, "keleighton@taylorcorp.com"); // hardCode. look at Util.php TRAVEL_ADMINS
        $uaID2 = $I->createFamilyMember($this->businessUserID, "Jeremy", "Roethel");
        $uaID3 = $I->createFamilyMember($this->businessUserID, "Julieta", "Cuadro");
        // makeRequest
        $name = "Jeremy Roethel";
        $this->businessEmail = "keleighton@taylorcorp.com";
        //		$request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);

        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('OK');
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID1, 'UserID' => $this->businessUserID]);
        $I->canSeeInDatabase('Trip', ['UserAgentID' => $uaID2, 'UserID' => $this->businessUserID]);
        $I->canSeeInDatabase('TripSegment', ['MarketingAirlineConfirmationNumber' => $this->recLoc]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID3, 'UserID' => $this->businessUserID]);
    }

    public function sendEmailToNotExist(\TestSymfonyGuy $I)
    {
        $uaID1 = $I->createFamilyMember($this->businessUserID, "John", "Smith");
        $uaID2 = $I->createFamilyMember($this->businessUserID, "Jeremy", "Roethel");
        $uaID3 = $I->createFamilyMember($this->businessUserID, "Julieta", "Cuadro");
        // makeRequest
        $name = "Vasia Pupkin";
        //		$request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);
        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('fail');
        $I->dontSeeInDatabase('TripSegment', ['MarketingAirlineConfirmationNumber' => $this->recLoc]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID1, 'UserID' => $this->businessUserID]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID2, 'UserID' => $this->businessUserID]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID3, 'UserID' => $this->businessUserID]);
    }

    public function sendEmailToConnectedMemberEmail(\TestSymfonyGuy $I)
    {
        $uID1 = $I->createAwUser(null, null, ['FirstName' => 'John', 'LastName' => 'Smith', 'MidName' => 'F'], true);
        $I->createConnection($uID1, $this->businessUserID, true, true, []);
        $uaID2 = $I->createFamilyMember($this->businessUserID, "John", "Smith", "F");
        $uaID3 = $I->createFamilyMember($this->businessUserID, "Vasia", "Pupkin", "S", $this->userEmail);
        // makeRequest
        $name = "John F Smith";
        //		$request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);
        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('OK');
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID1]);
        $I->dontSeeInDatabase('Trip', ['UserAgentID' => $uaID2, 'UserID' => $this->businessUserID]);
        $I->seeInDatabase('Trip', ['UserAgentID' => $uaID3, 'UserID' => $this->businessUserID]);
        $I->canSeeInDatabase('TripSegment', ['MarketingAirlineConfirmationNumber' => $this->recLoc]);
    }

    public function sendEmailToConnectedEditTimeLine(\TestSymfonyGuy $I)
    {
        $uID1 = $I->createAwUser(null, null, ['FirstName' => 'John', 'MidName' => 'First', 'LastName' => 'Smith'], true);
        $I->createConnection($uID1, $this->businessUserID, true, true, ['TripAccessLevel' => 1]);
        $I->shareAwTimeline($uID1, null, $this->businessUserID);
        $uID2 = $I->createAwUser(null, null, ['FirstName' => 'John', 'MidName' => 'Second', 'LastName' => 'Smith'], true);
        $I->createConnection($uID2, $this->businessUserID, true, true, ['TripAccessLevel' => 1]);
        $I->shareAwTimeline($uID2, null, $this->businessUserID);
        // makeRequest
        $name = "John First Smith";
        //		$request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);
        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('OK');
        $I->seeInDatabase('Trip', ['UserID' => $uID1, 'UserAgentID' => null]);
        $I->canSeeInDatabase('TripSegment', ['MarketingAirlineConfirmationNumber' => $this->recLoc]);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID2]);
    }

    public function sendEmailToConnectedEditTimeLineByEmail(\TestSymfonyGuy $I)
    {
        $uID1 = $I->createAwUser(null, null, ['FirstName' => 'John', 'LastName' => 'Smith', 'Email' => $this->userEmail], true);
        $I->createConnection($uID1, $this->businessUserID, true, true, ['TripAccessLevel' => 1]);
        $I->shareAwTimeline($uID1, null, $this->businessUserID);
        $uID2 = $I->createAwUser(null, null, ['FirstName' => 'Vasia', 'LastName' => 'Pupkin'], true);
        $I->createConnection($uID2, $this->businessUserID, true, true, ['TripAccessLevel' => 1]);
        $I->shareAwTimeline($uID2, null, $this->businessUserID);
        // makeRequest
        $name = "Joh Smith"; // special wrong name
        //		$request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);
        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('OK');
        $I->seeInDatabase('Trip', ['UserID' => $uID1]);
        $I->canSeeInDatabase('TripSegment', ['MarketingAirlineConfirmationNumber' => $this->recLoc]);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID2]);
    }

    public function _sendEmailToConnectedNotEditTimeLine(\TestSymfonyGuy $I)
    {
        $uID1 = $I->createAwUser(null, null, ['FirstName' => 'John', 'LastName' => 'Smith'], true);
        $I->createConnection($uID1, $this->businessUserID, true, false, []);
        $uID2 = $I->createAwUser(null, null, ['FirstName' => 'Vasia', 'LastName' => 'Pupkin'], true);
        $I->createConnection($uID2, $this->businessUserID, true, false, []);
        // makeRequest
        $name = "John Smith";
        //		$request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);
        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID1]);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID2]);
    }

    public function sendEmailToNotConnected(\TestSymfonyGuy $I)
    {
        $uID1 = $I->createAwUser(null, null, ['FirstName' => 'John', 'LastName' => 'Smith'], true);
        // makeRequest
        $name = "John Smith";
        //		$request = $this->_getFakeRequest($name, $this->login, $I);
        $request = $this->_getFakeRequest($name, $this->businessUserID, $I);
        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(200);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID1]);
        $I->dontSeeInDatabase('TripSegment', ['MarketingAirlineConfirmationNumber' => $this->recLoc]);
    }

    public function sendEmailFromUnknownBusiness(\TestSymfonyGuy $I)
    {
        $uID1 = $I->createAwUser(null, null, ['FirstName' => 'John', 'LastName' => 'Smith'], true);
        $I->createConnection($uID1, $this->businessUserID, true, true, []);
        $uID2 = $I->createAwUser(null, null, ['FirstName' => 'Vasia', 'LastName' => 'Pupkin'], true);
        $I->createConnection($uID2, $this->businessUserID, true, true, []);
        // makeRequest
        $name = "John Smith";
        //		$request = $this->_getFakeRequest($name, 'otherbusiness', $I);
        $request = $this->_getFakeRequest($name, 999999, $I);
        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(403);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID1]);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID2]);
    }

    public function sendEmailWithWrongPwd(\TestSymfonyGuy $I)
    {
        $uID1 = $I->createAwUser(null, null, ['FirstName' => 'John', 'LastName' => 'Smith'], true);
        $I->createConnection($uID1, $this->businessUserID, true, true, []);
        $uID2 = $I->createAwUser(null, null, ['FirstName' => 'Vasia', 'LastName' => 'Pupkin'], true);
        $I->createConnection($uID2, $this->businessUserID, true, true, []);
        // makeRequest
        $name = "John Smith";
        //		$request = $this->_getFakeRequest($name, 'otherbusiness', $I);
        $pwd = $I->grabRandomString();
        $request = $this->_getFakeRequest($name, 999999, $I, $pwd);
        $I->sendPOST(self::callbackURL, $request);

        $I->seeResponseCodeIs(403);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID1]);
        $I->dontSeeInDatabase('Trip', ['UserID' => $uID2]);
    }

    private function _getFakeRequest($traveller, $login, \TestSymfonyGuy $I, $pwd = null)
    {
        $request = [
            'status' => 'success',
            'apiVersion' => 2,
            'providerCode' => 'directravel',
            'fromProvider' => 'true',
            'itineraries' => [
                0 => [
                    'segments' => [
                        0 => [
                            'departure' => [
                                'airportCode' => 'MSP',
                                'name' => 'Minneapolis (Minneapolis Saint Paul International Airport), MN',
                                'localDateTime' => date('Y-m-d H:i:s', strtotime("tomorrow 09:00")),
                                'address' => [
                                    'text' => 'Minneapolis (Minneapolis Saint Paul International Airport), MN',
                                    'addressLine' => 'Minneapolis−Saint Paul International Airport',
                                    'city' => 'Minneapolis',
                                    'stateName' => 'Minnesota',
                                    'countryName' => 'United States',
                                    'postalCode' => '55111',
                                    'lat' => 44.884755400000003,
                                    'lng' => -93.222284599999995,
                                    'airportCode' => 'MSP',
                                    'timezone' => -21600,
                                ],
                            ],
                            'arrival' => [
                                'airportCode' => 'PDX',
                                'name' => 'Portland (Portland International Airport), OR',
                                'localDateTime' => date('Y-m-d H:i:s', strtotime("tomorrow 11:02")),
                                'address' => [
                                    'text' => 'Portland (Portland International Airport), OR',
                                    'addressLine' => '7000 Northeast Airport Way',
                                    'city' => 'Portland',
                                    'stateName' => 'Oregon',
                                    'countryName' => 'United States',
                                    'postalCode' => '97218',
                                    'lat' => 45.589769400000002,
                                    'lng' => -122.59509420000001,
                                    'airportCode' => 'PDX',
                                    'timezone' => -28800,
                                ],
                            ],
                            'seats' => [],
                            'marketingCarrier' => [
                                'airline' => [
                                    'name' => 'Delta Air Lines',
                                    'iata' => 'DL',
                                ],
                                'flightNumber' => '2163',
                                'confirmationNumber' => $this->recLoc,
                            ],
                            'aircraft' => [
                                'name' => 'Douglas MD-90',
                            ],
                            'cabin' => 'ECONOMY',
                            'duration' => '04:02',
                        ],
                        1 => [
                            'departure' => [
                                'airportCode' => 'PDX',
                                'name' => 'Portland (Portland International Airport), OR',
                                'localDateTime' => date('Y-m-d H:i:s', strtotime("+2 days 14:00")),
                                'address' => [
                                    'text' => 'Portland (Portland International Airport), OR',
                                    'addressLine' => '7000 Northeast Airport Way',
                                    'city' => 'Portland',
                                    'stateName' => 'Oregon',
                                    'countryName' => 'United States',
                                    'postalCode' => '97218',
                                    'lat' => 45.589769400000002,
                                    'lng' => -122.59509420000001,
                                    'airportCode' => 'PDX',
                                    'timezone' => -28800,
                                ],
                            ],
                            'arrival' => [
                                'airportCode' => 'MSP',
                                'name' => 'Minneapolis (Minneapolis Saint Paul International Airport), MN',
                                'localDateTime' => date('Y-m-d H:i:s', strtotime("+2 days 19:15")),
                                'address' => [
                                    'text' => 'Minneapolis (Minneapolis Saint Paul International Airport), MN',
                                    'addressLine' => 'Minneapolis−Saint Paul International Airport',
                                    'city' => 'Minneapolis',
                                    'stateName' => 'Minnesota',
                                    'countryName' => 'United States',
                                    'postalCode' => '55111',
                                    'lat' => 44.884755400000003,
                                    'lng' => -93.222284599999995,
                                    'airportCode' => 'MSP',
                                    'timezone' => -21600,
                                ],
                            ],
                            'seats' => [],
                            'marketingCarrier' => [
                                'airline' => [
                                    'name' => 'Delta Air Lines',
                                    'iata' => 'DL',
                                ],
                                'flightNumber' => '2167',
                                'confirmationNumber' => $this->recLoc,
                            ],
                            'aircraft' => [
                                'name' => 'Airbus A320',
                            ],
                            'cabin' => 'ECONOMY',
                            'duration' => '03:15',
                        ],
                    ],
                    'travelers' => [
                        0 => [
                            'name' => $traveller,
                        ],
                    ],
                    'providerInfo' => [
                        'name' => 'Direct Travel',
                        'code' => 'directravel',
                    ],
                    'type' => 'flight',
                ],
                1 => [
                    'hotelName' => 'HAMPTON INN PORTLAND CLACKAMAS',
                    'address' => [
                        'text' => '9040 SE ADAMS, CLACKAMAS OR 97015, US',
                        'addressLine' => '9040 Southeast Adams Street',
                        'city' => 'Clackamas',
                        'stateName' => 'Oregon',
                        'countryName' => 'United States',
                        'postalCode' => '97015',
                        'lat' => 45.408543299999998,
                        'lng' => -122.57031019999999,
                        'airportCode' => null,
                        'timezone' => -28800,
                    ],
                    'checkInDate' => date('Y-m-d H:i:s', strtotime("tomorrow 00:00")),
                    'checkOutDate' => date('Y-m-d H:i:s', strtotime("+2 days 00:00")),
                    'phone' => '1-503-655-7900',
                    'fax' => '1-503-655-1861',
                    'guests' => [
                        0 => [
                            'fullName' => $traveller,
                        ],
                    ],
                    'guestCount' => '1',
                    'kidsCount' => null,
                    'roomsCount' => '1',
                    'cancellationPolicy' => 'CANCEL BEFORE 06PM LOCAL HOTEL TIME ON SCHEDULED DATE OF ARRIVAL TO AVOID PENALTY',
                    'rooms' => [
                        0 => [
                            'type' => '1 QUEEN BED NONSMOKING',
                            'description' => null,
                            'rate' => 'USD132.30',
                        ],
                    ],
                    'confirmationNumbers' => [
                        [
                            'number' => '80410562',
                        ],
                    ],
                    'status' => 'Confirmed',
                    'providerInfo' => [
                        'name' => 'Direct Travel',
                        'code' => 'directravel',
                    ],
                    'type' => 'hotelReservation',
                ],
            ],
            'metadata' => [
                'from' => [
                    'name' => 'Direct2U',
                    'email' => 'direct2uitinerary@dt.com',
                ],
                'to' => [
                    0 => [
                        'name' => null,
                        'email' => $this->businessEmail,
                    ],
                    1 => [
                        'name' => null,
                        'email' => $this->userEmail,
                    ],
                ],
                'cc' => [],
                'subject' => 'Ticketed Direct2U Itinerary for testUser',
                'receivedDateTime' => date('Y-m-d H:i:s', strtotime("-2 days 16:19:43")),
                'userEmail' => $this->businessEmail,
                'nested' => false,
            ],
            'method' => 'auto',
        ];
        $email = file_get_contents(self::fileEML);
        $request['email'] = base64_encode(str_ireplace("TAYLOR_N8N96X@AWARDWALLET.COM", $this->businessEmail, str_ireplace("RDBENTLY@PHOTOCRAFT.COM", $this->userEmail, $email)));
        // $request['email'] = base64_encode($email);

        $I->haveHttpHeader("PHP_AUTH_USER", $login);

        if ($pwd !== null) {
            $pass = $pwd;
        } else {
            $pass = $I->grabService("service_container")->getParameter("email.callback_password");
        }
        $I->haveHttpHeader("PHP_AUTH_PW", $pass);
        $I->haveHttpHeader("Content-type", "application/json");

        return json_encode($request);
    }
}
