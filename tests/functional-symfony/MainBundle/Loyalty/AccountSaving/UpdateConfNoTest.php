<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries as Schema;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-functional
 * @group testclosure
 */
class UpdateConfNoTest extends BaseUserTest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testUpdate()
    {
        $flight = new Schema\Flight();
        $flight->issuingCarrier = new Schema\IssuingCarrier();
        $flight->issuingCarrier->confirmationNumber = 'H8BUMT';
        $flight->travelAgency = new Schema\TravelAgency();
        $flight->travelAgency->confirmationNumbers = [
            new Schema\ConfNo(),
            new Schema\ConfNo(),
        ];
        $flight->travelAgency->confirmationNumbers[0]->number = 'ta_number_1';
        $flight->travelAgency->confirmationNumbers[1]->number = 'ta_number_2';
        $flight->segments = [
            new Schema\FlightSegment(),
            new Schema\FlightSegment(),
            new Schema\FlightSegment(),
            new Schema\FlightSegment(),
        ];
        $flight->segments[0]->departure = new Schema\TripLocation();
        $flight->segments[0]->departure->localDateTime = date('Y-m-dTH:i:s', mktime(0, 10, 0, 1, 1, date("Y") + 1));
        $flight->segments[0]->departure->name = 'ALLENTOWN, PA';
        $flight->segments[0]->departure->airportCode = 'ABE';
        $flight->segments[0]->departure->address = new Schema\Address();
        $flight->segments[0]->departure->address->text = 'some address';
        $flight->segments[0]->arrival = new Schema\TripLocation();
        $flight->segments[0]->arrival->localDateTime = date('Y-m-dTH:i:s', mktime(4, 10, 0, 1, 1, date("Y") + 1));
        $flight->segments[0]->arrival->name = 'DETROIT';
        $flight->segments[0]->arrival->airportCode = 'DTW';
        $flight->segments[0]->arrival->address = new Schema\Address();
        $flight->segments[0]->arrival->address->text = 'some address';
        $flight->segments[0]->seats = ['02A'];
        $flight->segments[0]->marketingCarrier = new Schema\MarketingCarrier();
        $flight->segments[0]->marketingCarrier->airline = new Schema\Airline();
        $flight->segments[0]->marketingCarrier->airline->name = 'DELTA AIR LINES INC';
        $flight->segments[0]->marketingCarrier->flightNumber = '4532';
        $flight->segments[0]->marketingCarrier->confirmationNumber = 'conf_no_0';
        $flight->segments[0]->cabin = 'MAIN';
        $flight->segments[0]->bookingCode = 'Q';
        $flight->segments[1]->departure = new Schema\TripLocation();
        $flight->segments[1]->departure->localDateTime = date('Y-m-dTH:i:s', mktime(6, 10, 0, 1, 1, date("Y") + 1));
        $flight->segments[1]->departure->name = 'DETROIT';
        $flight->segments[1]->departure->airportCode = 'DTW';
        $flight->segments[1]->departure->address = new Schema\Address();
        $flight->segments[1]->departure->address->text = 'some address';
        $flight->segments[1]->arrival = new Schema\TripLocation();
        $flight->segments[1]->arrival->localDateTime = date('Y-m-dTH:i:s', mktime(10, 10, 0, 1, 1, date("Y") + 1));
        $flight->segments[1]->arrival->name = 'SAN FRANCISCO, CA';
        $flight->segments[1]->arrival->airportCode = 'SFO';
        $flight->segments[1]->arrival->address = new Schema\Address();
        $flight->segments[1]->arrival->address->text = 'some address';
        $flight->segments[1]->seats = ['26F'];
        $flight->segments[1]->marketingCarrier = new Schema\MarketingCarrier();
        $flight->segments[1]->marketingCarrier->airline = new Schema\Airline();
        $flight->segments[1]->marketingCarrier->airline->name = 'DELTA AIR LINES INC';
        $flight->segments[1]->marketingCarrier->flightNumber = '745';
        $flight->segments[1]->marketingCarrier->confirmationNumber = 'conf_no_1';
        $flight->segments[1]->cabin = 'MAIN';
        $flight->segments[1]->bookingCode = 'Q';
        $flight->segments[2]->departure = new Schema\TripLocation();
        $flight->segments[2]->departure->localDateTime = date('Y-m-dTH:i:s', mktime(12, 10, 0, 1, 1, date("Y") + 1));
        $flight->segments[2]->departure->name = 'SAN FRANCISCO, CA';
        $flight->segments[2]->departure->airportCode = 'SFO';
        $flight->segments[2]->departure->address = new Schema\Address();
        $flight->segments[2]->departure->address->text = 'some address';
        $flight->segments[2]->arrival = new Schema\TripLocation();
        $flight->segments[2]->arrival->localDateTime = date('Y-m-dTH:i:s', mktime(16, 10, 0, 1, 1, date("Y") + 1));
        $flight->segments[2]->arrival->name = 'DETROIT';
        $flight->segments[2]->arrival->airportCode = 'DTW';
        $flight->segments[2]->arrival->address = new Schema\Address();
        $flight->segments[2]->arrival->address->text = 'some address';
        $flight->segments[2]->seats = ['23A'];
        $flight->segments[2]->marketingCarrier = new Schema\MarketingCarrier();
        $flight->segments[2]->marketingCarrier->airline = new Schema\Airline();
        $flight->segments[2]->marketingCarrier->airline->name = 'DELTA AIR LINES INC';
        $flight->segments[2]->marketingCarrier->flightNumber = '854';
        $flight->segments[2]->marketingCarrier->confirmationNumber = 'conf_no_2';
        $flight->segments[2]->cabin = 'MAIN';
        $flight->segments[2]->bookingCode = 'L';
        $flight->segments[3]->departure = new Schema\TripLocation();
        $flight->segments[3]->departure->localDateTime = date('Y-m-dTH:i:s', mktime(18, 10, 0, 1, 1, date("Y") + 1));
        $flight->segments[3]->departure->name = 'DETROIT';
        $flight->segments[3]->departure->airportCode = 'DTW';
        $flight->segments[3]->departure->address = new Schema\Address();
        $flight->segments[3]->departure->address->text = 'some address';
        $flight->segments[3]->arrival = new Schema\TripLocation();
        $flight->segments[3]->arrival->localDateTime = date('Y-m-dTH:i:s', mktime(23, 10, 0, 1, 1, date("Y") + 1));
        $flight->segments[3]->arrival->name = 'ALLENTOWN, PA';
        $flight->segments[3]->arrival->airportCode = 'ABE';
        $flight->segments[3]->arrival->address = new Schema\Address();
        $flight->segments[3]->arrival->address->text = 'some address';
        $flight->segments[3]->seats = ['04A'];
        $flight->segments[3]->marketingCarrier = new Schema\MarketingCarrier();
        $flight->segments[3]->marketingCarrier->airline = new Schema\Airline();
        $flight->segments[3]->marketingCarrier->airline->name = 'DELTA AIR LINES INC';
        $flight->segments[3]->marketingCarrier->flightNumber = '3537';
        $flight->segments[3]->marketingCarrier->confirmationNumber = 'conf_no_3';
        $flight->segments[3]->cabin = 'MAIN';
        $flight->segments[3]->bookingCode = 'L';

        $itineraryProcessor = $this->container->get(ItinerariesProcessor::class);
        $report = $itineraryProcessor->save(
            [$flight],
            SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", [])
        );
        /** @var Trip $entityFlight */
        $this->assertNotEmpty($report->getAdded());
        $entityFlight = array_values($report->getAdded())[0];
        $this->db->seeInDatabase(
            "TripSegment",
            ["TripID" => $entityFlight->getId(), "DepCode" => "ABE", "ArrCode" => "DTW"]
        );
        $this->db->seeInDatabase(
            "TripSegment",
            ["TripID" => $entityFlight->getId(), "DepCode" => "DTW", "ArrCode" => "SFO"]
        );
        $this->db->seeInDatabase(
            "TripSegment",
            ["TripID" => $entityFlight->getId(), "DepCode" => "SFO", "ArrCode" => "DTW"]
        );
        $this->db->seeInDatabase(
            "TripSegment",
            ["TripID" => $entityFlight->getId(), "DepCode" => "DTW", "ArrCode" => "ABE"]
        );
        $tlManager = $this->container->get(Manager::class);
        $qOptions = QueryOptions::createDesktop()->setUser($this->user);
        $tlData = json_encode($tlManager->query($qOptions), JSON_PRETTY_PRINT);
        self::assertStringContainsString("DTW", $tlData);

        $updatedFlight = new Schema\Flight();
        $updatedFlight->issuingCarrier = new Schema\IssuingCarrier();
        $updatedFlight->issuingCarrier->confirmationNumber = 'H8BUMT';
        $updatedFlight->travelAgency = new Schema\TravelAgency();
        $updatedFlight->travelAgency->confirmationNumbers = [
            new Schema\ConfNo(),
        ];
        $updatedFlight->travelAgency->confirmationNumbers[0]->number = 'ta_number_2';
        $updatedFlight->segments = [
            new Schema\FlightSegment(),
            new Schema\FlightSegment(),
        ];
        $updatedFlight->segments[0]->departure = new Schema\TripLocation();
        $updatedFlight->segments[0]->departure->localDateTime = date(
            'Y-m-dTH:i:s',
            mktime(0, 10, 0, 1, 1, date("Y") + 1)
        );
        $updatedFlight->segments[0]->departure->name = 'San Francisco, California';
        $updatedFlight->segments[0]->departure->airportCode = 'SFO';
        $updatedFlight->segments[0]->departure->address = new Schema\Address();
        $updatedFlight->segments[0]->departure->address->text = 'some address';
        $updatedFlight->segments[0]->arrival = new Schema\TripLocation();
        $updatedFlight->segments[0]->arrival->localDateTime = date(
            'Y-m-dTH:i:s',
            mktime(4, 10, 0, 1, 1, date("Y") + 1)
        );
        $updatedFlight->segments[0]->arrival->name = 'Atlanta, Georgia';
        $updatedFlight->segments[0]->arrival->airportCode = 'ATL';
        $updatedFlight->segments[0]->arrival->address = new Schema\Address();
        $updatedFlight->segments[0]->arrival->address->text = 'some address';
        $updatedFlight->segments[0]->seats = ['45E'];
        $updatedFlight->segments[0]->marketingCarrier = new Schema\MarketingCarrier();
        $updatedFlight->segments[0]->marketingCarrier->airline = new Schema\Airline();
        $updatedFlight->segments[0]->marketingCarrier->airline->name = 'Delta';
        $updatedFlight->segments[0]->marketingCarrier->flightNumber = '2049';
        $updatedFlight->segments[0]->marketingCarrier->confirmationNumber = 'conf_no_0';
        $updatedFlight->segments[0]->cabin = 'Main Cabin';
        $updatedFlight->segments[1]->departure = new Schema\TripLocation();
        $updatedFlight->segments[1]->departure->localDateTime = date(
            'Y-m-dTH:i:s',
            mktime(6, 10, 0, 1, 1, date("Y") + 1)
        );
        $updatedFlight->segments[1]->departure->name = 'Atlanta, Georgia';
        $updatedFlight->segments[1]->departure->airportCode = 'ATL';
        $updatedFlight->segments[1]->departure->address = new Schema\Address();
        $updatedFlight->segments[1]->departure->address->text = 'some address';
        $updatedFlight->segments[1]->arrival = new Schema\TripLocation();
        $updatedFlight->segments[1]->arrival->localDateTime = date(
            'Y-m-dTH:i:s',
            mktime(10, 10, 0, 1, 1, date("Y") + 1)
        );
        $updatedFlight->segments[1]->arrival->name = 'Allentown, Pennsylvania';
        $updatedFlight->segments[1]->arrival->airportCode = 'ABE';
        $updatedFlight->segments[1]->arrival->address = new Schema\Address();
        $updatedFlight->segments[1]->arrival->address->text = 'some address';
        $updatedFlight->segments[1]->seats = ['3D'];
        $updatedFlight->segments[1]->marketingCarrier = new Schema\MarketingCarrier();
        $updatedFlight->segments[1]->marketingCarrier->airline = new Schema\Airline();
        $updatedFlight->segments[1]->marketingCarrier->airline->name = 'Delta';
        $updatedFlight->segments[1]->marketingCarrier->flightNumber = '893';
        $updatedFlight->segments[1]->marketingCarrier->confirmationNumber = 'conf_no_4';

        $report = $itineraryProcessor->save(
            [$updatedFlight],
            SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", [])
        );
        $this->assertNotEmpty($report->getUpdated());
        /** @var Trip $updatedEntityFlight */
        $updatedEntityFlight = array_values($report->getUpdated())[0];
        $this->db->seeInDatabase(
            "TripSegment",
            ["TripID" => $updatedEntityFlight->getId(), "DepCode" => "SFO", "ArrCode" => "ATL"]
        );
        $this->db->seeInDatabase(
            "TripSegment",
            ["TripID" => $updatedEntityFlight->getId(), "DepCode" => "ATL", "ArrCode" => "ABE"]
        );
        $this->assertEquals(
            6,
            $this->db->grabCountFromDatabase("TripSegment", ["TripID" => $updatedEntityFlight->getId()])
        );
        $tlData = json_encode($tlManager->query($qOptions), JSON_PRETTY_PRINT);
        self::assertStringContainsString("ATL", $tlData);
    }

    public function testAmbiguousRecordLocator()
    {
        $itineraryProcessor = $this->container->get(ItinerariesProcessor::class);
        $flight = new Schema\Flight();
        $flight->issuingCarrier = new Schema\IssuingCarrier();
        $flight->segments = [
            new Schema\FlightSegment(),
            new Schema\FlightSegment(),
        ];
        $flight->segments[0]->departure = new Schema\TripLocation();
        $flight->segments[0]->departure->localDateTime = date(
            'Y-m-dTH:i:s',
            mktime(0, 10, 0, 1, 1, date("Y") + 1)
        );
        $flight->segments[0]->departure->name = 'San Francisco, California';
        $flight->segments[0]->departure->airportCode = 'SFO';
        $flight->segments[0]->departure->address = new Schema\Address();
        $flight->segments[0]->departure->address->text = 'some address';
        $flight->segments[0]->arrival = new Schema\TripLocation();
        $flight->segments[0]->arrival->localDateTime = date(
            'Y-m-dTH:i:s',
            mktime(4, 10, 0, 1, 1, date("Y") + 1)
        );
        $flight->segments[0]->arrival->name = 'Atlanta, Georgia';
        $flight->segments[0]->arrival->airportCode = 'ATL';
        $flight->segments[0]->arrival->address = new Schema\Address();
        $flight->segments[0]->arrival->address->text = 'some address';
        $flight->segments[0]->seats = ['45E'];
        $flight->segments[0]->marketingCarrier = new Schema\MarketingCarrier();
        $flight->segments[0]->marketingCarrier->airline = new Schema\Airline();
        $flight->segments[0]->marketingCarrier->airline->name = 'Delta';
        $flight->segments[0]->marketingCarrier->flightNumber = '2049';
        $flight->segments[0]->marketingCarrier->confirmationNumber = 'conf_no_0';
        $flight->segments[0]->cabin = 'Main Cabin';
        $flight->segments[1]->departure = new Schema\TripLocation();
        $flight->segments[1]->departure->localDateTime = date(
            'Y-m-dTH:i:s',
            mktime(6, 10, 0, 1, 1, date("Y") + 1)
        );
        $flight->segments[1]->departure->name = 'Atlanta, Georgia';
        $flight->segments[1]->departure->airportCode = 'ATL';
        $flight->segments[1]->departure->address = new Schema\Address();
        $flight->segments[1]->departure->address->text = 'some address';
        $flight->segments[1]->arrival = new Schema\TripLocation();
        $flight->segments[1]->arrival->localDateTime = date(
            'Y-m-dTH:i:s',
            mktime(10, 10, 0, 1, 1, date("Y") + 1)
        );
        $flight->segments[1]->arrival->name = 'Allentown, Pennsylvania';
        $flight->segments[1]->arrival->airportCode = 'ABE';
        $flight->segments[1]->arrival->address = new Schema\Address();
        $flight->segments[1]->arrival->address->text = 'some address';
        $flight->segments[1]->seats = ['3D'];
        $flight->segments[1]->marketingCarrier = new Schema\MarketingCarrier();
        $flight->segments[1]->marketingCarrier->airline = new Schema\Airline();
        $flight->segments[1]->marketingCarrier->airline->name = 'Delta';
        $flight->segments[1]->marketingCarrier->flightNumber = '893';
        $flight->segments[1]->marketingCarrier->confirmationNumber = 'conf_no_4';

        $itineraryProcessor->save(
            [$flight],
            SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", [])
        );
        $this->db->dontSeeInDatabase('Trip', ['UserID' => $this->user->getUserid()]);
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @dataProvider schemaProvider
     */
    public function testItineraryRestorationOnUpdate(string $serializedItinerary, string $type)
    {
        // Save the itinerary
        $serializer = $this->container->get('jms_serializer');
        $schemaItinerary = $serializer->deserialize($serializedItinerary, $type, 'json');
        $itineraryProcessor = $this->container->get(ItinerariesProcessor::class);
        $options = SavingOptions::savingByEmail(new Owner($this->user), 1, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com'));
        $report = $itineraryProcessor->save([$schemaItinerary], $options);
        $this->assertCount(1, $report->getAdded());

        // Hide it
        /** @var Itinerary $savedItinerary */
        $savedItinerary = array_values($report->getAdded())[0];
        $savedItinerary->setHidden(true);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->flush();

        // Save again and check that it's not hidden anymore
        $report = $itineraryProcessor->save([$schemaItinerary], $options);
        $this->assertCount(1, $report->getUpdated());
        /** @var Itinerary $updatedItinerary */
        $updatedItinerary = array_values($report->getUpdated())[0];
        $this->assertFalse($updatedItinerary->getHidden());
    }

    public function schemaProvider(): array
    {
        return [
            [
                file_get_contents(codecept_data_dir() . '/itineraries/schemaFlight.json'),
                Schema\Flight::class,
            ],
            [
                file_get_contents(codecept_data_dir() . '/itineraries/schemaReservation.json'),
                Schema\HotelReservation::class,
            ],
            [
                file_get_contents(codecept_data_dir() . '/itineraries/schemaRental.json'),
                Schema\CarRental::class,
            ],
            [
                file_get_contents(codecept_data_dir() . '/itineraries/schemaEvent.json'),
                Schema\Event::class,
            ],
        ];
    }
}
