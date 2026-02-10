<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\Airline;
use AwardWallet\Schema\Itineraries\Bus;
use AwardWallet\Schema\Itineraries\BusSegment;
use AwardWallet\Schema\Itineraries\CarRental;
use AwardWallet\Schema\Itineraries\CarRentalLocation;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\CruiseDetails;
use AwardWallet\Schema\Itineraries\CruiseSegment;
use AwardWallet\Schema\Itineraries\Event;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\HotelReservation;
use AwardWallet\Schema\Itineraries\IssuingCarrier;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\OperatingCarrier;
use AwardWallet\Schema\Itineraries\ParsedNumber;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Itineraries\PricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo;
use AwardWallet\Schema\Itineraries\Train;
use AwardWallet\Schema\Itineraries\TrainSegment;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Schema\Itineraries\TravelAgency;
use AwardWallet\Schema\Itineraries\TripLocation;
use AwardWallet\Tests\Modules\DbBuilder\Airline as DBAirline;
use AwardWallet\Tests\Modules\DbBuilder\Provider as DBProvider;
use AwardWallet\Tests\Modules\DbBuilder\Trip as DBTrip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment as DBTripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User as DBUser;
use Codeception\Example;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\Tests\Modules\Utils\ClosureEvaluator\create;

/**
 * @group frontend-functional
 */
class ItinerariesProcessorMatchingCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?Usr $user;

    private ?ItinerariesProcessor $itinerariesProcessor;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $this->itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->itinerariesProcessor = null;
    }

    public function matchReservationsWithSameTimes(\TestSymfonyGuy $I)
    {
        $checkinDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $checkoutDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $resrvation1Id = $I->haveInDatabase("Reservation", [
            'UserID' => $this->user->getId(),
            'ConfirmationNumber' => 'CONF1',
            'HotelName' => 'Hotel1',
            'CheckInDate' => $checkinDate,
            'CheckOutDate' => $checkoutDate,
        ]);

        $resrvation2Id = $I->haveInDatabase("Reservation", [
            'UserID' => $this->user->getId(),
            'ConfirmationNumber' => 'CONF2',
            'HotelName' => 'Hotel2',
            'CheckInDate' => $checkinDate,
            'CheckOutDate' => $checkoutDate,
        ]);

        $reservation = new HotelReservation();
        $reservation->checkInDate = $checkinDate;
        $reservation->checkOutDate = $checkoutDate;
        $reservation->hotelName = 'Marriott';
        $confNo = new ConfNo();
        $confNo->number = 'CONF2';
        $reservation->confirmationNumbers = [$confNo];
        $reservation->address = new Address();
        $reservation->address->text = "Some address";

        $this->itinerariesProcessor->save([$reservation], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals("Marriott", $I->grabFromDatabase("Reservation", "HotelName", ["ReservationID" => $resrvation2Id]));
        $I->assertEquals("Hotel1", $I->grabFromDatabase("Reservation", "HotelName", ["ReservationID" => $resrvation1Id]));
    }

    public function matchReservationsByAgency(\TestSymfonyGuy $I)
    {
        $checkinDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $checkoutDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $I->haveInDatabase("Reservation", [
            'ProviderID' => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]),
            'UserID' => $this->user->getId(),
            'TravelAgencyConfirmationNumbers' => 'CONF1',
            'HotelName' => 'Hotel1',
            'CheckInDate' => date("Y-m-d 14:00", strtotime($checkinDate)),
            'CheckOutDate' => $checkoutDate,
        ]);

        $reservation = new HotelReservation();
        $reservation->checkInDate = $checkinDate;
        $reservation->checkOutDate = $checkoutDate;
        $reservation->hotelName = 'NewHotelName';
        $confNo = new ConfNo();
        $confNo->number = 'CONF-1';
        $reservation->travelAgency = new TravelAgency();
        $reservation->travelAgency->confirmationNumbers = [$confNo];
        $reservation->travelAgency->providerInfo = new ProviderInfo();
        $reservation->travelAgency->providerInfo->code = 'testprovider';
        $reservation->travelAgency->providerInfo->name = 'Test Provider';
        $reservation->address = new Address();
        $reservation->address->text = "Some address";
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $account = $I->getContainer()->get("doctrine.orm.entity_manager")->find(Account::class, $accountId);
        $this->itinerariesProcessor->save([$reservation], SavingOptions::savingByAccount($account, true));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Reservation", ["UserID" => $this->user->getId()]));
        $I->assertEquals("NewHotelName", $I->grabFromDatabase("Reservation", "HotelName", ["UserID" => $this->user->getId()]));
        $I->assertEquals($accountId, $I->grabFromDatabase("Reservation", "AccountID", ["UserID" => $this->user->getId()]));
    }

    public function matchReservationsByAnyNumber(\TestSymfonyGuy $I)
    {
        $checkinDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $checkoutDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $I->haveInDatabase("Reservation", [
            'ProviderID' => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]),
            'UserID' => $this->user->getId(),
            'ConfirmationNumber' => 'CONF1',
            'HotelName' => 'Hotel1',
            'CheckInDate' => date("Y-m-d 14:00", strtotime($checkinDate)),
            'CheckOutDate' => $checkoutDate,
        ]);

        $reservation = new HotelReservation();
        $reservation->checkInDate = $checkinDate;
        $reservation->checkOutDate = $checkoutDate;
        $reservation->hotelName = 'NewHotelName';
        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $reservation->travelAgency = new TravelAgency();
        $reservation->travelAgency->confirmationNumbers = [$confNo];
        $reservation->travelAgency->providerInfo = new ProviderInfo();
        $reservation->travelAgency->providerInfo->code = 'testprovider';
        $reservation->travelAgency->providerInfo->name = 'Test Provider';
        $reservation->address = new Address();
        $reservation->address->text = "Some address";
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $account = $I->getContainer()->get("doctrine.orm.entity_manager")->find(Account::class, $accountId);
        $this->itinerariesProcessor->save([$reservation], SavingOptions::savingByAccount($account, true));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Reservation", ["UserID" => $this->user->getId()]));
        $I->assertEquals("NewHotelName", $I->grabFromDatabase("Reservation", "HotelName", ["UserID" => $this->user->getId()]));
        $I->assertEquals($accountId, $I->grabFromDatabase("Reservation", "AccountID", ["UserID" => $this->user->getId()]));
    }

    public function matchRentalsWithSameTimes(\TestSymfonyGuy $I)
    {
        $pickupDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $dropoffDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $rental1Id = $I->haveInDatabase("Rental", [
            'UserID' => $this->user->getId(),
            'Number' => 'CONF1',
            'PickupLocation' => 'Location1',
            'DropoffLocation' => 'Location1',
            'PickupDatetime' => $pickupDate,
            'DropoffDatetime' => $dropoffDate,
            'PickupPhone' => 'Phone1',
        ]);

        $rental2Id = $I->haveInDatabase("Rental", [
            'UserID' => $this->user->getId(),
            'Number' => 'CONF2',
            'PickupLocation' => 'Location1',
            'DropoffLocation' => 'Location1',
            'PickupDatetime' => $pickupDate,
            'DropoffDatetime' => $dropoffDate,
            'PickupPhone' => 'Phone2',
        ]);

        $rental = new CarRental();
        $rental->pickup = new CarRentalLocation();
        $rental->pickup->localDateTime = $pickupDate;
        $rental->pickup->address = new Address();
        $rental->pickup->address->text = "Location1";
        $rental->dropoff = clone $rental->pickup;
        $rental->dropoff->localDateTime = $dropoffDate;
        $confNo = new ConfNo();
        $confNo->number = 'CONF2';
        $rental->confirmationNumbers = [$confNo];
        $rental->pickup->phone = 'newPhone';

        $this->itinerariesProcessor->save([$rental], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals("newPhone", $I->grabFromDatabase("Rental", "PickupPhone", ["RentalID" => $rental2Id]));
        $I->assertEquals("Phone1", $I->grabFromDatabase("Rental", "PickupPhone", ["RentalID" => $rental1Id]));
    }

    public function matchRentalByAgency(\TestSymfonyGuy $I)
    {
        $pickupDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $dropoffDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $I->haveInDatabase("Rental", [
            'UserID' => $this->user->getId(),
            'TravelAgencyConfirmationNumbers' => 'CONF-1',
            'PickupLocation' => 'OldPick[Location',
            'DropoffLocation' => 'OldDropLocation',
            'PickupDatetime' => date("Y-m-d 11:00", strtotime($pickupDate)),
            'DropoffDatetime' => $dropoffDate,
            'PickupPhone' => 'OldPhone',
        ]);

        $rental = new CarRental();
        $rental->pickup = new CarRentalLocation();
        $rental->pickup->localDateTime = $pickupDate;
        $rental->pickup->address = new Address();
        $rental->pickup->address->text = "NewLocation";
        $rental->dropoff = clone $rental->pickup;
        $rental->dropoff->localDateTime = $dropoffDate;
        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $rental->travelAgency = new TravelAgency();
        $rental->travelAgency->confirmationNumbers = [$confNo];
        $rental->travelAgency->providerInfo = new ProviderInfo();
        $rental->travelAgency->providerInfo->code = 'testprovider';
        $rental->travelAgency->providerInfo->name = 'Test Provider';
        $rental->pickup->phone = 'NewPhone';

        $this->itinerariesProcessor->save([$rental], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Rental", ["UserID" => $this->user->getId()]));
        $I->assertEquals("NewPhone", $I->grabFromDatabase("Rental", "PickupPhone", ["UserID" => $this->user->getId()]));
    }

    public function matchRentalByAnyNumber(\TestSymfonyGuy $I)
    {
        $pickupDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $dropoffDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $I->haveInDatabase("Rental", [
            'UserID' => $this->user->getId(),
            'Number' => 'CONF1',
            'PickupLocation' => 'OldPick[Location',
            'DropoffLocation' => 'OldDropLocation',
            'PickupDatetime' => date("Y-m-d 11:00", strtotime($pickupDate)),
            'DropoffDatetime' => $dropoffDate,
            'PickupPhone' => 'OldPhone',
        ]);

        $rental = new CarRental();
        $rental->pickup = new CarRentalLocation();
        $rental->pickup->localDateTime = $pickupDate;
        $rental->pickup->address = new Address();
        $rental->pickup->address->text = "NewLocation";
        $rental->dropoff = clone $rental->pickup;
        $rental->dropoff->localDateTime = $dropoffDate;
        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $rental->travelAgency = new TravelAgency();
        $rental->travelAgency->confirmationNumbers = [$confNo];
        $rental->travelAgency->providerInfo = new ProviderInfo();
        $rental->travelAgency->providerInfo->code = 'testprovider';
        $rental->travelAgency->providerInfo->name = 'Test Provider';
        $rental->pickup->phone = 'NewPhone';

        $this->itinerariesProcessor->save([$rental], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Rental", ["UserID" => $this->user->getId()]));
        $I->assertEquals("NewPhone", $I->grabFromDatabase("Rental", "PickupPhone", ["UserID" => $this->user->getId()]));
    }

    public function matchRestaurantByAgency(\TestSymfonyGuy $I)
    {
        $startDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $endDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $I->haveInDatabase("Restaurant", [
            'UserID' => $this->user->getId(),
            'TravelAgencyConfirmationNumbers' => 'CONF-1',
            'Name' => 'OldName',
            'Address' => 'OldAddress',
            'StartDate' => date("Y-m-d 11:00", strtotime($startDate)),
            'EndDate' => $endDate,
            'Phone' => 'OldPhone',
        ]);

        $restaurant = new Event();
        $restaurant->address = new Address();
        // Set address text, ignore all other fields
        $restaurant->address->text = '999 9th St NW, Washington, DC 20001, USA';
        $restaurant->eventName = 'new event name';
        $restaurant->eventType = 4;
        $restaurant->startDateTime = $startDate;
        $restaurant->endDateTime = $endDate;
        $restaurant->phone = 'NewPhone';
        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $restaurant->travelAgency = new TravelAgency();
        $restaurant->travelAgency->confirmationNumbers = [$confNo];
        $restaurant->travelAgency->providerInfo = new ProviderInfo();
        $restaurant->travelAgency->providerInfo->code = 'testprovider';
        $restaurant->travelAgency->providerInfo->name = 'Test Provider';

        $this->itinerariesProcessor->save([$restaurant], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Restaurant", ["UserID" => $this->user->getId()]));
        $I->assertEquals("NewPhone", $I->grabFromDatabase("Restaurant", "Phone", ["UserID" => $this->user->getId()]));
    }

    public function matchRestaurantByAnyNumber(\TestSymfonyGuy $I)
    {
        $startDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $endDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $I->haveInDatabase("Restaurant", [
            'UserID' => $this->user->getId(),
            'ConfNo' => 'CONF1',
            'Name' => 'OldName',
            'Address' => 'OldAddress',
            'StartDate' => date("Y-m-d 11:00", strtotime($startDate)),
            'EndDate' => $endDate,
            'Phone' => 'OldPhone',
        ]);

        $restaurant = new Event();
        $restaurant->address = new Address();
        // Set address text, ignore all other fields
        $restaurant->address->text = '999 9th St NW, Washington, DC 20001, USA';
        $restaurant->eventName = 'new event name';
        $restaurant->eventType = 4;
        $restaurant->startDateTime = $startDate;
        $restaurant->endDateTime = $endDate;
        $restaurant->phone = 'NewPhone';
        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $restaurant->travelAgency = new TravelAgency();
        $restaurant->travelAgency->confirmationNumbers = [$confNo];
        $restaurant->travelAgency->providerInfo = new ProviderInfo();
        $restaurant->travelAgency->providerInfo->code = 'testprovider';
        $restaurant->travelAgency->providerInfo->name = 'Test Provider';

        $this->itinerariesProcessor->save([$restaurant], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Restaurant", ["UserID" => $this->user->getId()]));
        $I->assertEquals("NewPhone", $I->grabFromDatabase("Restaurant", "Phone", ["UserID" => $this->user->getId()]));
    }

    public function matchTripByAgency(\TestSymfonyGuy $I)
    {
        $depDate = date("Y-m-d 14:00:00", strtotime("tomorrow"));
        $arrDate = date("Y-m-d 18:00:00", strtotime("tomorrow"));

        $trip1Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'TravelAgencyConfirmationNumbers' => 'CONF-1',
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]),
        ]);
        $trip1Segment1Id = $I->createTripSegment([
            "TripID" => $trip1Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 11:00", strtotime($depDate)),
            "ScheduledDepDate" => date("Y-m-d 11:00", strtotime($depDate)),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
        ]);

        $flight = new Flight();
        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->airline = new Airline();
        $segment->marketingCarrier->airline->name = "American Airlines";
        $segment->marketingCarrier->airline->iata = "AA";
        $segment->marketingCarrier->airline->icao = "AAL";

        $departure = new TripLocation();
        $departure->airportCode = "JFK";
        $departure->name = "JFK Airport";
        $departure->address = new Address();
        $departure->address->text = "JFK Airport";
        $departure->localDateTime = $depDate;
        $segment->departure = $departure;

        $arrival = new TripLocation();
        $arrival->airportCode = "LAX";
        $arrival->name = "LAX Airport";
        $arrival->address = new Address();
        $arrival->address->text = "LAX Airport";
        $arrival->localDateTime = $arrDate;
        $segment->arrival = $arrival;
        $flight->segments = [
            $segment,
        ];
        $flight->pricingInfo = new PricingInfo();
        $flight->pricingInfo->total = 100;
        $flight->travelAgency = new TravelAgency();
        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $taProviderId = $I->createAwProvider();
        $flight->travelAgency->confirmationNumbers = [$confNo];
        $flight->travelAgency->providerInfo = new ProviderInfo();
        $flight->travelAgency->providerInfo->code = $I->grabFromDatabase("Provider", "Code", ["ProviderID" => $taProviderId]);
        $flight->travelAgency->providerInfo->name = $I->grabFromDatabase("Provider", "Name", ["ProviderID" => $taProviderId]);

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Trip", ["UserID" => $this->user->getId()]));
        $I->assertEquals($depDate, $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $trip1Segment1Id]));
    }

    /**
     * @dataProvider matchTripByDatesDataProvider
     */
    public function matchTripByDates(\TestSymfonyGuy $I, Example $example)
    {
        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.entity_manager');
        /** @var Flight $schema */
        $flight = $example['schema'];
        /** @var DBTrip $trip */
        $trip = $example['trip'];
        $I->makeTrip($trip);
        $user = $em->getRepository(Usr::class)->find($userId = $trip->getUser()->getId());

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($user, null), 123, $this->getParsedEmailSource()));
        $em->flush();

        $I->assertEquals($example['expectedMatch'] ? 1 : 2, $I->grabCountFromDatabase('Trip', ['UserID' => $userId]));
    }

    /**
     * @dataProvider notMatchSameMarketingProviderAndDifferentConfNoDataProvider
     */
    public function notMatchSameMarketingProviderAndDifferentConfNo(\TestSymfonyGuy $I, Example $example)
    {
        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.entity_manager');
        /** @var Flight $schema */
        $flight = $example['schema'];
        /** @var DBTrip $trip */
        $trip = $example['trip'];
        $I->makeTrip($trip);
        $user = $em->getRepository(Usr::class)->find($userId = $trip->getUser()->getId());

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($user, null), 123, $this->getParsedEmailSource()));
        $em->flush();

        $I->assertEquals($example['expectedMatch'] ? 1 : 2, $I->grabCountFromDatabase('Trip', ['UserID' => $userId]));
    }

    public function matchTripIssuingByAgency(\TestSymfonyGuy $I)
    {
        $depDate = date("Y-m-d 14:00:00", strtotime("tomorrow"));
        $arrDate = date("Y-m-d 18:00:00", strtotime("tomorrow"));

        $trip1Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'IssuingAirlineConfirmationNumber' => 'CONF1',
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]),
        ]);
        $trip1Segment1Id = $I->createTripSegment([
            "TripID" => $trip1Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 11:00", strtotime($depDate)),
            "ScheduledDepDate" => date("Y-m-d 11:00", strtotime($depDate)),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "MarketingAirlineConfirmationNumber" => "CONF1",
        ]);

        $flight = new Flight();
        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->airline = new Airline();
        $segment->marketingCarrier->airline->name = "American Airlines";
        $segment->marketingCarrier->airline->iata = "AA";
        $segment->marketingCarrier->airline->icao = "AAL";

        $departure = new TripLocation();
        $departure->airportCode = "JFK";
        $departure->name = "JFK Airport";
        $departure->address = new Address();
        $departure->address->text = "JFK Airport";
        $departure->localDateTime = $depDate;
        $segment->departure = $departure;

        $arrival = new TripLocation();
        $arrival->airportCode = "LAX";
        $arrival->name = "LAX Airport";
        $arrival->address = new Address();
        $arrival->address->text = "LAX Airport";
        $arrival->localDateTime = $arrDate;
        $segment->arrival = $arrival;
        $flight->segments = [
            $segment,
        ];
        $flight->pricingInfo = new PricingInfo();
        $flight->pricingInfo->total = 100;
        $flight->travelAgency = new TravelAgency();
        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $flight->travelAgency->confirmationNumbers = [$confNo];
        $flight->travelAgency->providerInfo = new ProviderInfo();
        $taProviderId = $I->createAwProvider();
        $flight->travelAgency->providerInfo->code = $I->grabFromDatabase("Provider", "Code", ["ProviderID" => $taProviderId]);
        $flight->travelAgency->providerInfo->name = $I->grabFromDatabase("Provider", "Name", ["ProviderID" => $taProviderId]);

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Trip", ["UserID" => $this->user->getId()]));
        $I->assertEquals($depDate, $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $trip1Segment1Id]));
    }

    public function noConfNumbers(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $account = $I->getContainer()->get("doctrine.orm.entity_manager")->find(Account::class, $accountId);

        $depDate = date("Y-m-d 14:00", strtotime("tomorrow"));
        $arrDate = date("Y-m-d 18:00", strtotime("tomorrow"));

        $trip1Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'AccountID' => $accountId,
            'RecordLocator' => 'REC001',
        ]);
        $trip1Segment1Id = $I->createTripSegment([
            "TripID" => $trip1Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 15:00", strtotime("+10 day")),
            "ScheduledDepDate" => date("Y-m-d 15:00", strtotime("+10 day")),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "MarketingAirlineConfirmationNumber" => "REC001",
        ]);

        $trip2Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'RecordLocator' => 'REC002',
        ]);
        $trip2Segment1Id = $I->createTripSegment([
            "TripID" => $trip2Id,
            "DepCode" => "SEA",
            "DepName" => "SEA",
            "ArrCode" => "SFO",
            "ArrName" => "SFO",
            "DepDate" => date("Y-m-d 15:00", strtotime("+5 day")),
            "ScheduledDepDate" => date("Y-m-d 15:00", strtotime("+5 day")),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+5 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+5 day")),
            "MarketingAirlineConfirmationNumber" => "REC002",
        ]);

        $flight = new Flight();
        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->airline = new Airline();
        $segment->marketingCarrier->airline->name = "American Airlines";
        $segment->marketingCarrier->airline->iata = "AA";
        $segment->marketingCarrier->airline->icao = "AAL";

        $departure = new TripLocation();
        $departure->airportCode = "JFK";
        $departure->name = "JFK Airport";
        $departure->address = new Address();
        $departure->address->text = "JFK Airport";
        $departure->localDateTime = $depDate;
        $segment->departure = $departure;

        $arrival = new TripLocation();
        $arrival->airportCode = "LAX";
        $arrival->name = "LAX Airport";
        $arrival->address = new Address();
        $arrival->address->text = "LAX Airport";
        $arrival->localDateTime = $arrDate;
        $segment->arrival = $arrival;
        $flight->segments = [
            $segment,
        ];
        $flight->pricingInfo = new PricingInfo();
        $flight->pricingInfo->total = 100;

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByAccount($account, false));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripSegmentID" => $trip1Segment1Id]));
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripSegmentID" => $trip2Segment1Id]));
        $I->assertEmpty($I->grabFromDatabase("Trip", "Total", ["TripID" => $trip1Id]));
        $I->assertEmpty($I->grabFromDatabase("Trip", "Total", ["TripID" => $trip2Id]));
    }

    public function hungryCancelledTrip(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $account = $I->getContainer()->get("doctrine.orm.entity_manager")->find(Account::class, $accountId);

        $trip1Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'AccountID' => $accountId,
            'RecordLocator' => 'REC001',
        ]);
        $trip1Segment1Id = $I->createTripSegment([
            "TripID" => $trip1Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 15:00", strtotime("+10 day")),
            "ScheduledDepDate" => date("Y-m-d 15:00", strtotime("+10 day")),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "MarketingAirlineConfirmationNumber" => "REC001",
        ]);

        $trip2Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'RecordLocator' => 'REC002',
        ]);
        $trip2Segment1Id = $I->createTripSegment([
            "TripID" => $trip2Id,
            "DepCode" => "SEA",
            "DepName" => "SEA",
            "ArrCode" => "SFO",
            "ArrName" => "SFO",
            "DepDate" => date("Y-m-d 15:00", strtotime("+5 day")),
            "ScheduledDepDate" => date("Y-m-d 15:00", strtotime("+5 day")),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+5 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+5 day")),
            "MarketingAirlineConfirmationNumber" => "REC002",
        ]);

        $rental1Id = $I->haveInDatabase("Rental", [
            'UserID' => $this->user->getId(),
            'Number' => 'CONF1',
            'PickupLocation' => 'Location1',
            'DropoffLocation' => 'Location1',
            'PickupDatetime' => date("Y-m-d 15:00", strtotime("+5 day")),
            'DropoffDatetime' => date("Y-m-d 15:00", strtotime("+8 day")),
            'PickupPhone' => 'Phone1',
        ]);

        $flight = new Flight();
        $flight->travelAgency = new TravelAgency();
        $confNo = new ConfNo();
        $confNo->number = 'REC001';
        $flight->travelAgency->confirmationNumbers = [$confNo];
        $flight->cancelled = true;
        $flight->issuingCarrier = new IssuingCarrier();
        $flight->issuingCarrier->confirmationNumber = 'REC001';

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByAccount($account, false));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(0, $I->grabFromDatabase("Rental", "Hidden", ["RentalID" => $rental1Id]));
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripSegmentID" => $trip2Segment1Id]));
        $I->assertEquals(1, $I->grabFromDatabase("TripSegment", "Hidden", ["TripSegmentID" => $trip1Segment1Id]));
        $I->assertEquals(1, $I->grabFromDatabase("Trip", "Hidden", ["TripID" => $trip1Id]));
    }

    public function hungryCancelledRental(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $account = $I->getContainer()->get("doctrine.orm.entity_manager")->find(Account::class, $accountId);

        $trip1Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'AccountID' => $accountId,
            'RecordLocator' => 'REC001',
        ]);
        $trip1Segment1Id = $I->createTripSegment([
            "TripID" => $trip1Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 15:00", strtotime("+10 day")),
            "ScheduledDepDate" => date("Y-m-d 15:00", strtotime("+10 day")),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "MarketingAirlineConfirmationNumber" => "REC001",
        ]);

        $rental1Id = $I->haveInDatabase("Rental", [
            'UserID' => $this->user->getId(),
            'AccountID' => $account->getId(),
            'Number' => 'REC001',
            'PickupLocation' => 'Location1',
            'DropoffLocation' => 'Location1',
            'PickupDatetime' => date("Y-m-d 15:00", strtotime("+5 day")),
            'DropoffDatetime' => date("Y-m-d 15:00", strtotime("+8 day")),
            'PickupPhone' => 'Phone1',
        ]);

        $rental2Id = $I->haveInDatabase("Rental", [
            'UserID' => $this->user->getId(),
            'AccountID' => $account->getId(),
            'Number' => 'REC002',
            'PickupLocation' => 'Location1',
            'DropoffLocation' => 'Location1',
            'PickupDatetime' => date("Y-m-d 15:00", strtotime("+10 day")),
            'DropoffDatetime' => date("Y-m-d 15:00", strtotime("+12 day")),
            'PickupPhone' => 'Phone1',
        ]);

        $cancelled = new CarRental();
        $confNo = new ConfNo();
        $confNo->number = 'REC001';
        $cancelled->confirmationNumbers = [$confNo];
        $cancelled->cancelled = true;

        $this->itinerariesProcessor->save([$cancelled], SavingOptions::savingByAccount($account, false));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabFromDatabase("Rental", "Hidden", ["RentalID" => $rental1Id]));
        $I->assertEquals(0, $I->grabFromDatabase("Rental", "Hidden", ["RentalID" => $rental2Id]));
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripSegmentID" => $trip1Segment1Id]));
    }

    public function duplicateTripsMatch(\TestSymfonyGuy $I)
    {
        $depDate = strtotime("+1 day 15:00");
        $arrDate = strtotime("+3 hour", $depDate);
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "1234567");
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($accountId);
        $testProviderId = $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]);
        $userAgentId = $I->createFamilyMember($this->user->getId(), 'Jessica', 'Smith');

        $trip1Id = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getId(),
            'AccountID' => $accountId,
            'RecordLocator' => 'RECLOC1',
            'ProviderID' => $testProviderId,
            'Category' => 1,
            'Copied' => 1,
            'ConfirmationNumbers' => 'RECLOC1',
            'ParsedAccountNumbers' => '1234567,1234568',
            'TravelerNames' => 'John Smith,Jessica Smith',
            'IssuingAirlineConfirmationNumber' => 'RECLOC1',
        ]);

        $trip2Id = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getId(),
            'AccountID' => $accountId,
            'ProviderID' => $testProviderId,
            'Category' => 1,
            'TravelerNames' => 'Mr John Smith,Mrs Jessica Smith',
            'UserAgentID' => $userAgentId,
        ]);

        $createSegments = function (int $tripId) use ($depDate, $arrDate, $I) {
            $I->createTripSegment([
                'TripID' => $tripId,
                'DepCode' => 'CPH',
                'DepName' => 'Copenhagen Airport',
                'DepDate' => date("Y-m-d H:i:s", $depDate),
                'ScheduledDepDate' => date("Y-m-d H:i:s", $depDate),
                'ArrCode' => 'LHR',
                'ArrName' => 'London Heathrow Airport',
                'ArrDate' => date("Y-m-d H:i:s", $arrDate),
                'ScheduledArrDate' => date("Y-m-d H:i:s", $arrDate),
                'AirlineName' => 'British Airways',
                'FlightNumber' => '0815',
                'MarketingAirlineConfirmationNumber' => 'RECLOC1',
            ]);
        };

        $createSegments($trip1Id);
        $createSegments($trip2Id);

        $departure = new TripLocation();
        $departure->airportCode = "CPH";
        $departure->name = "Copenhagen Airport";
        $departure->address = new Address();
        $departure->address->text = "CPH";
        $departure->localDateTime = date("c", $depDate);

        $arrival = new TripLocation();
        $arrival->airportCode = "LHR";
        $arrival->name = "London Heathrow Airport";
        $arrival->address = new Address();
        $arrival->address->text = "LHR";
        $arrival->localDateTime = date("c", $arrDate);

        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->confirmationNumber = 'RECLOC1';
        $segment->marketingCarrier->flightNumber = '0815';
        $segment->marketingCarrier->airline = new Airline();
        $segment->marketingCarrier->airline->name = "British Airways";
        $segment->marketingCarrier->airline->iata = "BA";
        $segment->marketingCarrier->airline->icao = "BAQ";
        $segment->operatingCarrier = new OperatingCarrier();
        $segment->operatingCarrier->airline = new Airline();
        $segment->operatingCarrier->airline->name = "British Airways";
        $segment->operatingCarrier->airline->iata = "BA";
        $segment->operatingCarrier->airline->icao = "BAQ";
        $segment->departure = $departure;
        $segment->arrival = $arrival;

        $flight = new Flight();
        $flight->segments = [
            $segment,
        ];

        $flight->pricingInfo = new PricingInfo();
        $flight->pricingInfo->total = 100;

        $flight->providerInfo = new ProviderInfo();
        $flight->providerInfo->code = 'testprovider';
        $flight->providerInfo->name = 'Test Provider';
        $num1 = new ParsedNumber();
        $num1->number = '1234567';
        $num1->masked = false;
        $num2 = new ParsedNumber();
        $num2->number = '1234568';
        $num2->masked = false;
        $flight->providerInfo->accountNumbers = [$num1, $num2];

        $person1 = new Person();
        $person1->name = 'Mr John Smith';
        $person2 = new Person();
        $person2->name = 'Mrs Jessica Smith';
        $flight->travelers = [$person1, $person2];

        $flight->issuingCarrier = new IssuingCarrier();
        $flight->issuingCarrier->confirmationNumber = 'RECLOC1';
        $flight->issuingCarrier->airline = new Airline();
        $flight->issuingCarrier->airline->name = "British Airways";
        $flight->issuingCarrier->airline->iata = "BA";
        $flight->issuingCarrier->airline->icao = "BAQ";

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByAccount($account, false));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $trip1Id]));
        $I->assertEquals(1, $I->grabFromDatabase("Trip", "Parsed", ["TripID" => $trip1Id]));
        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $trip2Id]));
        $I->assertEquals(1, $I->grabFromDatabase("Trip", "Parsed", ["TripID" => $trip2Id]));
    }

    public function removeDuplicateTrip(\TestSymfonyGuy $I)
    {
        $depDate = strtotime("+1 day 15:00");
        $arrDate = strtotime("+3 hour", $depDate);
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "1234567");
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($accountId);
        $testProviderId = $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]);

        $trip1Id = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getId(),
            'AccountID' => $accountId,
            'RecordLocator' => 'RECLOC1',
            'ProviderID' => $testProviderId,
            'Category' => 1,
            'Copied' => 1,
            'ConfirmationNumbers' => 'RECLOC1',
            'ParsedAccountNumbers' => '1234567,1234568',
            'TravelerNames' => 'Mrs Johns,Mr Johns',
            'IssuingAirlineConfirmationNumber' => 'RECLOC1',
            'CreateDate' => '2013-01-01',
        ]);

        $trip2Id = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getId(),
            'AccountID' => $accountId,
            'ProviderID' => $testProviderId,
            'Category' => 1,
            'TravelerNames' => 'Mrs Johns,Mr Johns',
            'CreateDate' => '2012-01-01',
        ]);

        $createSegments = function (int $tripId) use ($depDate, $arrDate, $I) {
            $I->createTripSegment([
                'TripID' => $tripId,
                'DepCode' => 'CPH',
                'DepName' => 'Copenhagen Airport',
                'DepDate' => date("Y-m-d H:i:s", $depDate),
                'ScheduledDepDate' => date("Y-m-d H:i:s", $depDate),
                'ArrCode' => 'LHR',
                'ArrName' => 'London Heathrow Airport',
                'ArrDate' => date("Y-m-d H:i:s", $arrDate),
                'ScheduledArrDate' => date("Y-m-d H:i:s", $arrDate),
                'AirlineName' => 'British Airways',
                'FlightNumber' => '0815',
                'MarketingAirlineConfirmationNumber' => 'RECLOC1',
            ]);
        };

        $createSegments($trip1Id);
        $createSegments($trip2Id);

        $departure = new TripLocation();
        $departure->airportCode = "CPH";
        $departure->name = "Copenhagen Airport";
        $departure->address = new Address();
        $departure->address->text = "CPH";
        $departure->localDateTime = date("c", $depDate);

        $arrival = new TripLocation();
        $arrival->airportCode = "LHR";
        $arrival->name = "London Heathrow Airport";
        $arrival->address = new Address();
        $arrival->address->text = "LHR";
        $arrival->localDateTime = date("c", $arrDate);

        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->confirmationNumber = 'RECLOC1';
        $segment->marketingCarrier->flightNumber = '0815';
        $segment->marketingCarrier->airline = new Airline();
        $segment->marketingCarrier->airline->name = "British Airways";
        $segment->marketingCarrier->airline->iata = "BA";
        $segment->marketingCarrier->airline->icao = "BAQ";
        $segment->operatingCarrier = new OperatingCarrier();
        $segment->operatingCarrier->airline = new Airline();
        $segment->operatingCarrier->airline->name = "British Airways";
        $segment->operatingCarrier->airline->iata = "BA";
        $segment->operatingCarrier->airline->icao = "BAQ";
        $segment->departure = $departure;
        $segment->arrival = $arrival;

        $flight = new Flight();
        $flight->segments = [
            $segment,
        ];

        $flight->pricingInfo = new PricingInfo();
        $flight->pricingInfo->total = 100;

        $flight->providerInfo = new ProviderInfo();
        $flight->providerInfo->code = 'testprovider';
        $flight->providerInfo->name = 'Test Provider';
        $num1 = new ParsedNumber();
        $num1->number = '1234567';
        $num1->masked = false;
        $num2 = new ParsedNumber();
        $num2->number = '1234568';
        $num2->masked = false;
        $flight->providerInfo->accountNumbers = [$num1, $num2];

        $person1 = new Person();
        $person1->name = 'Mrs Johns';
        $person2 = new Person();
        $person2->name = 'Mr Johns';
        $flight->travelers = [$person1, $person2];

        $flight->issuingCarrier = new IssuingCarrier();
        $flight->issuingCarrier->confirmationNumber = 'RECLOC1';
        $flight->issuingCarrier->airline = new Airline();
        $flight->issuingCarrier->airline->name = "British Airways";
        $flight->issuingCarrier->airline->iata = "BA";
        $flight->issuingCarrier->airline->icao = "BAQ";

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByAccount($account, false));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $trip1Id]));
        $I->assertEquals(1, $I->grabFromDatabase("Trip", "Parsed", ["TripID" => $trip1Id]));
        $I->dontSeeInDatabase("Trip", ["TripID" => $trip2Id]);
    }

    public function segmentsWithSameDestinations(\TestSymfonyGuy $I)
    {
        $baseDate = strtotime("+1 day 15:00");
        $testProviderId = $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]);

        $tripId = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getId(),
            'RecordLocator' => 'RECLOC1',
            'ProviderID' => $testProviderId,
            'Category' => Trip::CATEGORY_CRUISE,
            'ConfirmationNumbers' => 'RECLOC1',
        ]);

        $createSegment = function (int $tripId, $depName, $arrName, $depDate, $arrDate) use ($I) {
            return $I->createTripSegment([
                'TripID' => $tripId,
                'DepName' => $depName,
                'DepDate' => date("Y-m-d H:i:s", $depDate),
                'ScheduledDepDate' => date("Y-m-d H:i:s", $depDate),
                'ArrName' => $arrName,
                'ArrDate' => date("Y-m-d H:i:s", $arrDate),
                'ScheduledArrDate' => date("Y-m-d H:i:s", $arrDate),
            ]);
        };

        $segment1Id = $createSegment($tripId, "New York", "London", $baseDate, $baseDate + 3600);
        $segment2Id = $createSegment($tripId, "London", "New York", $baseDate + 3600 * 2, $baseDate + 3600 * 3);
        $segment3Id = $createSegment($tripId, "New York", "London", $baseDate + 3600 * 4, $baseDate + 3600 * 5);

        $cruise = new Cruise();
        $cruise->segments = [];
        $cruise->providerInfo = new ProviderInfo();
        $cruise->providerInfo->code = 'testprovider';
        $cruise->providerInfo->name = 'Test Provider';
        $cruise->cruiseDetails = new CruiseDetails();
        $confNo = new ConfNo();
        $confNo->number = 'RECLOC1';
        $cruise->confirmationNumbers = [$confNo];

        // segment 1
        $departure = new TransportLocation();
        $departure->name = "New York";
        $departure->address = new Address();
        $departure->address->text = "New York";
        $departure->localDateTime = date("c", $baseDate);

        $arrival = new TransportLocation();
        $arrival->name = "London";
        $arrival->address = new Address();
        $arrival->address->text = "London";
        $arrival->localDateTime = date("c", $baseDate + 3600);

        $segment = new CruiseSegment();
        $segment->departure = $departure;
        $segment->arrival = $arrival;
        $cruise->segments[] = $segment;

        // segment 2
        $departure = new TransportLocation();
        $departure->name = "London";
        $departure->address = new Address();
        $departure->address->text = "London";
        $departure->localDateTime = date("c", $baseDate + 3600 * 2);

        $arrival = new TransportLocation();
        $arrival->name = "New York";
        $arrival->address = new Address();
        $arrival->address->text = "New York";
        $arrival->localDateTime = date("c", $baseDate + 3600 * 3);

        $segment = new CruiseSegment();
        $segment->departure = $departure;
        $segment->arrival = $arrival;
        $cruise->segments[] = $segment;

        // segment 3
        $departure = new TransportLocation();
        $departure->name = "New York";
        $departure->address = new Address();
        $departure->address->text = "New York";
        $departure->localDateTime = date("c", $baseDate + 3600 * 4);

        $arrival = new TransportLocation();
        $arrival->name = "London";
        $arrival->address = new Address();
        $arrival->address->text = "London";
        $arrival->localDateTime = date("c", $baseDate + 3600 * 5);

        $segment = new CruiseSegment();
        $segment->departure = $departure;
        $segment->arrival = $arrival;
        $cruise->segments[] = $segment;

        // check
        $this->itinerariesProcessor->save([$cruise], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabFromDatabase("Trip", "Parsed", ["TripID" => $tripId]));
        $I->assertEquals(
            date("Y-m-d H:i:s", $baseDate),
            $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $segment1Id])
        );
        $I->assertEquals(
            date("Y-m-d H:i:s", $baseDate + 3600),
            $I->grabFromDatabase("TripSegment", "ArrDate", ["TripSegmentID" => $segment1Id])
        );
        $I->assertEquals(
            date("Y-m-d H:i:s", $baseDate + 3600 * 2),
            $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $segment2Id])
        );
        $I->assertEquals(
            date("Y-m-d H:i:s", $baseDate + 3600 * 3),
            $I->grabFromDatabase("TripSegment", "ArrDate", ["TripSegmentID" => $segment2Id])
        );
        $I->assertEquals(
            date("Y-m-d H:i:s", $baseDate + 3600 * 4),
            $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $segment3Id])
        );
        $I->assertEquals(
            date("Y-m-d H:i:s", $baseDate + 3600 * 5),
            $I->grabFromDatabase("TripSegment", "ArrDate", ["TripSegmentID" => $segment3Id])
        );
    }

    public function trainAndBusWithSameNumbers(\TestSymfonyGuy $I)
    {
        $trainRide = new Train();
        $trainRide->pricingInfo = new PricingInfo();
        $trainRide->pricingInfo->total = 100;

        $trainRide->providerInfo = new ProviderInfo();
        $trainRide->providerInfo->code = 'testprovider';
        $trainRide->providerInfo->name = 'Test Provider';
        $trainRide->confirmationNumbers = [new ConfNo()];
        $trainRide->confirmationNumbers[0]->number = "Conf1";
        $trainRide->segments = [new TrainSegment()];
        $trainRide->segments[0]->scheduleNumber = 'SN2';
        $trainRide->segments[0]->departure = new TransportLocation();
        $trainRide->segments[0]->departure->name = "Second Segment Departure Station";
        $trainRide->segments[0]->departure->address = new Address();
        $trainRide->segments[0]->departure->address->text = "Queens, NY 11430, USA";
        $trainRide->segments[0]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+5 days'));
        $trainRide->segments[0]->departure->stationCode = 'DS2';
        $trainRide->segments[0]->arrival = new TransportLocation();
        $trainRide->segments[0]->arrival->name = "Second Segment Arrival Station";
        $trainRide->segments[0]->arrival->address = new Address();
        $trainRide->segments[0]->arrival->address->text = "Longford TW6, UK";
        $trainRide->segments[0]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+6 days'));
        $trainRide->segments[0]->arrival->stationCode = 'AS2';
        $trainRide->segments[0]->serviceName = 'second segment service';

        // check 1
        $accountId = $I->createAwAccount($this->user->getId(), "testprovider", "balance.random");
        $account = $I->getContainer()->get("doctrine.orm.entity_manager")->find(Account::class, $accountId);
        $this->itinerariesProcessor->save([$trainRide], SavingOptions::savingByAccount($account, true));
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $I->assertEquals(1, $I->grabCountFromDatabase("Trip", ["AccountID" => $accountId]));

        // add bus and check again
        $busRide = new Bus();
        $busRide->pricingInfo = new PricingInfo();
        $busRide->pricingInfo->total = 100;
        $busRide->confirmationNumbers = [new ConfNo()];
        $busRide->confirmationNumbers[0]->number = "Conf1";
        $busRide->segments = [new BusSegment()];
        $busRide->segments[0]->scheduleNumber = 'SN2';
        $busRide->segments[0]->departure = new TransportLocation();
        $busRide->segments[0]->departure->name = "Second Segment Departure Station";
        $busRide->segments[0]->departure->address = new Address();
        $busRide->segments[0]->departure->address->text = "Queens, NY 11430, USA";
        $busRide->segments[0]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+5 days'));
        $busRide->segments[0]->departure->stationCode = 'DS2';
        $busRide->segments[0]->arrival = new TransportLocation();
        $busRide->segments[0]->arrival->name = "Second Segment Arrival Station";
        $busRide->segments[0]->arrival->address = new Address();
        $busRide->segments[0]->arrival->address->text = "Longford TW6, UK";
        $busRide->segments[0]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+6 days'));
        $busRide->segments[0]->arrival->stationCode = 'AS2';

        // check 2
        $this->itinerariesProcessor->save([$busRide], SavingOptions::savingByAccount($account, true));
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $I->assertEquals(2, $I->grabCountFromDatabase("Trip", ["AccountID" => $accountId]));

        // update bus ride only
        $busRide->pricingInfo->total = 123;
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $this->itinerariesProcessor->save([$busRide], SavingOptions::savingByAccount($account, true));
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $I->assertEquals(123, $I->grabFromDatabase("Trip", "Total", ["AccountID" => $accountId, "Category" => Trip::CATEGORY_BUS]));
        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["AccountID" => $accountId, "Category" => Trip::CATEGORY_TRAIN]));
    }

    public function matchTrainByAgency(\TestSymfonyGuy $I)
    {
        $depDate = date("Y-m-d 14:00:00", strtotime("tomorrow"));
        $arrDate = date("Y-m-d 18:00:00", strtotime("tomorrow"));

        $trip1Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'TravelAgencyConfirmationNumbers' => 'CONF1',
            'Category' => Trip::CATEGORY_TRAIN,
        ]);
        $trip1Segment1Id = $I->createTripSegment([
            "TripID" => $trip1Id,
            "DepName" => "Second Segment Departure Station",
            "ArrName" => "Second Segment Arrival Station",
            "DepDate" => date("Y-m-d 11:00", strtotime($depDate)),
            "ScheduledDepDate" => date("Y-m-d 11:00", strtotime($depDate)),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
        ]);

        $trainRide = new Train();
        $trainRide->pricingInfo = new PricingInfo();
        $trainRide->pricingInfo->total = 100;

        $trainRide->providerInfo = new ProviderInfo();
        $trainRide->providerInfo->code = 'testprovider';
        $trainRide->providerInfo->name = 'Test Provider';
        $trainRide->segments = [new TrainSegment()];
        $trainRide->segments[0]->scheduleNumber = 'SN2';
        $trainRide->segments[0]->departure = new TransportLocation();
        $trainRide->segments[0]->departure->name = "Second Segment Departure Station";
        $trainRide->segments[0]->departure->address = new Address();
        $trainRide->segments[0]->departure->address->text = "Queens, NY 11430, USA";
        $trainRide->segments[0]->departure->localDateTime = $depDate;
        $trainRide->segments[0]->departure->stationCode = 'DS2';
        $trainRide->segments[0]->arrival = new TransportLocation();
        $trainRide->segments[0]->arrival->name = "Second Segment Arrival Station";
        $trainRide->segments[0]->arrival->address = new Address();
        $trainRide->segments[0]->arrival->address->text = "Longford TW6, UK";
        $trainRide->segments[0]->arrival->localDateTime = $arrDate;
        $trainRide->segments[0]->arrival->stationCode = 'AS2';
        $trainRide->segments[0]->serviceName = 'second segment service';

        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $trainRide->travelAgency = new TravelAgency();
        $trainRide->travelAgency->confirmationNumbers = [$confNo];
        $trainRide->travelAgency->providerInfo = new ProviderInfo();
        $trainRide->travelAgency->providerInfo->code = 'testprovider';
        $trainRide->travelAgency->providerInfo->name = 'Test Provider';

        $this->itinerariesProcessor->save([$trainRide], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabCountFromDatabase("Trip", ["UserID" => $this->user->getId()]));
        $I->assertEquals(1, $I->grabFromDatabase("Trip", "Parsed", ["TripID" => $trip1Id]));
    }

    /**
     * @dataProvider unhideLogicDataProvider
     */
    public function testUnhideLogic(\TestSymfonyGuy $I, Example $example)
    {
        $depDate = date("Y-m-d 14:00:00", strtotime("tomorrow"));
        $arrDate = date("Y-m-d 18:00:00", strtotime("tomorrow"));

        $trip1Id = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getId(),
            'TravelAgencyConfirmationNumbers' => 'CONF-1',
            "ProviderID" => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]),
            "Copied" => $example["TripCopied"],
            "Hidden" => $example["TripHidden"],
        ]);
        $trip1Segment1Id = $I->createTripSegment([
            "TripID" => $trip1Id,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "DepDate" => date("Y-m-d 11:00", strtotime($depDate)),
            "ScheduledDepDate" => date("Y-m-d 11:00", strtotime($depDate)),
            "ArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "ScheduledArrDate" => date("Y-m-d 18:00", strtotime("+10 day")),
            "Hidden" => $example["SegmentHidden"],
        ]);

        $flight = new Flight();
        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->airline = new Airline();
        $segment->marketingCarrier->airline->name = "American Airlines";
        $segment->marketingCarrier->airline->iata = "AA";
        $segment->marketingCarrier->airline->icao = "AAL";

        $departure = new TripLocation();
        $departure->airportCode = "JFK";
        $departure->name = "JFK Airport";
        $departure->address = new Address();
        $departure->address->text = "JFK Airport";
        $departure->localDateTime = $depDate;
        $segment->departure = $departure;

        $arrival = new TripLocation();
        $arrival->airportCode = "LAX";
        $arrival->name = "LAX Airport";
        $arrival->address = new Address();
        $arrival->address->text = "LAX Airport";
        $arrival->localDateTime = $arrDate;
        $segment->arrival = $arrival;
        $flight->segments = [
            $segment,
        ];
        $flight->pricingInfo = new PricingInfo();
        $flight->pricingInfo->total = 100;
        $flight->travelAgency = new TravelAgency();
        $confNo = new ConfNo();
        $confNo->number = 'CONF1';
        $taProviderId = $I->createAwProvider();
        $flight->travelAgency->confirmationNumbers = [$confNo];
        $flight->travelAgency->providerInfo = new ProviderInfo();
        $flight->travelAgency->providerInfo->code = $I->grabFromDatabase("Provider", "Code", ["ProviderID" => $taProviderId]);
        $flight->travelAgency->providerInfo->name = $I->grabFromDatabase("Provider", "Name", ["ProviderID" => $taProviderId]);

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource(), $example["InitializedByUser"]));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        if ($example["ExpectedMatch"]) {
            $I->assertEquals(1, $I->grabCountFromDatabase("Trip", ["UserID" => $this->user->getId()]));
            $I->assertEquals(
                $depDate,
                $I->grabFromDatabase("TripSegment", "DepDate", ["TripSegmentID" => $trip1Segment1Id])
            );
        } else {
            $I->assertEquals(2, $I->grabCountFromDatabase("Trip", ["UserID" => $this->user->getId()]));
        }
        $I->assertEquals($example["ExpectedTripHidden"], $I->grabFromDatabase("Trip", "Hidden", ["TripID" => $trip1Id]));
        $I->assertEquals($example["ExpectedSegmentHidden"], $I->grabFromDatabase("TripSegment", "Hidden", ["TripSegmentID" => $trip1Segment1Id]));
    }

    public function unhideLogicDataProvider()
    {
        return [
            [
                'TripHidden' => 1, "TripCopied" => 1, "SegmentHidden" => 0, 'InitializedByUser' => true,
                "ExpectedMatch" => true, "ExpectedTripHidden" => 0, "ExpectedSegmentHidden" => 0,
            ],
            [
                'TripHidden' => 0, "TripCopied" => 0, "SegmentHidden" => 1, 'InitializedByUser' => true,
                "ExpectedMatch" => true, "ExpectedTripHidden" => 0, "ExpectedSegmentHidden" => 0,
            ],
            [
                'TripHidden' => 0, "TripCopied" => 0, "SegmentHidden" => 1, 'InitializedByUser' => false,
                "ExpectedMatch" => true, "ExpectedTripHidden" => 0, "ExpectedSegmentHidden" => 0,
            ],
            [
                'TripHidden' => 0, "TripCopied" => 0, "SegmentHidden" => 2, 'InitializedByUser' => true,
                "ExpectedMatch" => true, "ExpectedTripHidden" => 0, "ExpectedSegmentHidden" => 0,
            ],
            [
                'TripHidden' => 0, "TripCopied" => 0, "SegmentHidden" => 1, 'InitializedByUser' => true,
                "ExpectedMatch" => true, "ExpectedTripHidden" => 0, "ExpectedSegmentHidden" => 0,
            ],
            [
                'TripHidden' => 0, "TripCopied" => 0, "SegmentHidden" => 2, 'InitializedByUser' => false,
                "ExpectedMatch" => true, "ExpectedTripHidden" => 0, "ExpectedSegmentHidden" => 2,
            ],
        ];
    }

    public function matchTripByMarketing(\TestSymfonyGuy $I)
    {
        $depDate = strtotime("+1 day 15:00");
        $arrDate = strtotime("+3 hour", $depDate);
        $testProviderId = $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]);

        $trip1Id = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getId(),
            'RecordLocator' => 'RECLOC1',
            'ProviderID' => $testProviderId,
            'Category' => 1,
        ]);

        $I->createTripSegment([
            'TripID' => $trip1Id,
            'DepCode' => 'PHL',
            'DepName' => 'Philadelphia International Airport',
            'DepDate' => date("Y-m-d H:i:s", $depDate),
            'ScheduledDepDate' => date("Y-m-d H:i:s", $depDate),
            'ArrCode' => 'DOH',
            'ArrName' => 'Hamad International Airport',
            'ArrDate' => date("Y-m-d H:i:s", $arrDate),
            'ScheduledArrDate' => date("Y-m-d H:i:s", $arrDate),
            'AirlineName' => 'British Airways',
            'FlightNumber' => '728',
            'MarketingAirlineConfirmationNumber' => 'RECLOC1',
        ]);
        $I->createTripSegment([
            'TripID' => $trip1Id,
            'DepCode' => 'DOH',
            'DepName' => 'Hamad International Airport',
            'DepDate' => date("Y-m-d H:i:s", $depDate + 8 * 3600),
            'ScheduledDepDate' => date("Y-m-d H:i:s", $depDate + 8 * 3600),
            'ArrCode' => 'JNB',
            'ArrName' => 'O.R. Tambo International Airport',
            'ArrDate' => date("Y-m-d H:i:s", $arrDate + 8 * 3600),
            'ScheduledArrDate' => date("Y-m-d H:i:s", $arrDate + 8 * 3600),
            'AirlineName' => 'British Airways',
            'FlightNumber' => '1365',
            'MarketingAirlineConfirmationNumber' => 'RECLOC1',
        ]);

        $flight = create(function (Flight $flight) use ($depDate, $arrDate) {
            $flight->segments = [
                create(function (FlightSegment $segment) use ($depDate, $arrDate) {
                    $segment->marketingCarrier = create(function (MarketingCarrier $carrier) {
                        $carrier->flightNumber = '2156';
                        $carrier->airline = create(function (Airline $airline) {
                            $airline->name = "British Airways";
                            $airline->iata = "BA";
                            $airline->icao = "BAQ";
                        });
                    });
                    $segment->departure = create(function (TripLocation $location) use ($depDate) {
                        $location->airportCode = "MSY";
                        $location->name = "Louis Armstrong New Orleans International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "MSY";
                        });
                        $location->localDateTime = date("c", $depDate - 8 * 3600);
                    });
                    $segment->arrival = create(function (TripLocation $location) use ($arrDate) {
                        $location->airportCode = "PHL";
                        $location->name = "Philadelphia International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "PHL";
                        });
                        $location->localDateTime = date("c", $arrDate - 6 * 3600);
                    });
                }),
                create(function (FlightSegment $segment) use ($depDate, $arrDate) {
                    $segment->marketingCarrier = create(function (MarketingCarrier $carrier) {
                        $carrier->flightNumber = '728';
                        $carrier->airline = create(function (Airline $airline) {
                            $airline->name = "British Airways";
                            $airline->iata = "BA";
                            $airline->icao = "BAQ";
                        });
                        $carrier->confirmationNumber = 'RECLOC1';
                    });
                    $segment->departure = create(function (TripLocation $location) use ($depDate) {
                        $location->airportCode = "PHL";
                        $location->name = "Philadelphia International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "PHL";
                        });
                        $location->localDateTime = date("c", $depDate);
                    });
                    $segment->arrival = create(function (TripLocation $location) use ($arrDate) {
                        $location->airportCode = "DOH";
                        $location->name = "Hamad International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "DOH";
                        });
                        $location->localDateTime = date("c", $arrDate);
                    });
                }),
            ];
            $flight->pricingInfo = create(function (PricingInfo $pricingInfo) {
                $pricingInfo->total = 100;
            });
            $flight->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->code = 'testprovider';
                $providerInfo->name = 'Test Provider';
            });
        });

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $trip1Id]));
    }

    public function updateTripProvider(\TestSymfonyGuy $I)
    {
        $depDate = strtotime("+1 day 15:00");
        $arrDate = strtotime("+3 hour", $depDate);
        $otherProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_OTHER]);

        $trip1Id = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getId(),
            'RecordLocator' => 'RECLOC1',
            'ProviderID' => $otherProviderId,
            'Category' => 1,
        ]);

        $I->createTripSegment([
            'TripID' => $trip1Id,
            'DepCode' => 'PHL',
            'DepName' => 'Philadelphia International Airport',
            'DepDate' => date("Y-m-d H:i:s", $depDate),
            'ScheduledDepDate' => date("Y-m-d H:i:s", $depDate),
            'ArrCode' => 'DOH',
            'ArrName' => 'Hamad International Airport',
            'ArrDate' => date("Y-m-d H:i:s", $arrDate),
            'ScheduledArrDate' => date("Y-m-d H:i:s", $arrDate),
            'AirlineName' => 'British Airways',
            'FlightNumber' => '728',
            'MarketingAirlineConfirmationNumber' => 'RECLOC1',
        ]);

        $airlineProviderCode = "p" . bin2hex(random_bytes(7));
        $airlineProviderName = "n" . $airlineProviderCode;
        $airlineProviderId = $I->createAwProvider(null, $airlineProviderCode, ["Kind" => PROVIDER_KIND_AIRLINE]);

        $flight = create(function (Flight $flight) use ($depDate, $arrDate, $airlineProviderCode, $airlineProviderName) {
            $flight->segments = [
                create(function (FlightSegment $segment) use ($depDate, $arrDate) {
                    $segment->marketingCarrier = create(function (MarketingCarrier $carrier) {
                        $carrier->flightNumber = '2156';
                        $carrier->airline = create(function (Airline $airline) {
                            $airline->name = "British Airways";
                            $airline->iata = "BA";
                            $airline->icao = "BAQ";
                        });
                    });
                    $segment->departure = create(function (TripLocation $location) use ($depDate) {
                        $location->airportCode = "MSY";
                        $location->name = "Louis Armstrong New Orleans International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "MSY";
                        });
                        $location->localDateTime = date("c", $depDate - 8 * 3600);
                    });
                    $segment->arrival = create(function (TripLocation $location) use ($arrDate) {
                        $location->airportCode = "PHL";
                        $location->name = "Philadelphia International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "PHL";
                        });
                        $location->localDateTime = date("c", $arrDate - 6 * 3600);
                    });
                }),
                create(function (FlightSegment $segment) use ($depDate, $arrDate) {
                    $segment->marketingCarrier = create(function (MarketingCarrier $carrier) {
                        $carrier->flightNumber = '728';
                        $carrier->airline = create(function (Airline $airline) {
                            $airline->name = "British Airways";
                            $airline->iata = "BA";
                            $airline->icao = "BAQ";
                        });
                        $carrier->confirmationNumber = 'RECLOC1';
                    });
                    $segment->departure = create(function (TripLocation $location) use ($depDate) {
                        $location->airportCode = "PHL";
                        $location->name = "Philadelphia International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "PHL";
                        });
                        $location->localDateTime = date("c", $depDate);
                    });
                    $segment->arrival = create(function (TripLocation $location) use ($arrDate) {
                        $location->airportCode = "DOH";
                        $location->name = "Hamad International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "DOH";
                        });
                        $location->localDateTime = date("c", $arrDate);
                    });
                }),
            ];
            $flight->pricingInfo = create(function (PricingInfo $pricingInfo) {
                $pricingInfo->total = 100;
            });
            $flight->providerInfo = create(function (ProviderInfo $providerInfo) use ($airlineProviderCode, $airlineProviderName) {
                $providerInfo->code = $airlineProviderCode;
                $providerInfo->name = $airlineProviderName;
            });
        });

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource()));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $trip1Id]));
        $I->assertEquals($airlineProviderId, $I->grabFromDatabase("Trip", "ProviderID", ["TripID" => $trip1Id]));
    }

    public function changeTripAccount(\TestSymfonyGuy $I)
    {
        $depDate = strtotime("+1 day 15:00");
        $arrDate = strtotime("+3 hour", $depDate);
        $testProviderId = $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]);
        $account1Id = $I->createAwAccount($this->user->getId(), "testprovider", "login1");

        $trip1Id = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getId(),
            'RecordLocator' => 'RECLOC1',
            'ProviderID' => $testProviderId,
            'Category' => 1,
            'AccountID' => $account1Id,
        ]);

        $I->createTripSegment([
            'TripID' => $trip1Id,
            'DepCode' => 'PHL',
            'DepName' => 'Philadelphia International Airport',
            'DepDate' => date("Y-m-d H:i:s", $depDate),
            'ScheduledDepDate' => date("Y-m-d H:i:s", $depDate),
            'ArrCode' => 'DOH',
            'ArrName' => 'Hamad International Airport',
            'ArrDate' => date("Y-m-d H:i:s", $arrDate),
            'ScheduledArrDate' => date("Y-m-d H:i:s", $arrDate),
            'AirlineName' => 'British Airways',
            'FlightNumber' => '728',
            'MarketingAirlineConfirmationNumber' => 'RECLOC1',
        ]);

        $airlineProviderCode = "p" . bin2hex(random_bytes(7));
        $airlineProviderName = "n" . $airlineProviderCode;
        $airlineProviderId = $I->createAwProvider(null, $airlineProviderCode, ["Kind" => PROVIDER_KIND_AIRLINE]);

        $flight1 = create(function (Flight $flight) use ($depDate, $arrDate, $airlineProviderCode, $airlineProviderName) {
            $flight->segments = [
                create(function (FlightSegment $segment) use ($depDate, $arrDate) {
                    $segment->marketingCarrier = create(function (MarketingCarrier $carrier) {
                        $carrier->flightNumber = '2156';
                        $carrier->airline = create(function (Airline $airline) {
                            $airline->name = "British Airways";
                            $airline->iata = "BA";
                            $airline->icao = "BAQ";
                        });
                    });
                    $segment->departure = create(function (TripLocation $location) use ($depDate) {
                        $location->airportCode = "MSY";
                        $location->name = "Louis Armstrong New Orleans International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "MSY";
                        });
                        $location->localDateTime = date("c", $depDate - 8 * 3600);
                    });
                    $segment->arrival = create(function (TripLocation $location) use ($arrDate) {
                        $location->airportCode = "PHL";
                        $location->name = "Philadelphia International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "PHL";
                        });
                        $location->localDateTime = date("c", $arrDate - 6 * 3600);
                    });
                }),
                create(function (FlightSegment $segment) use ($depDate, $arrDate) {
                    $segment->marketingCarrier = create(function (MarketingCarrier $carrier) {
                        $carrier->flightNumber = '728';
                        $carrier->airline = create(function (Airline $airline) {
                            $airline->name = "British Airways";
                            $airline->iata = "BA";
                            $airline->icao = "BAQ";
                        });
                        $carrier->confirmationNumber = 'RECLOC1';
                    });
                    $segment->departure = create(function (TripLocation $location) use ($depDate) {
                        $location->airportCode = "PHL";
                        $location->name = "Philadelphia International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "PHL";
                        });
                        $location->localDateTime = date("c", $depDate);
                    });
                    $segment->arrival = create(function (TripLocation $location) use ($arrDate) {
                        $location->airportCode = "DOH";
                        $location->name = "Hamad International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "DOH";
                        });
                        $location->localDateTime = date("c", $arrDate);
                    });
                }),
            ];
            $flight->pricingInfo = create(function (PricingInfo $pricingInfo) {
                $pricingInfo->total = 100;
            });
            $flight->providerInfo = create(function (ProviderInfo $providerInfo) use ($airlineProviderCode, $airlineProviderName) {
                $providerInfo->code = $airlineProviderCode;
                $providerInfo->name = $airlineProviderName;
            });
        });

        $account2Id = $I->createAwAccount($this->user->getId(), "testprovider", "login2");
        $account2 = $I->getContainer()->get("doctrine.orm.entity_manager")->find(Account::class, $account2Id);
        $this->itinerariesProcessor->save([$flight1], SavingOptions::savingByAccount($account2, true));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $trip1Id]));
        $I->assertEquals($account2Id, $I->grabFromDatabase("Trip", "AccountID", ["TripID" => $trip1Id]));
    }

    /**
     * refs #21090.
     */
    public function sameFlightNumbers(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase('Trip', [
            'UserID' => $this->user->getId(),
            'RecordLocator' => 'CONF1',
            'ProviderID' => $I->grabFromDatabase('Provider', 'ProviderID', ['Code' => 'testprovider']),
        ]);
        $tripSegment1Id = $I->createTripSegment([
            'TripID' => $tripId,
            'FlightNumber' => 2421,
            'DepCode' => 'MSN',
            'DepName' => 'MSN',
            'ArrCode' => 'ATL',
            'ArrName' => 'ATL',
            'DepDate' => date('Y-m-d 15:00:00', strtotime('+1 day')),
            'ScheduledDepDate' => date('Y-m-d 15:00:00', strtotime('+1 day')),
            'ArrDate' => date('Y-m-d 18:00:00', strtotime('+1 day')),
            'ScheduledArrDate' => date('Y-m-d 18:00:00', strtotime('+1 day')),
            'MarketingAirlineConfirmationNumber' => 'CONF1',
        ]);
        $tripSegment2Id = $I->createTripSegment([
            'TripID' => $tripId,
            'FlightNumber' => 1419,
            'DepCode' => 'ATL',
            'DepName' => 'ATL',
            'ArrCode' => 'PBI',
            'ArrName' => 'PBI',
            'DepDate' => $dep2Date = date('Y-m-d 10:00:00', strtotime('+2 day')),
            'ScheduledDepDate' => date('Y-m-d 10:00:00', strtotime('+2 day')),
            'ArrDate' => $arr2Date = date('Y-m-d 14:00:00', strtotime('+2 day')),
            'ScheduledArrDate' => date('Y-m-d 14:00:00', strtotime('+2 day')),
            'MarketingAirlineConfirmationNumber' => 'CONF1',
        ]);
        $tripSegment3Id = $I->createTripSegment([
            'TripID' => $tripId,
            'FlightNumber' => 1419,
            'DepCode' => 'PBI',
            'DepName' => 'PBI',
            'ArrCode' => 'ATL',
            'ArrName' => 'ATL',
            'DepDate' => $dep3Date = date('Y-m-d 15:00:00', strtotime('+6 day')),
            'ScheduledDepDate' => date('Y-m-d 15:00:00', strtotime('+6 day')),
            'ArrDate' => $arr3Date = date('Y-m-d 18:00:00', strtotime('+6 day')),
            'ScheduledArrDate' => date('Y-m-d 18:00:00', strtotime('+6 day')),
            'MarketingAirlineConfirmationNumber' => 'CONF1',
        ]);
        $tripSegment4Id = $I->createTripSegment([
            'TripID' => $tripId,
            'FlightNumber' => 1253,
            'DepCode' => 'ATL',
            'DepName' => 'ATL',
            'ArrCode' => 'MSN',
            'ArrName' => 'MSN',
            'DepDate' => $dep4Date = date('Y-m-d 15:00:00', strtotime('+7 day')),
            'ScheduledDepDate' => date('Y-m-d 15:00:00', strtotime('+7 day')),
            'ArrDate' => $arr4Date = date('Y-m-d 18:00:00', strtotime('+7 day')),
            'ScheduledArrDate' => date('Y-m-d 18:00:00', strtotime('+7 day')),
            'MarketingAirlineConfirmationNumber' => 'CONF1',
        ]);

        $flight = new Flight();
        $segment = new FlightSegment();
        $segment->marketingCarrier = new MarketingCarrier();
        $segment->marketingCarrier->airline = new Airline();
        $segment->marketingCarrier->flightNumber = '1419';
        $segment->marketingCarrier->confirmationNumber = 'CONF1';
        $segment->marketingCarrier->airline->name = "American Airlines";
        $segment->marketingCarrier->airline->iata = "AA";
        $segment->marketingCarrier->airline->icao = "AAL";

        $departure = new TripLocation();
        $departure->airportCode = "PBI";
        $departure->name = "PBI Airport";
        $departure->address = new Address();
        $departure->address->text = "PBI Airport";
        $departure->localDateTime = $dep3Date;
        $segment->departure = $departure;

        $arrival = new TripLocation();
        $arrival->airportCode = "ATL";
        $arrival->name = "ATL Airport";
        $arrival->address = new Address();
        $arrival->address->text = "ATL Airport";
        $arrival->localDateTime = $arr3Date;
        $segment->arrival = $arrival;

        $segment2 = new FlightSegment();
        $segment2->marketingCarrier = new MarketingCarrier();
        $segment2->marketingCarrier->airline = new Airline();
        $segment2->marketingCarrier->flightNumber = '1253';
        $segment2->marketingCarrier->confirmationNumber = 'CONF1';
        $segment2->marketingCarrier->airline->name = "American Airlines";
        $segment2->marketingCarrier->airline->iata = "AA";
        $segment2->marketingCarrier->airline->icao = "AAL";

        $departure2 = new TripLocation();
        $departure2->airportCode = "ATL";
        $departure2->name = "ATL Airport";
        $departure2->address = new Address();
        $departure2->address->text = "ATL Airport";
        $departure2->localDateTime = $dep4Date;
        $segment2->departure = $departure2;

        $arrival2 = new TripLocation();
        $arrival2->airportCode = "MSN";
        $arrival2->name = "MSN Airport";
        $arrival2->address = new Address();
        $arrival2->address->text = "MSN Airport";
        $arrival2->localDateTime = $arr4Date;
        $segment2->arrival = $arrival2;

        $flight->segments = [
            $segment,
            $segment2,
        ];
        $flight->pricingInfo = new PricingInfo();
        $flight->pricingInfo->total = 100;

        $this->itinerariesProcessor->save([$flight], SavingOptions::savingByEmail(new Owner($this->user, null), 123, $this->getParsedEmailSource(), true, false, true));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals($dep2Date, $I->grabFromDatabase('TripSegment', 'DepDate', ['TripSegmentID' => $tripSegment2Id]));
    }

    private function matchTripByDatesDataProvider()
    {
        $depDate = fn () => new \DateTime('tomorrow 11:00:00');
        $arrDate = fn () => new \DateTime('tomorrow 18:00:00');
        $dbTripSegment = fn (array $fields = []): DBTripSegment => new DBTripSegment(
            'JFK', 'JFK', $depDate(),
            'LAX', 'LAX', $arrDate(), null, $fields
        );
        $dbTrip = fn (?string $confNo = null, array $fields = []): DBTrip => new DBTrip(
            $confNo,
            [$dbTripSegment()],
            new DBUser(),
            $fields
        );
        $schemaDepTripLocation = fn (): TripLocation => SchemaBuilder::makeSchemaTripLocation(
            'JFK Airport',
            $depDate(),
            SchemaBuilder::makeSchemaAddress('JFK Airport'),
            'JFK'
        );
        $schemaArrTripLocation = fn (): TripLocation => SchemaBuilder::makeSchemaTripLocation(
            'LAX Airport',
            $arrDate(),
            SchemaBuilder::makeSchemaAddress('LAX Airport'),
            'LAX'
        );
        $schemaTripSegment = fn (): FlightSegment => SchemaBuilder::makeSchemaFlightSegment(
            $schemaDepTripLocation(),
            $schemaArrTripLocation(),
            SchemaBuilder::makeSchemaMarketingCarrier(
                SchemaBuilder::makeSchemaAirline('American Airlines', 'AA'), 12345,
            )
        );

        return [
            [
                'expectedMatch' => false,
                'trip' => $dbTrip()->setProvider(new DBProvider()),
                'schema' => SchemaBuilder::makeSchemaFlight(
                    [
                        $schemaTripSegment(),
                    ],
                    null,
                    SchemaBuilder::makeSchemaIssuingCarrier(null, 'CONF1'),
                ),
            ],
            [
                'expectedMatch' => false,
                'trip' => $dbTrip(null, [
                    'TravelerNames' => 'John Smith',
                ])->setProvider(new DBProvider()),
                'schema' => SchemaBuilder::makeSchemaFlight(
                    [
                        $schemaTripSegment(),
                    ],
                    [SchemaBuilder::makeSchemaPerson('Larry Jameson', true)],
                    SchemaBuilder::makeSchemaIssuingCarrier(null, 'CONF1'),
                ),
            ],
            [
                'expectedMatch' => true,
                'trip' => $dbTrip()->setProvider(new DBProvider()),
                'schema' => SchemaBuilder::makeSchemaFlight(
                    [
                        $schemaTripSegment(),
                    ],
                    [SchemaBuilder::makeSchemaPerson('Larry Jameson', true)],
                    SchemaBuilder::makeSchemaIssuingCarrier(null, 'CONF1'),
                ),
            ],
            [
                'expectedMatch' => true,
                'trip' => $dbTrip(null, [
                    'TravelerNames' => 'John Smith',
                ])->setProvider(new DBProvider()),
                'schema' => SchemaBuilder::makeSchemaFlight(
                    [
                        $schemaTripSegment(),
                    ],
                    null,
                    SchemaBuilder::makeSchemaIssuingCarrier(null, 'CONF1'),
                ),
            ],
            [
                'expectedMatch' => true,
                'trip' => $dbTrip(null, [
                    'TravelerNames' => 'John Smith',
                ])->setProvider(new DBProvider()),
                'schema' => SchemaBuilder::makeSchemaFlight(
                    [
                        $schemaTripSegment(),
                    ],
                    [SchemaBuilder::makeSchemaPerson('John Smith', true)],
                    SchemaBuilder::makeSchemaIssuingCarrier(null, 'CONF1'),
                ),
            ],
        ];
    }

    private function notMatchSameMarketingProviderAndDifferentConfNoDataProvider()
    {
        $depDate = fn () => new \DateTime('tomorrow 11:00:00');
        $arrDate = fn () => new \DateTime('tomorrow 18:00:00');
        $dbTripSegment = fn (array $fields = []): DBTripSegment => new DBTripSegment(
            'JFK', 'JFK', $depDate(),
            'LAX', 'LAX', $arrDate(),
            null, $fields
        );
        $dbTrip = fn (
            array $segments,
            ?string $confNo = null,
            array $fields = []
        ): DBTrip => new DBTrip(
            $confNo,
            $segments,
            new DBUser(),
            array_merge(['TravelerNames' => 'John Smith'], $fields)
        );
        $schemaDepTripLocation = fn (): TripLocation => SchemaBuilder::makeSchemaTripLocation(
            'JFK Airport',
            $depDate(),
            SchemaBuilder::makeSchemaAddress('JFK Airport'),
            'JFK'
        );
        $schemaArrTripLocation = fn (): TripLocation => SchemaBuilder::makeSchemaTripLocation(
            'LAX Airport',
            $arrDate(),
            SchemaBuilder::makeSchemaAddress('LAX Airport'),
            'LAX'
        );
        $schemaFlight = fn (
            Airline $marketingCarrier,
            ?string $marketingConfNo = null
        ): Flight => SchemaBuilder::makeSchemaFlight(
            [
                SchemaBuilder::makeSchemaFlightSegment(
                    $schemaDepTripLocation(),
                    $schemaArrTripLocation(),
                    SchemaBuilder::makeSchemaMarketingCarrier(
                        $marketingCarrier,
                        12345,
                        $marketingConfNo
                    ),
                ),
            ],
            [SchemaBuilder::makeSchemaPerson('John Smith', true)],
        );

        return [
            [
                'expectedMatch' => true,
                'trip' => $dbTrip([
                    $dbTripSegment(['MarketingAirlineConfirmationNumber' => 'CONF0'])
                        ->setAirline(new DBAirline('AA')),
                ]),
                'schema' => $schemaFlight(
                    SchemaBuilder::makeSchemaAirline('American Airlines', 'AA'),
                    'CONF0'
                ),
            ],
            [
                'expectedMatch' => false,
                'trip' => $dbTrip([
                    $dbTripSegment(['MarketingAirlineConfirmationNumber' => 'CONF0'])
                        ->setAirline(new DBAirline('AA')),
                ]),
                'schema' => $schemaFlight(
                    SchemaBuilder::makeSchemaAirline('American Airlines', 'AA'),
                    'CONF1'
                ),
            ],
        ];
    }

    private function getParsedEmailSource()
    {
        return new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com');
    }
}
