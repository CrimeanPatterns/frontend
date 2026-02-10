<?php

namespace AwardWallet\Tests\FunctionalSymfony\FlightStats;

use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\FlightStats\AirlineConverter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\Subscriber;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class TripAlertControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const DEFAULT_DATE = '2016-12-14';

    private $flightNumber;
    private $userId;
    private $tripSegmentId;
    private $tripId;
    private $id;
    private $authToken;

    public function _before(\TestSymfonyGuy $I)
    {
        $I->mockService(AirlineConverter::class, $I->stubMakeEmpty(AirlineConverter::class, [
            'IataToFSCode' => 'AA',
            'FSCodeToIata' => 'AA',
            'FSCodeToName' => 'American Airlines',
        ]));

        $this->userId = $I->createAwUser();
        $this->tripId = $I->haveInDatabase("Trip", [
            "UserID" => $this->userId,
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "aa"]),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $this->flightNumber = StringHandler::getRandomCode(10);
        $this->tripSegmentId = $I->createTripSegment([
            "TripID" => $this->tripId,
            "FlightNumber" => $this->flightNumber,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => 'LGA',
            "DepName" => 'Los Angeles',
            "DepDate" => '2017-03-16 17:40:00',
            "ScheduledDepDate" => '2017-03-16 17:40:00',
            "ArrCode" => 'CMH',
            'ArrName' => 'Ohio',
            "ArrDate" => '2017-03-16 19:45:00',
            "ScheduledArrDate" => '2017-03-16 19:45:00',
        ]);
        $this->id = "AA.{$this->flightNumber}.LGA.2017-03-16T17:40";
        $I->executeQuery("delete from Overlay where Kind = 'S' and ID = '{$this->id}'");
        $this->authToken = sha1($this->userId . $I->getContainer()->getParameter("secret") . Subscriber::SALT);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $I->verifyMocks();
        $I->executeQuery("delete from Overlay where Kind = 'S' and ID = '{$this->id}'");
    }

    public function testFlightDepartureDelay(\TestSymfonyGuy $I)
    {
        $scheduled = strtotime("-30 minute");
        $estimated = strtotime("+2 hour", $scheduled);
        $vars = [
            '[flightNumber]' => $this->flightNumber,
            '[scheduledDepDate]' => date("Y-m-d\\TH:i:s", $scheduled),
            '[scheduledArrDate]' => date("Y-m-d\\TH:i:s", strtotime("+4 hour", $scheduled)),
            '[estimatedDepDate]' => date("Y-m-d\\TH:i:s", $estimated),
            '[estimatedArrDate]' => date("Y-m-d\\TH:i:s", strtotime("+4 hour", $estimated)),
        ];

        $userId2 = $I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
        ]);
        $accountId = $I->createAwAccount($userId2, "testprovider", "balance.random");

        $tripId2 = $I->haveInDatabase("Trip", [
            "UserID" => $userId2,
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "aa"]),
            "RecordLocator" => 'FLIGHT1',
            "AccountID" => $accountId,
        ]);
        $tripSegmentId2 = $I->createTripSegment([
            "TripID" => $tripId2,
            "FlightNumber" => $this->flightNumber,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => 'LGA',
            "DepName" => 'Los Angeles',
            "ScheduledDepDate" => $vars['[scheduledDepDate]'],
            "DepDate" => $vars['[scheduledDepDate]'],
            "ArrCode" => 'CMH',
            'ArrName' => 'Ohio',
            "ArrDate" => $vars['[scheduledArrDate]'],
            "ScheduledArrDate" => $vars['[scheduledArrDate]'],
            "DepGeoTagID" => FindGeoTag("LGA")["GeoTagID"],
        ]);
        $I->assertNull($I->grabFromDatabase("Account", "QueueDate", ["AccountID" => $accountId]));

        $userId3 = $I->createAwUser();
        $tripId3 = $I->haveInDatabase("Trip", [
            "UserID" => $userId3,
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "aa"]),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $tripSegmentId3 = $I->createTripSegment([
            "TripID" => $tripId3,
            "FlightNumber" => $this->flightNumber,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => 'LGA',
            "DepName" => 'LGA',
            "ScheduledDepDate" => $vars['[scheduledDepDate]'],
            "DepDate" => $vars['[scheduledDepDate]'],
            "ArrCode" => 'CMH',
            "ArrName" => 'CMH',
            "ArrDate" => $vars['[scheduledArrDate]'],
            "ScheduledArrDate" => $vars['[scheduledArrDate]'],
            "DepGeoTagID" => FindGeoTag("LGA")["GeoTagID"],
        ]);
        $I->assertNull($I->grabFromDatabase("Account", "QueueDate", ["AccountID" => $accountId]));

        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::exactly(2, function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::exactly(2, function (Content $content, array $devices) use ($I, $estimated): bool {
                $I->assertEquals("Flight Delay!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("American Airlines flight {$this->flightNumber} LGA to CMH is delayed by 54 minutes. Now departing on " . date("F j, Y, \\a\\t g:i A", $estimated) . " from Terminal B, Gate C8", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_FLIGHT_DELAY, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $id = "AA.{$this->flightNumber}.LGA." . date("Y-m-d\\TH:i", $scheduled);
        $I->executeQuery("delete from Overlay where Kind = 'S' and ID = '{$id}'");
        $this->authToken = sha1($userId2 . $I->getContainer()->getParameter("secret") . Subscriber::SALT);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $userId2]);
        $I->sendPOST($route, $this->loadAlert('FlightDepartureDelay', $vars));
        $I->seeResponseCodeIs(200);

        $data = $I->grabFromDatabase("Overlay", "Data", ["Kind" => "S", "ID" => $id]);
        $I->assertEquals('{"departure":{"terminal":"B","localDateTime":"' . $vars['[estimatedDepDate]'] . '","gate":"C8"},"arrival":{"localDateTime":"' . $vars['[estimatedArrDate]'] . '","gate":"B20","baggage":"5"}}', $data);
        $I->assertEquals(date("Y-m-d H:i:s", $estimated), $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $tripSegmentId2]));
        $I->assertEquals(date("Y-m-d H:i:s", strtotime("+4 hour", $estimated)), $I->grabFromDatabase("TripSegment", "ArrDate", ["TripSegmentID" => $tripSegmentId2]));
        $I->assertEquals(date("Y-m-d H:i:s", $estimated), $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $tripSegmentId3]));
        $I->assertEquals(date("Y-m-d H:i:s", strtotime("+4 hour", $estimated)), $I->grabFromDatabase("TripSegment", "ArrDate", ["TripSegmentID" => $tripSegmentId3]));
        $I->assertEquals('C8', $I->grabFromDatabase("TripSegment", "DepartureGate", ["TripSegmentID" => $tripSegmentId2]));
        $I->assertEquals('B20', $I->grabFromDatabase("TripSegment", "ArrivalGate", ["TripSegmentID" => $tripSegmentId2]));
        $I->assertEquals('C8', $I->grabFromDatabase("TripSegment", "DepartureGate", ["TripSegmentID" => $tripSegmentId3]));
        $I->assertEquals('B20', $I->grabFromDatabase("TripSegment", "ArrivalGate", ["TripSegmentID" => $tripSegmentId3]));
        $tzLocation = $I->query("select TimeZoneLocation from GeoTag where Address = 'LGA'")->fetchColumn();
        $offset = (new \DateTimeZone($tzLocation))->getOffset(new \DateTime());
        $I->assertEquals(date("Y-m-d H:i:s", strtotime("+1 hour", $scheduled - $offset)), $I->grabFromDatabase("Account", "QueueDate", ["AccountID" => $accountId]));
        $I->assertNotEmpty($I->grabFromDatabase('Trip', 'LastParseDate', ['TripID' => $tripId2]));
        $I->assertNotEmpty($I->grabFromDatabase('Trip', 'LastParseDate', ['TripID' => $tripId3]));
    }

    public function testDoubleSegment(\TestSymfonyGuy $I)
    {
        $scheduled = strtotime("-30 minute");
        $estimated = strtotime("+2 hour", $scheduled);
        $vars = [
            '[flightNumber]' => $this->flightNumber,
            '[scheduledDepDate]' => date("Y-m-d\\TH:i:s", $scheduled),
            '[scheduledArrDate]' => date("Y-m-d\\TH:i:s", strtotime("+4 hour", $scheduled)),
            '[estimatedDepDate]' => date("Y-m-d\\TH:i:s", $estimated),
            '[estimatedArrDate]' => date("Y-m-d\\TH:i:s", strtotime("+4 hour", $estimated)),
        ];

        $tripId2 = $I->haveInDatabase("Trip", [
            "UserID" => $this->userId,
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "aa"]),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $tripSegmentId2 = $I->createTripSegment([
            "TripID" => $tripId2,
            "FlightNumber" => $this->flightNumber,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => 'LGA',
            "DepName" => 'Los Angeles',
            "ScheduledDepDate" => $vars['[scheduledDepDate]'],
            "DepDate" => $vars['[scheduledDepDate]'],
            "ArrCode" => 'CMH',
            'ArrName' => 'Ohio',
            "ArrDate" => $vars['[scheduledArrDate]'],
            "ScheduledArrDate" => $vars['[scheduledArrDate]'],
            "DepGeoTagID" => FindGeoTag("LGA")["GeoTagID"],
        ]);

        $tripId3 = $I->haveInDatabase("Trip", [
            "UserID" => $this->userId,
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "aa"]),
            "RecordLocator" => 'FLIGHT1',
        ]);
        $tripSegmentId3 = $I->createTripSegment([
            "TripID" => $tripId3,
            "FlightNumber" => $this->flightNumber,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => 'LGA',
            "DepName" => 'Los Angeles',
            "ScheduledDepDate" => $vars['[scheduledDepDate]'],
            "DepDate" => $vars['[scheduledDepDate]'],
            "ArrCode" => 'CMH',
            'ArrName' => 'Ohio',
            "ArrDate" => $vars['[scheduledArrDate]'],
            "ScheduledArrDate" => $vars['[scheduledArrDate]'],
            "DepGeoTagID" => FindGeoTag("LGA")["GeoTagID"],
        ]);

        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::exactly(1, function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::exactly(1, function (Content $content, array $devices) use ($I, $estimated): bool {
                $I->assertEquals("Flight Delay!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("American Airlines flight {$this->flightNumber} LGA to CMH is delayed by 54 minutes. Now departing on " . date("F j, Y, \\a\\t g:i A", $estimated) . " from Terminal B, Gate C8", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_FLIGHT_DELAY, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $id = "AA.{$this->flightNumber}.LGA." . date("Y-m-d\\TH:i", $scheduled);
        $I->executeQuery("delete from Overlay where Kind = 'S' and ID = '{$id}'");
        $this->authToken = sha1($this->userId . $I->getContainer()->getParameter("secret") . Subscriber::SALT);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('FlightDepartureDelay', array_merge($vars, ["B20" => "B21"])));
        $I->assertEquals(date("Y-m-d H:i:s", $estimated), $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $tripSegmentId2]));
        $I->seeResponseCodeIs(200);

        $I->assertEquals('B21', $I->grabFromDatabase("TripSegment", "ArrivalGate", ["TripSegmentID" => $tripSegmentId2]));
    }

    public function testLegBaggageChange(\TestSymfonyGuy $I)
    {
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::never(),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegBaggageChange', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
        $data = $I->grabFromDatabase("Overlay", "Data", ["Kind" => "S", "ID" => $this->id]);
        $I->assertEquals('{"departure":{},"arrival":{"baggage":"Belt 5"}}', $data);
    }

    public function testLegBaggageSecondChange(\TestSymfonyGuy $I)
    {
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Flight Baggage Change", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_FLIGHT_BAGGAGE_CHANGE, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegBaggageChange', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
        $I->sendPOST($route, $this->loadAlert('LegBaggageChange', ['[flightNumber]' => $this->flightNumber, 'Belt 5' => 'Belt 6']));
        $I->seeResponseCodeIs(200);

        $data = $I->grabFromDatabase("Overlay", "Data", ["Kind" => "S", "ID" => $this->id]);
        $I->assertEquals('{"departure":{},"arrival":{"baggage":"Belt 6"}}', $data);
    }

    public function testMixTwoChanges(\TestSymfonyGuy $I)
    {
        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegDepartureInfo', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegBaggageChange', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);

        $data = $I->grabFromDatabase("Overlay", "Data", ["Kind" => "S", "ID" => $this->id]);
        $I->assertEquals('{"departure":{"terminal":"B","localDateTime":"2017-03-16T18:36:00","gate":"C8"},"arrival":{"localDateTime":"2017-03-16T20:41:00","gate":"B20","baggage":"Belt 5"}}', $data);
    }

    public function testLegArrived(\TestSymfonyGuy $I)
    {
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("You have arrived!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Welcome to Columbus, you are arriving at Terminal B, Gate B20. Luggage will be available on carousel #5", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_LEG_ARRIVED, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegArrived', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
        $data = $I->grabFromDatabase("Overlay", "Data", ["Kind" => "S", "ID" => $this->id]);
        $I->assertEquals('{"departure":{"terminal":"B","localDateTime":"2017-03-16T18:36:00","gate":"C8"},"arrival":{"terminal":"B","localDateTime":"2017-03-16T20:41:00","gate":"B20","baggage":"5"}}', $data);
    }

    public function testDuplicateLegArrived(\TestSymfonyGuy $I)
    {
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::exactly(2, function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::exactly(2, function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("You have arrived!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Welcome to Columbus, you are arriving at Terminal B, Gate B20. Luggage will be available on carousel #5", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_LEG_ARRIVED, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        // create other user with the same trip
        $otherUserId = $I->createAwUser();
        $otherTripId = $I->haveInDatabase("Trip", [
            "UserID" => $otherUserId,
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "aa"]),
            "RecordLocator" => 'FLIGHT2',
        ]);
        $this->tripSegmentId = $I->createTripSegment([
            "TripID" => $otherTripId,
            "FlightNumber" => $this->flightNumber,
            "MarketingAirlineConfirmationNumber" => "FLIGHT2",
            "DepCode" => 'LGA',
            "DepName" => 'Los Angeles',
            "DepDate" => '2017-03-16 17:40:00',
            "ScheduledDepDate" => '2017-03-16 17:40:00',
            "ArrCode" => 'CMH',
            'ArrName' => 'Ohio',
            "ArrDate" => '2017-03-16 19:45:00',
            "ScheduledArrDate" => '2017-03-16 19:45:00',
        ]);

        // first call should send pushes to both users
        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegArrived', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);

        $data = $I->grabFromDatabase("Overlay", "Data", ["Kind" => "S", "ID" => $this->id]);
        $I->assertEquals('{"departure":{"terminal":"B","localDateTime":"2017-03-16T18:36:00","gate":"C8"},"arrival":{"terminal":"B","localDateTime":"2017-03-16T20:41:00","gate":"B20","baggage":"5"}}', $data);

        // next call should send no pushes, prevent duplicates
        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $otherUserId]);
        $I->sendPOST($route, $this->loadAlert('LegArrived', ['[flightNumber]' => $this->flightNumber, $this->authToken => sha1($otherUserId . $I->getContainer()->getParameter("secret") . Subscriber::SALT)]));
        $I->seeResponseCodeIs(200);
    }

    public function testExcludeSNTerminal(\TestSymfonyGuy $I)
    {
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("You have arrived!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Welcome to Columbus, you are arriving at Gate D43. Luggage will be available on carousel #5", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_LEG_ARRIVED, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegArrivedSN', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testLegArrivedOnHidden(\TestSymfonyGuy $I)
    {
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::never(),
            'send' => Stub::never(),
        ]);
        $I->mockService(Sender::class, $mock);

        $I->executeQuery("update TripSegment set Hidden = 1 where TripSegmentID = {$this->tripSegmentId}");
        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegArrived', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testHotelPhone(\TestSymfonyGuy $I)
    {
        $I->haveInDatabase("Reservation", [
            "UserID" => $this->userId,
            "HotelName" => "Hilton",
            "CheckInDate" => '2017-03-16 14:00',
            "CheckOutDate" => '2017-03-17 12:00',
            "Phone" => '(1)(510) 547-7888',
            "CreateDate" => date("Y-m-d H:i:s"),
        ]);

        $call = 0;
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::exactly(2, function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::exactly(2, function (Content $content, array $devices) use ($I, &$call): bool {
                switch ($call) {
                    case 0:
                        $I->assertEquals("You have arrived!", $content->title->trans($I->getContainer()->get("translator")));
                        $I->assertEquals("Welcome to Columbus, you are arriving at Terminal B, Gate B20. Luggage will be available on carousel #5", $content->message->trans($I->getContainer()->get("translator")));

                        break;

                    case 1:
                        $I->assertEquals("Phone Number", $content->title->trans($I->getContainer()->get("translator")));
                        $I->assertEquals("The phone number for Hilton is (1)(510) 547-7888, tap to call", $content->message->trans($I->getContainer()->get("translator")));
                        $I->assertEquals(300, $content->options->getDelay());
                        $I->assertEquals(Content::TYPE_HOTEL_PHONE, $content->type);
                        $I->assertEquals('15105477888', $content->target->data);

                        break;

                    default:
                        $I->fail("called too many times");
                }
                $call++;

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('LegArrived', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testFlightCancellation(\TestSymfonyGuy $I)
    {
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Flight Cancellation!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("American Airlines flight {$this->flightNumber} LGA to CMH on March 16, 2017 at 5:40 PM has been canceled", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_FLIGHT_CANCELLATION, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('FlightCancellation', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
        $I->assertEquals(1, $I->grabFromDatabase("TripSegment", "Hidden", ["TripSegmentID" => $this->tripSegmentId]));
    }

    public function testFlightReinstated(\TestSymfonyGuy $I)
    {
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Flight Reinstated", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Previously canceled flight American Airlines {$this->flightNumber} LGA to CMH on March 16, 2017 at 5:40 PM has been reinstated", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_FLIGHT_REINSTATED, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $I->executeQuery("update TripSegment set Hidden = 1 where TripSegmentID = {$this->tripSegmentId}");
        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('FlightReinstated', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripSegmentID" => $this->tripSegmentId]));
    }

    public function testConnectionInfo(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Connection Info", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Welcome to San Francisco, you are arriving at Terminal INTL, Gate A1. Your next flight, American Airlines 4870 to SEA, is scheduled to depart at 10:33 AM, from Terminal INTL, Gate A1 (-33 minutes connection)", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_CONNECTION_INFO, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('ConnectionInfo', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testFlightArrivedConnectionInfo(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Connection Info", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Welcome to San Francisco, you are arriving at Terminal INTL, Gate A1. Your next flight, American Airlines 4870 to SEA, is scheduled to depart at 10:33 AM, from Terminal INTL, Gate A1 (-33 minutes connection)", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_CONNECTION_INFO, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('FlightArrivedWithConnection', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testFlightArrivedConnectionInfoNoStatus(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::never(),
            'send' => Stub::never(),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('FlightArrivedWithConnectionNoStatus', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testFlightArrivedThenConnectionInfo(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Connection Info", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Welcome to San Francisco, you are arriving at Terminal INTL, Gate A1. Your next flight, American Airlines 4870 to SEA, is scheduled to depart at 10:33 AM, from Terminal INTL, Gate A1 (-33 minutes connection)", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_CONNECTION_INFO, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('FlightArrivedWithConnection', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
        $I->sendPOST($route, $this->loadAlert('ConnectionInfo', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testFlightArrivedNoConnection(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::never(),
            'send' => Stub::never(),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('FlightArrivedNoConnection', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testConnectionInfoTwoUsers(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);

        $user2Id = $I->createAwUser();
        $trip2Id = $I->haveInDatabase('Trip', [
            'UserID' => $user2Id,
            'ProviderID' => $I->grabFromDatabase('Provider', 'ProviderID', ['Code' => 'aa']),
            'RecordLocator' => 'FLIGHT2',
        ]);
        $this->createTripSegment(
            $I,
            $trip2Id,
            $this->flightNumber . '1',
            'LAX',
            'SFO',
            self::DEFAULT_DATE . ' 08:00:00',
            self::DEFAULT_DATE . ' 09:36:00'
        );
        $this->createTripSegment(
            $I,
            $trip2Id,
            $this->flightNumber . '3',
            'SFO',
            'ABJ',
            self::DEFAULT_DATE . ' 10:00:00',
            self::DEFAULT_DATE . ' 14:15:00'
        );

        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Connection Info", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Welcome to San Francisco, you are arriving at Terminal INTL, Gate A1. Your next flight, American Airlines 4870 to SEA, is scheduled to depart at 10:33 AM, from Terminal INTL, Gate A1 (-33 minutes connection)", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_CONNECTION_INFO, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get('router')->generate('aw_tripalert_callback', ['userId' => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('ConnectionInfo', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testConnectionInfoGateChange(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);
        $I->mockService(TimeCommunicator::class, $I->stubMakeEmpty(TimeCommunicator::class, [
            'getCurrentDateTime' => Stub::once(function () {
                return new \DateTime('2016-12-14 09:40:00', new \DateTimeZone('America/Los_Angeles'));
            }),
        ]));

        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Gate Change Alert!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Your departure gate has changed from Terminal INTL, Gate A1 to Terminal INTL, Gate B12 for American Airlines flight {$this->flightNumber}2 to SEA departing at 10:33 AM (in 53 minutes)", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_CONNECTION_INFO_GATE_CHANGE, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('ConnectionInfoGateChange', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testConnectionInfoGateChangeTwoUsers(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);

        $user2Id = $I->createAwUser();
        $trip2Id = $I->haveInDatabase('Trip', [
            'UserID' => $user2Id,
            'ProviderID' => $I->grabFromDatabase('Provider', 'ProviderID', ['Code' => 'aa']),
            'RecordLocator' => 'FLIGHT2',
        ]);
        $this->createTripSegment(
            $I,
            $trip2Id,
            $this->flightNumber . '1',
            'LAX',
            'SFO',
            self::DEFAULT_DATE . ' 08:00:00',
            self::DEFAULT_DATE . ' 09:36:00'
        );
        $this->createTripSegment(
            $I,
            $trip2Id,
            $this->flightNumber . '3',
            'SFO',
            'ABJ',
            self::DEFAULT_DATE . ' 10:00:00',
            self::DEFAULT_DATE . ' 14:15:00'
        );

        $I->mockService(TimeCommunicator::class, $I->stubMakeEmpty(TimeCommunicator::class, [
            'getCurrentDateTime' => Stub::once(function () {
                return new \DateTime('2016-12-14 09:40:00', new \DateTimeZone('America/Los_Angeles'));
            }),
        ]));

        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) {
                return [
                    (new MobileDevice())
                        ->setDeviceKey(bin2hex(random_bytes(10)))
                        ->setDeviceType(MobileDevice::TYPE_IOS)
                        ->setUser($recipients[0])
                        ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Gate Change Alert!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Your departure gate has changed from Terminal INTL, Gate A1 to Terminal INTL, Gate B12 for American Airlines flight {$this->flightNumber}2 to SEA departing at 10:33 AM (in 53 minutes)", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_CONNECTION_INFO_GATE_CHANGE, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('ConnectionInfoGateChange', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    public function testFlightDepartureGateChange(\TestSymfonyGuy $I, ?int $date = null)
    {
        if ($date === null) {
            $date = strtotime('+1 month');
        }

        $dateStr = date('Y-m-d', $date);
        $this->createConnectionData($I, $dateStr);
        $I->mockService(TimeCommunicator::class, $I->stubMakeEmpty(TimeCommunicator::class, [
            'getCurrentDateTime' => Stub::once(function () use ($dateStr) {
                return new \DateTime("$dateStr 09:40:00", new \DateTimeZone('America/Los_Angeles'));
            }),
        ]));
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::once(function (array $recipients, array $deviceTypes, $contentType) use ($I) {
                return [
                    (new MobileDevice())
                    ->setDeviceKey($I->grabRandomString(10))
                    ->setDeviceType(MobileDevice::TYPE_IOS)
                    ->setUser($recipients[0])
                    ->setLang('en'),
                ];
            }),
            'send' => Stub::once(function (Content $content, array $devices) use ($I): bool {
                $I->assertEquals("Gate Change Alert!", $content->title->trans($I->getContainer()->get("translator")));
                $I->assertEquals("Your departure gate has changed from Terminal INTL, Gate A1 to Terminal INTL, Gate B12 for American Airlines flight {$this->flightNumber}2 to SEA departing at 10:33 AM (in 53 minutes)", $content->message->trans($I->getContainer()->get("translator")));
                $I->assertEquals(Content::TYPE_FLIGHT_DEPARTURE_GATE_CHANGE, $content->type);

                return true;
            }),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $tripSeg2 = $I->grabFromDatabase('TripSegment', 'TripSegmentID', [
            'TripID' => $this->tripId,
            'FlightNumber' => $this->flightNumber . 2,
        ]);
        $sql = "SELECT * FROM TripSegment WHERE TripSegmentID = {$tripSeg2} AND ChangeDate IS NOT NULL";
        $I->assertFalse($I->query($sql)->fetch(\PDO::FETCH_ASSOC));
        $I->sendPOST($route, $this->loadAlert(
            'FlightDepartureGateChange',
            ['[flightNumber]' => $this->flightNumber],
            ['/\d{4}-\d{2}-\d{2}/' => $dateStr]
        ));
        $I->seeResponseCodeIs(200);
        $I->assertNotFalse($I->query($sql)->fetch(\PDO::FETCH_ASSOC));
        $I->seeInDatabase('DiffChange', [
            'SourceID' => sprintf('S.%d', $tripSeg2),
            'Property' => 'DepartureGate',
        ]);
    }

    public function testDepartureAndConnectionGateDouble(\TestSymfonyGuy $I)
    {
        $date = strtotime('+1 month');
        $dateStr = date('Y-m-d', $date);
        $this->testFlightDepartureGateChange($I, $date);

        // we should not receive second push, when gate change is same as flight dep
        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert(
            'ConnectionInfoGateChange',
            ['[flightNumber]' => $this->flightNumber],
            ['/\d{4}-\d{2}-\d{2}/' => $dateStr]
        ));
        $I->seeResponseCodeIs(200);
    }

    public function testConnectionInfoGateNoChange(\TestSymfonyGuy $I)
    {
        $this->createConnectionData($I);
        $mock = $I->stubMakeEmpty(Sender::class, [
            'loadDevices' => Stub::never(),
            'send' => Stub::never(),
        ]);
        $I->mockService(Sender::class, $mock);

        $route = $I->getContainer()->get("router")->generate("aw_tripalert_callback", ["userId" => $this->userId]);
        $I->sendPOST($route, $this->loadAlert('ConnectionInfoGateNoChange', ['[flightNumber]' => $this->flightNumber]));
        $I->seeResponseCodeIs(200);
    }

    private function createConnectionData(\TestSymfonyGuy $I, ?string $date = null)
    {
        if (is_null($date)) {
            $date = self::DEFAULT_DATE;
        }

        $this->createTripSegment(
            $I, $this->tripId, $this->flightNumber . '1', 'LAX', 'SFO', $date . ' 08:00:00', $date . ' 09:36:00'
        );
        $this->createTripSegment(
            $I, $this->tripId, $this->flightNumber . '2', 'SFO', 'SEA', $date . ' 10:33:00', $date . ' 12:55:00'
        );
    }

    private function createTripSegment(
        \TestSymfonyGuy $I,
        int $tripId,
        string $flightNumber,
        string $depCode,
        string $arrCode,
        string $depDate,
        string $arrDate
    ) {
        $I->createTripSegment([
            "TripID" => $tripId,
            "FlightNumber" => $flightNumber,
            "DepCode" => $depCode,
            "DepName" => $depCode,
            "DepartureGate" => 'X1',
            "ScheduledDepDate" => $depDate,
            "DepDate" => $depDate,
            "ArrCode" => $arrCode,
            "ArrName" => $arrCode,
            "ArrDate" => $arrDate,
            "ScheduledArrDate" => $arrDate,
        ]);
    }

    private function loadAlert($name, $replaces, array $pregReplaces = [])
    {
        $data = file_get_contents(codecept_data_dir("FlightStatsAlert/{$name}.json"));
        $data = json_decode($data);
        $data->trip->attributes = ['AUTHTOKEN' => $this->authToken];
        $data->id = StringUtils::getRandomCode(10);
        $data = json_encode($data);
        $data = strtr($data, $replaces);
        $data = preg_replace(array_keys($pregReplaces), array_values($pregReplaces), $data);

        return $data;
    }
}
