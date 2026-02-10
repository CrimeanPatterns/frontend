<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\Common\API\Converter\V2\Loader;
use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Extra\ProviderData;
use AwardWallet\MainBundle\Email\CallbackProcessor;
use AwardWallet\MainBundle\Email\EmailOptions;
use AwardWallet\MainBundle\Email\Util;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\ItineraryMail\UpdateListener;
use AwardWallet\Schema\Parser\Component\Options;
use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Schema\Parser\Util\ArrayConverter;
use Codeception\Module\Aw;
use Doctrine\DBAL\Connection;

/**
 * @group frontend-unit
 */
class EmailTest extends BaseContainerTest
{
    /**
     * @var Usr
     */
    private $user;

    public function _before()
    {
        parent::_before();
        $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [], true /* staff user has access to test provider */);
        $this->assertNotNull($this->user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId));
    }

    public function _after()
    {
        parent::_after();
        $this->user = null;
    }

    public function testGmailConfirmation()
    {
        $processor = $this->getCallbackProcessor();
        $email = file_get_contents(__DIR__ . '/../_data/gmailConfirmation.eml');
        $email = str_ireplace("field210@awardwallet.com", $this->user->getLogin() . '@awardwallet.com', $email);
        $data = $this->buildData($email, null, null);
        $data->userData = json_encode(["source" => "plans"]);
        $result = $processor->process($data, new EmailOptions($data, false), null, null);
        $this->assertEquals(CallbackProcessor::SAVE_MESSAGE_SUCCESS, $result);
    }

    public function testTwoReservations()
    {
        $processor = $this->getCallbackProcessor();
        $parsed = [
            'Itineraries' => [
                [
                    'TripSegments' => [
                        0 => [
                            'FlightNumber' => '12345',
                            'DepDate' => strtotime("tomorrow 14:00"),
                            'DepCode' => 'DEN',
                            'DepName' => 'Denver Co',
                            'ArrDate' => strtotime("tomorrow 16:00"),
                            'ArrCode' => 'PHX',
                            'ArrName' => 'Phoenix Az',
                            'AirlineName' => 'WN',
                        ],
                    ],
                    'RecordLocator' => 'A7BU5R',
                    'Kind' => 'T',
                    'Passengers' => 'Marie E Dyroff',
                ],
                [
                    'TripSegments' => [
                        0 => [
                            'FlightNumber' => '54321',
                            'DepDate' => strtotime("+10 days 13:00"),
                            'DepCode' => 'PHX',
                            'DepName' => 'Phoenix Az',
                            'ArrDate' => strtotime("+10 days 15:00"),
                            'ArrCode' => 'LAX',
                            'ArrName' => 'Los Angeles',
                            'AirlineName' => 'UA',
                        ],
                    ],
                    'RecordLocator' => 'A7BU5S',
                    'Kind' => 'T',
                    'Passengers' => 'Marie E Dyroff',
                ],
            ],
        ];
        $email = file_get_contents(__DIR__ . '/../_data/expedia.eml');
        $email = str_ireplace("ialabuzheva.123@awardwallet.com", $this->user->getLogin() . '@awardwallet.com', $email);
        $data = $this->buildData($email, $parsed, 'delta');
        $mock = $this->createMock(UpdateListener::class);
        $mock->expects($this->exactly(2))
            ->method('onItineraryUpdate');
        $this->container->get('event_dispatcher')->addListener('aw.itinerary.update', [$mock, 'onItineraryUpdate']);
        $result = $processor->process($data, new EmailOptions($data, false), null, null);
        $this->assertEquals(CallbackProcessor::SAVE_MESSAGE_SUCCESS, $result);
    }

    public function testOldReservations()
    {
        $processor = $this->getCallbackProcessor();
        $parsed = [
            'Itineraries' => [
                [
                    'TripSegments' => [
                        0 => [
                            'FlightNumber' => '12345',
                            'DepDate' => strtotime("-1 month 14:00"),
                            'DepCode' => 'DEN',
                            'DepName' => 'Denver Co',
                            'ArrDate' => strtotime("-1 month 16:00"),
                            'ArrCode' => 'PHX',
                            'ArrName' => 'Phoenix Az',
                            'AirlineName' => 'DL',
                        ],
                    ],
                    'RecordLocator' => ($locator = strtoupper(bin2hex(openssl_random_pseudo_bytes(3)))),
                    'Kind' => 'T',
                    'Passengers' => 'Marie E Dyroff',
                ],
                [
                    'Kind' => 'L',
                    'Number' => ($rental = strtoupper(bin2hex(openssl_random_pseudo_bytes(4)))),
                    'PickupDatetime' => strtotime('-2 month 00:00'),
                    'PickupLocation' => 'PHL',
                    'DropoffDatetime' => strtotime('-1 month 00:00'),
                    'DropoffLocation' => 'PHL',
                    'PickupPhone' => '122-236-785',
                    'RenterName' => 'Den Matson',
                ],
                [
                    'Kind' => 'E',
                    'ConfNo' => ($event = strtoupper(bin2hex(openssl_random_pseudo_bytes(4)))),
                    'Name' => 'Kentucky Fried Chicken',
                    'StartDate' => strtotime('-2 month 00:00'),
                    'EndDate' => strtotime('-1 month 00:00'),
                    'Address' => '123, Street',
                ],
                [
                    'Kind' => 'R',
                    'ConfirmationNumber' => ($hotel = strtoupper(bin2hex(openssl_random_pseudo_bytes(4)))),
                    'HotelName' => 'Sheraton Philadelphia Downtown Hotel',
                    'CheckInDate' => strtotime('-2 month 14:00'),
                    'CheckOutDate' => strtotime('-1 month 12:00'),
                    'Address' => '123, Street',
                ],
            ],
        ];
        $email = file_get_contents(__DIR__ . '/../_data/expedia.eml');
        $email = str_ireplace("ialabuzheva.123@awardwallet.com", $this->user->getLogin() . '@awardwallet.com', $email);
        $data = $this->buildData($email, $parsed, 'delta');
        $result = $processor->process($data, new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);

        $this->db->seeInDatabase("Trip", ["RecordLocator" => $locator, "UserID" => $this->user->getUserid()]);
        $this->db->seeInDatabase("Rental", ["Number" => $rental, "UserID" => $this->user->getUserid()]);
        $this->db->seeInDatabase("Restaurant", ["ConfNo" => $event, "UserID" => $this->user->getUserid()]);
        $this->db->seeInDatabase("Reservation", ["ConfirmationNumber" => $hotel, "UserID" => $this->user->getUserid()]);
    }

    public function testBoardingPass()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "IssuingAirlineConfirmationNumber" => "RECLOC",
        ]);
        $segmentId1 = $this->aw->createTripSegment([
            "TripID" => $tripId,
            'FlightNumber' => '666',
            'DepDate' => date("Y-m-d H:i:s", strtotime('tomorrow 13:00')),
            'DepCode' => 'LAX',
            'DepName' => 'LAX',
            'ArrDate' => date("Y-m-d H:i:s", strtotime('tomorrow 15:00')),
            'ArrCode' => 'BUF',
            'ArrName' => 'BUF',
            'AirlineName' => 'Delta',
        ]);
        $segmentId2 = $this->aw->createTripSegment([
            "TripID" => $tripId,
            'FlightNumber' => '777',
            'DepDate' => date("Y-m-d H:i:s", strtotime('tomorrow 18:00')),
            'DepCode' => 'BUF',
            'DepName' => 'BUF',
            'ArrDate' => date("Y-m-d H:i:s", strtotime('tomorrow 23:00')),
            'ArrCode' => 'JFK',
            'ArrName' => 'JFK',
            'AirlineName' => 'American',
        ]);
        $this->assertEquals(2, $this->db->query("select count(TripSegmentID) from TripSegment where Hidden = 0 and TripID = $tripId")->fetchColumn());

        /** @var Connection $connection */
        $connection = $this->container->get('database_connection');
        $segment = [
            'FlightNumber' => '666',
            'DepDate' => strtotime('tomorrow 13:00'),
            'DepCode' => 'LAX',
            'DepName' => 'LAX',
            'ArrDate' => strtotime('tomorrow 15:00'),
            'ArrCode' => 'BUF',
            'ArrName' => 'BUF',
            'AirlineName' => 'DL',
        ];
        $segment2 = [
            'FlightNumber' => '888',
            'DepDate' => strtotime('tomorrow 19:00'),
            'DepCode' => 'LGA',
            'DepName' => 'LGA',
            'ArrDate' => strtotime('tomorrow 23:30'),
            'ArrCode' => 'ORD',
            'ArrName' => 'ORD',
            'AirlineName' => 'DL',
        ];
        $segment3 = [
            'FlightNumber' => '888',
            'DepDate' => strtotime('tomorrow 14:00'),
            'DepCode' => 'ORD',
            'DepName' => 'ORD',
            'ArrDate' => strtotime('tomorrow 16:00'),
            'ArrCode' => 'LAX',
            'ArrName' => 'LAX',
            'AirlineName' => 'DL',
        ];
        $passurl = 'https://boarding_pass.url';
        $email = file_get_contents(__DIR__ . '/../_data/deltaBoardingPass.eml');
        $email = str_ireplace("%login%", $this->user->getLogin() . '@awardwallet.com', $email);

        // test that we will not delete segment parsed from other source than email (loyalty)
        $itinerary = [
            'Itineraries' => [
                [
                    'RecordLocator' => 'RECLOC',
                    'Kind' => 'T',
                    'Passengers' => ['John Doe'],
                    'TripSegments' => [$segment],
                ],
            ],
        ];
        $processor = $this->getCallbackProcessor();
        $result = $processor->process($data = $this->buildData($email, $itinerary, 'delta'), new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);
        $this->assertEmpty($this->db->query("select SourceID from TripSegment where TripSegmentID = $segmentId1")->fetchColumn());
        $this->assertEquals(2, $this->db->query("select count(TripSegmentID) from TripSegment where Hidden = 0 and TripID = $tripId")->fetchColumn());

        $boardingPass = [
            'BoardingPass' => [
                [
                    'DepCode' => $segment['DepCode'],
                    'FlightNumber' => $segment['FlightNumber'],
                    'BoardingPassURL' => $passurl,
                ],
            ],
        ];
        $result = $processor->process($data = $this->buildData($email, $boardingPass, 'delta'), new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);
        $data = $connection->executeQuery('select ts.BoardingPassURL, ts.TripID from TripSegment ts left join Trip t on ts.TripID = t.TripID
						where t.UserID = ? and t.RecordLocator = ? and ts.FlightNumber = ? and ts.DepCode = ?',
            [$this->user->getUserid(), 'RECLOC', '666', 'LAX'],
            [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals($passurl, $data['BoardingPassURL']);
        $this->assertEquals(2, $this->db->query("select count(TripSegmentID) from TripSegment where Hidden = 0 and TripID = $tripId")->fetchColumn());

        // test that we will add segment from email
        $itinerary = [
            'Itineraries' => [
                [
                    'RecordLocator' => 'RECLOC',
                    'Kind' => 'T',
                    'Passengers' => ['John Doe'],
                    'TripSegments' => [$segment, $segment2, $segment3],
                ],
            ],
        ];
        $result = $processor->process($data = $this->buildData($email, $itinerary, 'delta'), new EmailOptions($data, false), null, null);
        $this->assertEquals(4, $this->db->query("select count(TripSegmentID) from TripSegment where Hidden = 0 and TripID = $tripId")->fetchColumn());
        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);
        $this->assertEmpty($this->db->query("select SourceKind from TripSegment where TripSegmentID = $segmentId2")->fetchColumn());

        // test that we will delete segment from email
        $itinerary = [
            'Itineraries' => [
                [
                    'RecordLocator' => 'RECLOC',
                    'Kind' => 'T',
                    'Passengers' => ['John Doe'],
                    'TripSegments' => [$segment],
                ],
            ],
        ];
        $result = $processor->process($data = $this->buildData($email, $itinerary, 'delta'), new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);
        $this->assertEmpty($this->db->query("select SourceID from TripSegment where TripSegmentID = $segmentId1")->fetchColumn());
        $this->assertEquals(2, $this->db->query("select count(TripSegmentID) from TripSegment where Hidden = 0 and TripID = $tripId")->fetchColumn());
    }

    public function testAirportCodesOverwrite()
    {
        $this->markTestSkipped();
        $processor = $this->getCallbackProcessor();
        $startTime = strtotime("tomorrow 13:00");
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            "UpdateDate" => date("Y-m-d H:i:s"),
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => Aw::GMT_AIRPORT_2,
            "DepDate" => date("Y-m-d H:i:s", $startTime),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $startTime + DateTimeUtils::SECONDS_PER_HOUR * 3),
        ]);

        $email = file_get_contents(__DIR__ . '/../_data/deltaBoardingPass.eml');
        $email = str_ireplace("%login%", $this->user->getLogin() . '@awardwallet.com', $email);
        $depDate = strtotime('tomorrow 13:30');
        $segment = [
            'FlightNumber' => '1111',
            'DepDate' => $depDate,
            'DepCode' => TRIP_CODE_UNKNOWN,
            'DepName' => 'Los Angeles',
            'ArrDate' => strtotime('tomorrow 15:00'),
            'ArrCode' => TRIP_CODE_UNKNOWN,
            'ArrName' => 'Buffalo',
            'AirlineName' => 'Delta',
        ];
        $itinerary = [
            'Itineraries' => [
                [
                    'RecordLocator' => 'FLIGHT1',
                    'Kind' => 'T',
                    'Passengers' => ['John Doe'],
                    'TripSegments' => [$segment],
                ],
            ],
        ];
        $oldUpdateDate = $this->db->grabFromDatabase("Trip", "UpdateDate", ["TripID" => $tripId]);
        $result = $processor->process($data = $this->buildData($email, $itinerary, 'delta'), new EmailOptions($data, false), null, null);

        $this->assertEquals(Util::SAVE_MESSAGE_FAIL, $result);
        $this->assertEquals($oldUpdateDate, $this->db->grabFromDatabase("Trip", "UpdateDate", ["TripID" => $tripId]));

        return;
        /*
        $this->assertEquals('Los Angeles', $this->db->grabFromDatabase("TripSegment", "DepName", ["TripSegmentID" => $segmentId]));
        $this->assertEquals('Buffalo', $this->db->grabFromDatabase("TripSegment", "ArrName", ["TripSegmentID" => $segmentId]));
        $this->assertEquals(date("Y-m-d H:i:s", $depDate), $this->db->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $segmentId]));
        $this->assertEquals(Aw::GMT_AIRPORT_2, $this->db->grabFromDatabase("TripSegment", "DepCode", ["TripSegmentID" => $segmentId]));
        $this->assertEquals(Aw::GMT_AIRPORT, $this->db->grabFromDatabase("TripSegment", "ArrCode", ["TripSegmentID" => $segmentId]));
        */
    }

    private function getCallbackProcessor()
    {
        $this->mockServiceWithBuilder('aw.email.mailer')->method('send')->willReturn(true);
        $this->mockServiceWithBuilder('aw.solver.alias_provider.aircode');

        return $this->container->get('aw.email.callback_processor');
    }

    private function buildData($emailSource, ?array $parsed, $providerCode): ParseEmailResponse
    {
        $data = new ParseEmailResponse();
        $data->itineraries = [];

        if (isset($emailSource)) {
            $data->email = base64_encode($emailSource);
        }

        if (isset($parsed)) {
            $email = new Email('e', new Options());
            ArrayConverter::convertMaster($parsed, $email);
            $email->setProviderCode($providerCode);
            $extra = new Extra();
            $extra->provider = ProviderData::fromArray($this->container->get('database_connection')->fetchAssoc('select * from Provider where Code = ?', [$providerCode], [\PDO::PARAM_STR]));
            $extra->context->partnerLogin = '';
            $this->container->get('aw.solver.master')->solve($email, $extra);
            $converter = new Loader();

            foreach ($email->getItineraries() as $it) {
                $data->itineraries[] = $converter->convert($it, $extra);
            }

            foreach ($email->getBPasses() as $bp) {
                $new = new \AwardWallet\Common\API\Email\V2\BoardingPass\BoardingPass();
                $new->departureAirportCode = $bp->getDepCode() ?? null;
                $new->flightNumber = $bp->getFlightNumber() ?? null;
                $new->boardingPassUrl = $bp->getUrl() ?? null;
                $data->boardingPasses[] = $new;
            }
        }

        return $data;
    }
}
