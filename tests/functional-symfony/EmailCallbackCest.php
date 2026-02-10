<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Email\EmailOptions;
use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Email;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\EmailCallbackExecutor;
use AwardWallet\MainBundle\Worker\AsyncProcess\EmailCallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use AwardWallet\Schema\Itineraries\Flight;
use Codeception\Example;
use JMS\Serializer\Serializer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @group frontend-functional
 */
class EmailCallbackCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const callbackURL = '/api/awardwallet/email';
    public const fileEML = __DIR__ . '/../_data/taylor.eml';

    private $UserID;
    private $recLoc;
    private ?EmailCallbackTask $task;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->UserID = $I->createAwUser(null, null, [], true);
        $this->recLoc = 'RL' . $I->grabRandomString(6); // 'G5R2XQ'
        $I->haveHttpHeader("PHP_AUTH_USER", "awardwallet");
        $I->haveHttpHeader("PHP_AUTH_PW", $I->grabService("service_container")->getParameter("email.callback_password"));
        $I->haveHttpHeader("Content-type", "application/json");
        $I->mockService(Process::class, $I->stubMakeEmpty(Process::class, [
            'execute' => function (Task $task) {
                if ($task instanceof EmailCallbackTask) {
                    $this->task = $task;
                }

                return new Response(Response::STATUS_READY);
            },
        ]));
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->task = null;
    }

    /**
     * @dataProvider times
     */
    public function testAddRemove(\TestSymfonyGuy $I, Example $example)
    {
        $request = $this->_getFakeRequest($example["baseDate"]);

        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $tripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertNotEmpty($tripId);
        $I->seeInDatabase("TripSegment", ["TripID" => $tripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "MSP", "Hidden" => 0]);
        $I->seeInDatabase("TripSegment", ["TripID" => $tripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "PDX", "Hidden" => 0]);

        $request = $this->_getFakeRequest($example["baseDate"]);
        unset($request['itineraries'][0]['segments'][1]);
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $tripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertNotEmpty($tripId);
        $I->seeInDatabase("TripSegment", ["TripID" => $tripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "MSP", "Hidden" => 0]);
        $I->dontSeeInDatabase("TripSegment", ["TripID" => $tripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "PDX", "Hidden" => 0]);
        $I->seeInDatabase("TripSegment", ["TripID" => $tripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "PDX", "Hidden" => 1]);
    }

    public function testSpentAwards(\TestSymfonyGuy $I)
    {
        $request = $this->_getFakeRequest();
        $request['providerCode'] = 'amex';
        $request['itineraries'][0]['pricingInfo']['spentAwards'] = 100;
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $I->seeInDatabase('Trip', ['UserID' => $this->UserID, 'SpentAwards' => null]);

        $I->executeQuery('DELETE FROM Trip WHERE UserID = ' . $this->UserID);
        $request['providerCode'] = 'directravel';
        $request['itineraries'][0]['pricingInfo']['spentAwards'] = 100;
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $I->seeInDatabase('Trip', ['UserID' => $this->UserID, 'SpentAwards' => 100]);
        $providerId = $I->grabFromDatabase('Trip', 'ProviderID', ['UserID' => $this->UserID, 'SpentAwards' => 100]);
        $I->assertNotEmpty($providerId);

        $request['providerCode'] = 'amex';
        $request['itineraries'][0]['pricingInfo']['spentAwards'] = 200;
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $I->seeInDatabase('Trip', ['UserID' => $this->UserID, 'SpentAwards' => 100, 'ProviderID' => $providerId]);

        $request['providerCode'] = 'directravel';
        $request['itineraries'][0]['pricingInfo']['spentAwards'] = 500;
        $request['itineraries'][0]['travelAgency']['providerInfo']['code'] = 'amex';
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $I->seeInDatabase('Trip', ['UserID' => $this->UserID, 'SpentAwards' => 100, 'ProviderID' => $providerId]);
    }

    public function saveAndCancel(\TestSymfonyGuy $I)
    {
        $user = $I->grabService('doctrine.orm.default_entity_manager')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->UserID);
        /** @var Serializer $jms */
        $jms = $I->grabService('jms_serializer');
        /** @var ItinerariesProcessor $processor */
        $processor = $I->grabService(ItinerariesProcessor::class);
        $source = new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com', 'f8efc9b935f402b70e2e34fd672c86e1');
        $flightJson = '{"status":"Confirmed","providerInfo":{"code":"delta","name":"Delta Air Lines"},"travelers":[{"name":"John Doe","full":true},{"name":"Jane Doe","full":true}],"segments":[{"departure":{"airportCode":"PEE","terminal":"A","name":"Bolshoe savino","localDateTime":"2019-01-01T13:45:00","address":{"text":"PEE"}},"arrival":{"airportCode":"DME","terminal":"2","name":"Domodedovo","localDateTime":"2019-01-01T15:00:00","address":{"text":"DME"}},"marketingCarrier":{"airline":{"name":"Delta Air Lines","iata":"DL","icao":"DAL"},"flightNumber":"0013","confirmationNumber":"AABBCC"}}],"type":"flight"}';
        /** @var Flight $flight */
        $flight = $jms->deserialize($flightJson, \AwardWallet\Schema\Itineraries\Flight::class, 'json');
        $options = SavingOptions::savingByEmail(OwnerRepository::getOwner($user, null), $messageId = bin2hex(random_bytes(3)), $source);
        $report = $processor->save([$flight], $options);
        $I->seeInDatabase('Trip', ['UserID' => $this->UserID]);
        $this->assertEmailSource($I, $report->getAdded()[0]->getSegments()[0], $messageId, $source);
        $I->assertEquals(1, count($report->getAdded()));

        $flight->cancelled = true;
        $options = SavingOptions::savingByEmail(OwnerRepository::getOwner($user, null), $message2Id = bin2hex(random_bytes(3)), $source);
        $report = $processor->save([$flight], $options);
        $I->assertEquals(1, count($report->getRemoved()));
        $tripId = $I->grabFromDatabase('Trip', 'TripID', ['UserID' => $this->UserID]);
        $I->assertNotNull($tripId);
        $this->assertEmailSource($I, $report->getRemoved()[0]->getSegments()[0], $messageId, $source);
        $I->seeInDatabase('Trip', ['TripID' => $tripId, 'Hidden' => '1']);

        $hotelJson = '{"status":"Confirmed","providerInfo":{"code":"spg","name":"Starwood Hotels"},"confirmationNumbers":[{"number":"1122334455","description":"Confirmation number"}],"hotelName":"Sheraton Philadelphia Downtown Hotel","address":{"text":"201 North 17th Street, Philadelphia, Pennsylvania 19103 United States","addressLine":"201 North 17th Street","city":"Philadelphia","stateName":"Pennsylvania","countryName":"United States","postalCode":"19103","lat":39.9569828,"lng":-75.1674669,"timezone":-14400},"checkInDate":"2019-01-01T13:30:00","checkOutDate":"2019-01-05T12:00:00","phone":"+1-22-3333","fax":"+1-66-77899","guests":[{"name":"John D.","full":false},{"name":"Jane D.","full":false}],"guestCount":2,"kidsCount":3,"roomsCount":1,"rooms":[{"type":"King bed","description":"Traditional, TV, free wi-fi","rate":"30$\/night","rateType":"King bed"}],"type":"hotelReservation"}';
        $hotel = $jms->deserialize($hotelJson, \AwardWallet\Schema\Itineraries\HotelReservation::class, 'json');
        $options = SavingOptions::savingByEmail(OwnerRepository::getOwner($user, null), $message3Id = bin2hex(random_bytes(3)), $source);
        $report = $processor->save([$hotel], $options);
        $I->seeInDatabase('Reservation', ['UserID' => $this->UserID]);
        $this->assertEmailSource($I, $report->getAdded()[0], $message3Id, $source);
        $I->assertEquals(1, count($report->getAdded()));

        $hotelCancelledJson = '{"cancelled":true,"type":"hotelReservation","confirmationNumbers":[{"number":"1122334455"}]}';
        $hotelCancelled = $jms->deserialize($hotelCancelledJson, \AwardWallet\Schema\Itineraries\HotelReservation::class, 'json');
        $options = SavingOptions::savingByEmail(OwnerRepository::getOwner($user, null), $message4Id = bin2hex(random_bytes(3)), $source);
        $report = $processor->save([$hotelCancelled], $options);
        $I->assertEquals(1, count($report->getRemoved()));
        $this->assertEmailSource($I, $report->getRemoved()[0], $message3Id, $source);
        $I->seeInDatabase('Reservation', ['UserID' => $this->UserID, 'Hidden' => '1']);
    }

    public function saveWithReceivedDate(\TestSymfonyGuy $I)
    {
        $time = time();
        $request = $this->_getFakeRequest();
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);

        $tripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertNotEmpty($tripId);
        $trip = $I->query("select * from Trip where TripID = ?", [$tripId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertGreaterOrEquals($time, strtotime($trip['CreateDate']));
        $I->assertEquals(date("Y-m-d H:i:s", strtotime($request['metadata']['receivedDateTime'])), $trip['FirstSeenDate']);
    }

    public function update(\TestSymfonyGuy $I)
    {
        $request = $this->_getFakeRequest();
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $I->seeInDatabase("Trip", ["UserID" => $this->UserID]);
        $I->amOnPage('/timeline/data?_switch_user=' . $I->grabFromDatabase("Usr", "Login", ["UserID" => $this->UserID]));
        $seats = $I->grabDataFromResponseByJsonPath('$.segments[1].details.columns[0].rows[2]')[0];
        $I->assertSame('12A, 25B', $seats['value']);
        $I->assertFalse(isset($seats['prevValue']));
        $changed = $I->grabDataFromResponseByJsonPath('$.segments[1].changed');
        $I->assertEmpty($changed);

        $request['itineraries'][0]['segments'][0]['seats'] = ['25B', '37C'];
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $I->seeInDatabase("Trip", ["UserID" => $this->UserID]);
        $I->amOnPage('/timeline/data');
        $seats = $I->grabDataFromResponseByJsonPath('$.segments[1].details.columns[0].rows[2]')[0];
        $I->assertSame('25B, 37C', $seats['value']);
        $I->assertTrue(isset($seats['prevValue']));
        $I->assertSame('12A, 25B', $seats['prevValue']);
        $changed = $I->grabDataFromResponseByJsonPath('$.segments[1].changed')[0];
        $I->assertTrue($changed);
    }

    public function testAddFromScannerToFamilyMember(\TestSymfonyGuy $I)
    {
        $fmId = $I->createFamilyMember($this->UserID, "Violette", "Snow");
        $request = $this->_getFakeRequest("now", $fmId);

        $I->sendPOST(self::callbackURL, json_encode($request));

        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $tripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID, "UserAgentID" => $fmId]);
        $I->assertNotEmpty($tripId);
        $I->seeInDatabase("TripSegment", ["TripID" => $tripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "MSP", "Hidden" => 0]);
        $I->seeInDatabase("TripSegment", ["TripID" => $tripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "PDX", "Hidden" => 0]);
    }

    public function testAddForwardedToFamilyMember(\TestSymfonyGuy $I)
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $I->grabService("event_dispatcher");
        /** @var ItineraryUpdateEvent[] $events */
        $events = [];
        $eventDispatcher->addListener(ItineraryUpdateEvent::NAME, function (ItineraryUpdateEvent $event) use (&$events) {
            $events[] = $event;
        });

        // add to main user
        $myLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->UserID]);
        $request = $this->_getFakeRequest("now", null, $myLogin . '@awardwallet.com');
        $request['userData'] = json_encode(["awid" => "5de4b5f4a0f3b", "aw-dto" => "whatisit"]);
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);

        $emails = $I->grabRawMails();
        $I->assertCount(1, $emails);
        $I->assertEquals($I->grabFromDatabase('Usr', 'Email', ['UserID' => $this->UserID]), $emails[0]["to"]);
        $I->assertEquals("Ticketed Direct2U Itinerary for ROLAND DEAN BENTLEY on Feb 26, 2017 DEYYMR", $emails[0]["subject"]);
        $I->assertEquals(0, $I->grabMailMessagesCount());

        $I->assertCount(3, $events);
        $I->assertFalse($events[0]->isSilent());

        $userTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertNotEmpty($userTripId);

        // add to family member
        $fmId = $I->createFamilyMember($this->UserID, "Violette", "Snow");
        $I->executeQuery("update UserAgent set Alias = 'violette' where UserAgentID = $fmId");

        $fmEmail = $myLogin . '.violette@awardwallet.com';
        $request = $this->_getFakeRequest("now", null, $fmEmail);

        $request['userData'] = json_encode(["awid" => "5de4b5f4a0f3b", "aw-dto" => "whatisit"]);

        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);

        $fmTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID, "UserAgentID" => $fmId]);
        $I->assertNotEmpty($fmTripId);
        $I->assertNotEquals($userTripId, $fmTripId);
        $I->seeInDatabase("TripSegment", ["TripID" => $fmTripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "MSP", "Hidden" => 0]);
        $I->seeInDatabase("TripSegment", ["TripID" => $fmTripId, "MarketingAirlineConfirmationNumber" => $this->recLoc, "DepCode" => "PDX", "Hidden" => 0]);
    }

    public function testNoForwardingOnParsed(\TestSymfonyGuy $I)
    {
        $myLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->UserID]);
        $request = $this->_getFakeRequest("now", null, $myLogin . EmailOptions::SILENT_SUFFIX . '@awardwallet.com');
        $request['userData'] = json_encode(["awid" => "5de4b5f4a0f3b", "aw-dto" => "whatisit"]);

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $I->grabService("event_dispatcher");
        /** @var ItineraryUpdateEvent[] $events */
        $events = [];
        $eventDispatcher->addListener(ItineraryUpdateEvent::NAME, function (ItineraryUpdateEvent $event) use (&$events) {
            $events[] = $event;
        });

        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);

        $emails = $I->grabRawMails();
        $I->assertCount(0, $emails);
        $I->assertEquals(0, $I->grabMailMessagesCount());

        $I->assertCount(3, $events);
        $I->assertTrue($events[0]->isSilent());

        $userTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertNotEmpty($userTripId);
    }

    public function testForwardingParsedFromProvider(\TestSymfonyGuy $I)
    {
        $myLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->UserID]);
        $request = $this->_getFakeRequest("now", null, $myLogin . EmailOptions::SILENT_SUFFIX . '@awardwallet.com');
        $request['userData'] = json_encode(["awid" => "5de4b5f4a0f3b", "aw-dto" => "whatisit"]);
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);

        $emails = $I->grabRawMails();
        $I->assertCount(0, $emails);
        $I->assertEquals(0, $I->grabMailMessagesCount());

        $userTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertNotEmpty($userTripId);
    }

    /**
     * @dataProvider silentDataProvider
     */
    public function testForwardingUnknownFromProvider(\TestSymfonyGuy $I, Example $example)
    {
        $myLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->UserID]);
        $request = $this->_getFakeRequest("now", null, $myLogin . ($example['silent'] ? EmailOptions::SILENT_SUFFIX : '') . '@awardwallet.com');
        $request['userData'] = json_encode(["awid" => "5de4b5f4a0f3b", "aw-dto" => "whatisit"]);
        unset($request['itineraries']);
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);

        $expectedEmailCount = $example['expectedEmail'] ? 1 : 0;
        $emails = $I->grabRawMails();
        $I->assertCount($expectedEmailCount, $emails);
        $I->assertEquals(0, $I->grabMailMessagesCount());

        $userTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertEmpty($userTripId);
    }

    /**
     * @dataProvider silentDataProvider
     */
    public function testSilentUnrecognized(\TestSymfonyGuy $I, Example $example)
    {
        $myLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->UserID]);
        $request = $this->_getFakeRequest("now", null, $myLogin . ($example['silent'] ? EmailOptions::SILENT_SUFFIX : '') . '@awardwallet.com');

        $request['userData'] = json_encode(["awid" => "5de4b5f4a0f3b", "aw-dto" => "whatisit"]);
        $request['metadata']['from']['email'] = 'someunknown@domain.com';
        unset($request['itineraries']);
        $request['fromProvider'] = false;
        $request['status'] = 'review';

        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);

        $expectedEmailCount = $example['expectedEmail'] ? 1 : 0;
        $emails = $I->grabRawMails();
        $I->assertCount(0, $emails); // no forwarded
        $I->assertEquals(0, $I->grabMailMessagesCount()); // no messages "unrecoginzed"

        $userTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertEmpty($userTripId);
    }

    /**
     * @dataProvider silentDataProvider
     */
    public function testBalanceUpdated(\TestSymfonyGuy $I, Example $example)
    {
        $myLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->UserID]);
        $accountId = $I->createAwAccount($this->UserID, "testprovider", "balance.random", null, ["Balance" => 1, 'UpdateDate' => (new \DateTime('-5 day'))->format('Y-m-d H:i:s')]);
        $propertyId = $I->grabFromDatabase("ProviderProperty", "ProviderPropertyID", ["ProviderID" => Provider::TEST_PROVIDER_ID, "Code" => "Number"]);
        $I->haveInDatabase("AccountProperty", ["AccountID" => $accountId, "ProviderPropertyID" => $propertyId, "Val" => "12345"]);
        $I->haveInDatabase("AccountBalance", ["AccountID" => $accountId, "UpdateDate" => date("Y-m-d", strtotime("-5 day")), "Balance" => "1"]);
        $request = $this->_getFakeRequest("now", null, $myLogin . ($example['silent'] ? EmailOptions::SILENT_SUFFIX : '') . '@awardwallet.com');

        $request['userData'] = json_encode(["awid" => "5de4b5f4a0f3b", "aw-dto" => "whatisit"]);
        $request['providerCode'] = 'testprovider';
        $request['loyaltyAccount'] = [
            'balance' => 100,
            'properties' => [
                [
                    'code' => 'Number',
                    'name' => 'Number',
                    'kind' => 1,
                    'value' => 12345,
                ],
            ],
        ];
        unset($request['itineraries']);

        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $I->assertEquals(100, $I->grabFromDatabase("Account", "Balance", ["AccountID" => $accountId]));

        $expectedEmailCount = $example['expectedEmail'] ? 1 : 0;
        $emails = $I->grabRawMails();
        $I->assertCount($expectedEmailCount, $emails); // no forwarded

        $I->assertEquals($expectedEmailCount, $I->grabMailMessagesCount()); // one message "unrecoginzed"

        $userTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->UserID]);
        $I->assertEmpty($userTripId);
    }

    public function testUpdateOnly(\TestSymfonyGuy $I)
    {
        $myLogin = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->UserID]);
        $request = $this->_getFakeRequest("now", null, $myLogin . EmailOptions::UPDATE_ONLY_SUFFIX . '@awardwallet.com');
        $I->sendPOST(self::callbackURL, json_encode($request));
        $I->seeResponseCodeIs(200);
        $this->executeAsyncTask($I);
        $I->dontSeeInDatabase("Trip", ["UserID" => $this->UserID]);
    }

    protected function silentDataProvider(): array
    {
        return [
            ['silent' => false, 'expectedEmail' => true],
            ['silent' => true, 'expectedEmail' => false],
        ];
    }

    private function times()
    {
        return [
            ['baseDate' => 'now'],
            ['baseDate' => '-5 year'],
        ];
    }

    private function assertEmailSource(\TestSymfonyGuy $I, SourceListInterface $sourcable, string $messageId, ParsedEmailSource $source)
    {
        /** @var Email $current */
        $current = current($sourcable->getSources());
        $I->assertInstanceOf(Email::class, $current);
        $I->assertEquals("e.$messageId", $current->getId());
        $I->assertEquals($source->getUserEmail(), $current->getRecipient());
    }

    private function _getFakeRequest($baseDateStr = "now", $agentId = null, $toEmail = null)
    {
        $baseDate = strtotime($baseDateStr);

        $userData = ["user" => $this->UserID, "email" => "some@email.com"];

        if ($agentId !== null) {
            $userData["userAgent"] = $agentId;
        }

        $request = [
            'status' => 'success',
            'apiVersion' => 2,
            'requestId' => 'f8efc9b935f402b70e2e34fd672c86e1',
            'providerCode' => 'directravel',
            'fromProvider' => 'true',
            'itineraries' => [
                0 => [
                    'segments' => [
                        0 => [
                            'departure' => [
                                'airportCode' => 'MSP',
                                'name' => 'Minneapolis (Minneapolis Saint Paul International Airport), MN',
                                'localDateTime' => date('Y-m-d H:i:s', strtotime("tomorrow 09:00", $baseDate)),
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
                                'localDateTime' => date('Y-m-d H:i:s', strtotime("tomorrow 11:02", $baseDate)),
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
                            'seats' => ['12A,25B'],
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
                                'localDateTime' => date('Y-m-d H:i:s', strtotime("+2 days 14:00", $baseDate)),
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
                                'localDateTime' => date('Y-m-d H:i:s', strtotime("+2 days 19:15", $baseDate)),
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
                            'name' => 'John Smith',
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
                    'checkInDate' => date('Y-m-d H:i:s', strtotime("tomorrow 00:00", $baseDate)),
                    'checkOutDate' => date('Y-m-d H:i:s', strtotime("+2 days 00:00", $baseDate)),
                    'phone' => '1-503-655-7900',
                    'fax' => '1-503-655-1861',
                    'guests' => [
                        0 => [
                            'name' => "John Smith",
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
                2 => [
                    'travelAgency' => null,
                    'pricingInfo' => [
                        'total' => 378.86,
                        'cost' => null,
                        'discount' => null,
                        'spentAwards' => null,
                        'currencyCode' => 'USD',
                        'fees' => [
                        ],
                    ],
                    'status' => null,
                    'reservationDate' => null,
                    'providerInfo' => [
                        'code' => 'rentacar',
                        'name' => 'Enterprise',
                        'accountNumbers' => null,
                        'earnedRewards' => null,
                    ],
                    'cancellationPolicy' => null,
                    'confirmationNumbers' => [
                        0 => [
                            'number' => '4MZSJZ',
                            'description' => null,
                            'isPrimary' => null,
                        ],
                    ],
                    'pickup' => [
                        'address' => [
                            'text' => 'DIXIE, 1475 AEROWOOD DRIVEUNIT #2MISSISSAUGA,ON L4W1C2',
                            'addressLine' => '1475 Aerowood Drive',
                            'city' => 'Mississauga',
                            'stateName' => 'Ontario',
                            'countryName' => 'Canada',
                            'postalCode' => 'L4W 1C2',
                            'lat' => 43.6442405,
                            'lng' => -79.6341131,
                            'timezone' => -18000,
                        ],
                        'localDateTime' => date('Y-m-d H:i:s', strtotime("+3 days 00:00", $baseDate)),
                        'openingHours' => null,
                        'phone' => '(905) 238-0775',
                        'fax' => null,
                    ],
                    'dropoff' => [
                        'address' => [
                            'text' => 'Friday, February 1, 2019 10:30 AM',
                            'addressLine' => null,
                            'city' => null,
                            'stateName' => null,
                            'countryName' => null,
                            'postalCode' => null,
                            'lat' => null,
                            'lng' => null,
                            'timezone' => null,
                        ],
                        'localDateTime' => date('Y-m-d H:i:s', strtotime("+4 days 00:00", $baseDate)),
                        'openingHours' => null,
                        'phone' => null,
                        'fax' => null,
                    ],
                    'car' => [
                        'type' => null,
                        'model' => '2018 KIA SOUL 4DLX GRAY MED',
                        'imageUrl' => null,
                    ],
                    'discounts' => null,
                    'driver' => [
                        'name' => 'WAYNE ALLEN',
                        'full' => null,
                    ],
                    'pricedEquipment' => null,
                    'rentalCompany' => null,
                    'type' => 'carRental',
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
                        'email' => 'somerandom1@useremail.com',
                    ],
                    1 => [
                        'name' => null,
                        'email' => 'somerandom2@useremail.com',
                    ],
                ],
                'cc' => [],
                'subject' => 'Ticketed Direct2U Itinerary for testUser',
                'receivedDateTime' => date('Y-m-d H:i:s', strtotime("-2 days 16:19:43", $baseDate)),
                'userEmail' => 'somerandom3@useremail.com',
                'nested' => false,
            ],
            'method' => 'auto',
            'userData' => json_encode($userData),
        ];
        $email = file_get_contents(self::fileEML);

        if ($email !== null) {
            // unused actually
            $request['metadata']['to'] = [['name' => null, 'email' => $toEmail]];
            // this one is used
            $email = str_ireplace('TAYLOR_N8N96X@AWARDWALLET.COM', $toEmail, $email);
        }

        $request['email'] = base64_encode($email);

        return $request;
    }

    private function executeAsyncTask(\TestSymfonyGuy $I)
    {
        /** @var EmailCallbackExecutor $executor */
        $executor = $I->grabService(EmailCallbackExecutor::class);
        $executor->execute($this->task);
    }
}
