<?php
/**
 * Created by PhpStorm.
 * User: ANelyudov
 * Date: 16.03.18
 * Time: 12:00.
 */

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Itinerary as ItineraryEntity;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Restaurant as EntityEvent;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Trip as EntityBusRide;
use AwardWallet\MainBundle\Entity\Trip as EntityCruise;
use AwardWallet\MainBundle\Entity\Trip as EntityFerry;
use AwardWallet\MainBundle\Entity\Trip as EntityFlight;
use AwardWallet\MainBundle\Entity\Trip as EntityTrainRide;
use AwardWallet\MainBundle\Entity\Trip as EntityTransfer;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityBusRideSegment;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityCruiseSegment;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityFerrySegment;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityFlightSegment;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTrainRideSegment;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTransferSegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\Resources\Itineraries\CruiseDetails;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\Aircraft;
use AwardWallet\Schema\Itineraries\Airline;
use AwardWallet\Schema\Itineraries\Bus as SchemaBusRide;
use AwardWallet\Schema\Itineraries\BusSegment as SchemaBusRideSegment;
use AwardWallet\Schema\Itineraries\Car;
use AwardWallet\Schema\Itineraries\CarRental as SchemaRental;
use AwardWallet\Schema\Itineraries\CarRentalDiscount;
use AwardWallet\Schema\Itineraries\CarRentalLocation;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\CruiseSegment as SchemaCruiseSegment;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Fee;
use AwardWallet\Schema\Itineraries\Ferry as SchemaFerry;
use AwardWallet\Schema\Itineraries\FerrySegment as SchemaFerrySegment;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaReservation;
use AwardWallet\Schema\Itineraries\IssuingCarrier;
use AwardWallet\Schema\Itineraries\Itinerary;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\OperatingCarrier;
use AwardWallet\Schema\Itineraries\ParsedNumber;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Itineraries\PhoneNumber;
use AwardWallet\Schema\Itineraries\PricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo;
use AwardWallet\Schema\Itineraries\Room;
use AwardWallet\Schema\Itineraries\Train as SchemaTrainRide;
use AwardWallet\Schema\Itineraries\TrainSegment as SchemaTrainRideSegment;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use AwardWallet\Schema\Itineraries\TransferLocation;
use AwardWallet\Schema\Itineraries\TransferSegment as SchemaTransferSegment;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Schema\Itineraries\TravelAgency;
use AwardWallet\Schema\Itineraries\TripLocation;
use AwardWallet\Schema\Itineraries\Vehicle;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\Tests\Modules\Utils\ClosureEvaluator\create;

/**
 * @group frontend-functional
 */
class ItinerariesProcessorCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?ItinerariesProcessor $itinerariesProcessor;

    /**
     * @var Owner
     */
    private $owner;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var SavingOptions
     */
    private $options;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * @var Provider
     */
    private $travelAgency;

    public function _before(\TestSymfonyGuy $I)
    {
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createFamilyMember($user->getUserid(), 'Family', 'Member')
        );
        $this->owner = OwnerRepository::getOwner($user, $familyMember);
        $providerId = $I->createAwProvider();
        $this->account = $I->grabService('doctrine')->getRepository(Account::class)->find(
            $I->createAwAccount($this->owner->getUser()->getUserid(), $providerId, 'test', null, [
                'UserAgentID' => $this->owner->getFamilyMember()->getUseragentid(),
            ])
        );
        $this->options = SavingOptions::savingByAccount($this->account, SavingOptions::INITIALIZED_AUTOMATICALLY);
        $this->itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
        $this->provider = $I->grabService('doctrine')->getRepository(Provider::class)
            ->find($I->createAwProvider("provider {$I->grabRandomString(10)}", $I->grabRandomString(10)));
        $this->travelAgency = $I->grabService('doctrine')->getRepository(Provider::class)
            ->find($I->createAwProvider("travel agency {$I->grabRandomString(10)}", $I->grabRandomString(10)));
    }

    public function saveFlight(\TestSymfonyGuy $I)
    {
        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.entity_manager');

        $repository = $em->getRepository(EntityTrip::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaFlight = $this->getSchemaFlight($I);
        $this->itinerariesProcessor->save([$schemaFlight], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkFlightFields($I, $flight, $schemaFlight, 'created');
    }

    public function updateFlight(\TestSymfonyGuy $I)
    {
        $schemaFlight = $this->getSchemaFlight($I);
        $this->createEntityFlight($I, $schemaFlight);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaFlight], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkFlightFields($I, $flight, $schemaFlight, 'updated');
    }

    public function updateCopiedFlight(\TestSymfonyGuy $I)
    {
        $schemaFlight = $this->getSchemaFlight($I);
        $this->createEntityFlight($I, $schemaFlight);
        $this->createEntityFlight($I, $schemaFlight, new Owner($this->owner->getUser(), null));
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $entities = $repository->findBy(['account' => $this->account]);
        $I->assertCount(2, $entities);
        /** @var Trip $copy */
        $copy = array_shift($entities);
        /** @var Trip $original */
        $original = array_shift($entities);
        $copy->setCopied(true);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.entity_manager');
        $em->flush();

        $this->itinerariesProcessor->save([$schemaFlight], $this->options);
        $em->flush();
        $this->checkFlightFields($I, $original, $schemaFlight, 'updated');
        $this->checkFlightFields($I, $copy, $schemaFlight, 'updated');
    }

    public function updateDeletedFlight(\TestSymfonyGuy $I)
    {
        $schemaFlight = $this->getSchemaFlight($I);
        $this->createEntityFlight($I, $schemaFlight);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        /** @var Trip $trip */
        $trip = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($trip);
        $trip->setHidden(true);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $this->options = SavingOptions::savingByAccount($this->account, SavingOptions::INITIALIZED_BY_USER);
        $this->itinerariesProcessor->save([$schemaFlight], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkFlightFields($I, $flight, $schemaFlight, 'updated');
        $I->assertFalse($trip->getHidden());
    }

    public function updateDeletedCopiedFlight(\TestSymfonyGuy $I)
    {
        $schemaFlight = $this->getSchemaFlight($I);
        $this->createEntityFlight($I, $schemaFlight);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        /** @var Trip $trip */
        $trip = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($trip);
        $trip->setHidden(true);
        $trip->setCopied(true);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $this->options = SavingOptions::savingByAccount($this->account, SavingOptions::INITIALIZED_BY_USER);
        $this->itinerariesProcessor->save([$schemaFlight], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkFlightFields($I, $flight, $schemaFlight, 'updated');
        $I->assertFalse($trip->getHidden());
    }

    public function updateDeletedModifiedFlight(\TestSymfonyGuy $I)
    {
        $schemaFlight = $this->getSchemaFlight($I);
        $this->createEntityFlight($I, $schemaFlight);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        /** @var Trip $trip */
        $trip = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($trip);
        $trip->setHidden(true);
        $trip->setModified(true);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $this->itinerariesProcessor->save([$schemaFlight], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkFlightFields($I, $flight, $schemaFlight, 'updated');
        $I->assertFalse($trip->getModified());
    }

    public function updateModifiedFlight(\TestSymfonyGuy $I)
    {
        $schemaFlight = $this->getSchemaFlight($I);
        $this->createEntityFlight($I, $schemaFlight);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        /** @var Trip $trip */
        $trip = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($trip);
        $trip->setModified(true);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $this->itinerariesProcessor->save([$schemaFlight], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkFlightFields($I, $flight, $schemaFlight, 'updated');
        $I->assertFalse($trip->getModified());
    }

    public function updateFlightPartial(\TestSymfonyGuy $I)
    {
        $schemaFlight = $this->getSchemaFlight($I);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $I->assertNull($repository->findOneBy(['user' => $user]));

        $options = SavingOptions::savingByEmail(new Owner($user), 1, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com'));
        $this->itinerariesProcessor->save([$schemaFlight], $options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        /** @var EntityFlight $flight */
        $flight = $repository->findOneBy(['user' => $user]);
        $I->assertNotNull($flight);
        $I->assertCount(2, $flight->getVisibleSegments());

        $fixedFlight = clone $schemaFlight;
        // Leave only the first segment
        $fixedFlight->segments = [$fixedFlight->segments[0]];
        $this->itinerariesProcessor->save([$fixedFlight], $options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        /** @var EntityFlight $flight */
        $flight = $repository->findOneBy(['user' => $user]);
        $I->assertNotNull($flight);
        $I->assertCount(1, $flight->getVisibleSegments());

        // Bring segment back
        $this->itinerariesProcessor->save([$schemaFlight], $options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        /** @var EntityFlight $flight */
        $flight = $repository->findOneBy(['user' => $user]);
        $I->assertNotNull($flight);
        $I->assertCount(2, $flight->getVisibleSegments());

        // This time source ID will be different, segment should not be hidden
        $options = SavingOptions::savingByEmail(new Owner($user), 2, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com'));
        $this->itinerariesProcessor->save([$fixedFlight], $options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        /** @var EntityFlight $flight */
        $flight = $repository->findOneBy(['user' => $user]);
        $I->assertNotNull($flight);
        $I->assertCount(2, $flight->getVisibleSegments());
    }

    public function saveTrainRide(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaTrainRide = $this->getSchemaTrainRide();
        $this->itinerariesProcessor->save([$schemaTrainRide], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $trainRIde = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($trainRIde);
        $this->checkTrainRideFields($I, $trainRIde, $schemaTrainRide, 'created');
    }

    public function updateTrainRide(\TestSymfonyGuy $I)
    {
        $schemaTrainRide = $this->getSchemaTrainRide();
        $this->createEntityTrainRide($I, $schemaTrainRide);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaTrainRide], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkTrainRideFields($I, $flight, $schemaTrainRide, 'updated');
    }

    public function saveBusRide(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaBusRide = $this->getSchemaBusRide();
        $this->itinerariesProcessor->save([$schemaBusRide], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $BusRIde = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($BusRIde);
        $this->checkBusRideFields($I, $BusRIde, $schemaBusRide, 'created');
    }

    public function updateBusRide(\TestSymfonyGuy $I)
    {
        $schemaBusRide = $this->getSchemaBusRide();
        $this->createEntityBusRide($I, $schemaBusRide);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaBusRide], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkBusRideFields($I, $flight, $schemaBusRide, 'updated');
    }

    public function saveCruise(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaCruise = $this->getSchemaCruise();
        $this->itinerariesProcessor->save([$schemaCruise], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $Cruise = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($Cruise);
        $this->checkCruiseFields($I, $Cruise, $schemaCruise, 'created');
    }

    public function updateCruise(\TestSymfonyGuy $I)
    {
        $schemaCruise = $this->getSchemaCruise();
        $this->createEntityCruise($I, $schemaCruise);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaCruise], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkCruiseFields($I, $flight, $schemaCruise, 'updated');
    }

    public function saveFerry(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schema = $this->getSchemaFerry();
        $this->itinerariesProcessor->save([$schema], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $itinerary = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($itinerary);
        $this->checkFerryFields($I, $itinerary, $schema, 'created');
    }

    public function updateFerry(\TestSymfonyGuy $I)
    {
        $schemaFerry = $this->getSchemaFerry();
        $this->createEntityFerry($I, $schemaFerry);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaFerry], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkFerryFields($I, $flight, $schemaFerry, 'updated');
    }

    public function saveTransfer(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaTransfer = $this->getSchemaTransfer();
        $this->itinerariesProcessor->save([$schemaTransfer], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $Transfer = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($Transfer);
        $this->checkTransferFields($I, $Transfer, $schemaTransfer, 'created');
    }

    public function updateTransfer(\TestSymfonyGuy $I)
    {
        $schemaTransfer = $this->getSchemaTransfer();
        $this->createEntityTransfer($I, $schemaTransfer);
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaTransfer], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $flight = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($flight);
        $this->checkTransferFields($I, $flight, $schemaTransfer, 'updated');
    }

    public function saveReservation(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityReservation::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaReservation = $this->getSchemaReservation();
        $this->itinerariesProcessor->save([$schemaReservation], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $reservation = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($reservation);
        $this->checkReservationFields($I, $reservation, $schemaReservation, 'created');
    }

    public function updateReservation(\TestSymfonyGuy $I)
    {
        $schemaReservation = $this->getSchemaReservation();
        $this->createEntityReservation($I, $schemaReservation);
        $repository = $I->grabService('doctrine')->getRepository(EntityReservation::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaReservation], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $reservation = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($reservation);
        $this->checkReservationFields($I, $reservation, $schemaReservation, 'updated');
    }

    public function saveRental(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityRental::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaRental = $this->getSchemaRental();
        $this->itinerariesProcessor->save([$schemaRental], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $rental = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($rental);
        $this->checkRentalFields($I, $rental, $schemaRental, 'created');
    }

    public function updateRental(\TestSymfonyGuy $I)
    {
        $schemaRental = $this->getSchemaRental();
        $this->createEntityRental($I, $schemaRental);
        $repository = $I->grabService('doctrine')->getRepository(EntityRental::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaRental], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $rental = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($rental);
        $this->checkRentalFields($I, $rental, $schemaRental, 'updated');
    }

    public function updateRentalWithEmptyField(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityRental::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaRental = $this->getSchemaRental();
        $this->itinerariesProcessor->save([$schemaRental], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        /** @var Rental $rental */
        $rental = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($rental);
        $this->checkRentalFields($I, $rental, $schemaRental, 'created');

        $schemaRental->dropoff->openingHours = null;
        $this->itinerariesProcessor->save([$schemaRental], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals("drop-off opening hours", $rental->getDropoffhours());
    }

    public function saveEvent(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityEvent::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaEvent = $this->getSchemaEvent();
        $this->itinerariesProcessor->save([$schemaEvent], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $event = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($event);
        $this->checkEventFields($I, $event, $schemaEvent, 'created', new \DateTime('+3 days'));
    }

    public function saveEventWithEmptyEndDate(\TestSymfonyGuy $I)
    {
        $repository = $I->grabService('doctrine')->getRepository(EntityEvent::class);
        $I->assertNull($repository->findOneBy(['account' => $this->account]));

        $schemaEvent = $this->getSchemaEvent();
        $schemaEvent->endDateTime = null;
        $this->itinerariesProcessor->save([$schemaEvent], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $event = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($event);
        $this->checkEventFields($I, $event, $schemaEvent, 'created', null);
    }

    public function updateEvent(\TestSymfonyGuy $I)
    {
        $schemaEvent = $this->getSchemaEvent();
        $this->createEntityEvent($I, $schemaEvent);
        $repository = $I->grabService('doctrine')->getRepository(EntityEvent::class);
        $I->assertNotNull($repository->findOneBy(['account' => $this->account]));

        $this->itinerariesProcessor->save([$schemaEvent], $this->options);
        $I->grabService('doctrine.orm.entity_manager')->flush();
        $event = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($event);
        $this->checkEventFields($I, $event, $schemaEvent, 'updated', new \DateTime('+3 days'));
    }

    public function cancelled(\TestSymfonyGuy $I)
    {
        // could not use @dataProvider because it lacks $I
        $itineraries = [
            'flight' => [
                'schema' => $this->getSchemaFlight($I),
                'repo' => EntityTrip::class,
                'entityCreator' => [$this, "createEntityFlight"],
            ],
            'reservation' => [
                'schema' => $this->getSchemaReservation(),
                'repo' => EntityReservation::class,
                'entityCreator' => [$this, "createEntityReservation"],
            ],
            'rental' => [
                'schema' => $this->getSchemaRental(),
                'repo' => EntityRental::class,
                'entityCreator' => [$this, "createEntityRental"],
            ],
            'event' => [
                'schema' => $this->getSchemaEvent(),
                'repo' => EntityEvent::class,
                'entityCreator' => [$this, "createEntityEvent"],
            ],
        ];

        foreach ($itineraries as $type => $itinerary) {
            /** @var ItineraryEntity $entity */
            $I->wantTo("check cancelling of $type");
            $itinerary['schema']->cancelled = true;
            $entity = call_user_func($itinerary['entityCreator'], $I, $itinerary['schema']);
            $I->assertFalse($entity->getCancelled());
            $I->assertFalse($entity->getHidden());
            $repository = $I->grabService('doctrine')->getRepository($itinerary['repo']);
            $I->assertNotNull($repository->findOneBy(['account' => $this->account]));
            $this->itinerariesProcessor->save([$itinerary['schema']], $this->options);
            $em = $I->grabService('doctrine.orm.entity_manager');
            $em->flush();
            $I->assertTrue($entity->getCancelled());
            $I->assertTrue($entity->getHidden());
        }
    }

    public function cancelledSegment(\TestSymfonyGuy $I)
    {
        /** @var Flight $entity */
        $schema = $this->getSchemaFlight($I);
        $schema->segments[1]->cancelled = true;

        $this->itinerariesProcessor->save([$schema], $this->options);
        $em = $I->grabService('doctrine.orm.entity_manager');
        $em->flush();
        $repository = $I->grabService('doctrine')->getRepository(EntityTrip::class);
        /** @var Trip $entity */
        $entity = $repository->findOneBy(['account' => $this->account]);
        $I->assertNotNull($entity);

        $I->assertFalse($entity->getCancelled());
        $I->assertFalse($entity->getHidden());
        $I->assertCount(2, $entity->getSegments());
        $I->assertFalse($entity->getSegments()[0]->getHidden());
        $I->assertTrue($entity->getSegments()[1]->getHidden());

        $schema->segments[0]->cancelled = true;
        $schema->segments[1]->cancelled = false;

        $this->itinerariesProcessor->save([$schema], $this->options);
        $em->flush();

        $I->assertFalse($entity->getCancelled());
        $I->assertFalse($entity->getHidden());
        $I->assertCount(2, $entity->getSegments());
        $I->assertTrue($entity->getSegments()[0]->getHidden());
        $I->assertFalse($entity->getSegments()[1]->getHidden());
    }

    private function createEntityFlight(\TestSymfonyGuy $I, SchemaFlight $schemaFlight, ?Owner $owner = null): EntityFlight
    {
        $entityFlight = new EntityFlight();
        $entityFlight->setIssuingAirlineConfirmationNumber($schemaFlight->issuingCarrier->confirmationNumber);
        $entityFlight->setOwner($owner ?? $this->options->getOwner());
        $entityFlight->setAccount($this->options->getAccount());
        $entityFlight->setCategory(EntityFlight::CATEGORY_AIR);
        $entityFlight->setTravelerNames(array_map(function (Person $person) {
            return $person->name;
        }, $schemaFlight->getPersons()));

        // This segment will be absent from schema and shall be removed
        $segment1 = new EntityFlightSegment();
        $segment1->setTripid($entityFlight);
        $segment1->setDepcode('DC1');
        $segment1->setArrcode('AC1');
        $segment1->setDepname('departure name 1');
        $segment1->setArrname('arrival name 1');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));
        $segment1->setFlightNumber('MAFN1');

        // This segment will be matched and updated
        $segment1 = new EntityFlightSegment();
        $segment1->setTripid($entityFlight);
        $segment1->setDepcode('JFK');
        $segment1->setArrcode('LHR');
        $segment1->setDepname('departure name 2');
        $segment1->setArrname('arrival name 2');
        $segment1->setDepartureDate(new \DateTime('+3 day'));
        $segment1->setArrivalDate(new \DateTime('+4 days'));
        $segment1->setFlightNumber('MAFN2');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityFlight);
        $entityManager->flush();

        return $entityFlight;
    }

    private function createEntityTrainRide(\TestSymfonyGuy $I, SchemaTrainRide $schemaTrainRide): void
    {
        $entityTrainRide = new EntityTrainRide();
        $entityTrainRide->setCategory(Trip::CATEGORY_TRAIN);
        $entityTrainRide->setConfirmationNumber($schemaTrainRide->confirmationNumbers[1]->number);
        $entityTrainRide->setOwner($this->options->getOwner());
        $entityTrainRide->setAccount($this->options->getAccount());
        $entityTrainRide->setCategory(EntityFlight::CATEGORY_TRAIN);

        // This segment will be absent from schema and shall be removed
        $segment1 = new EntityTrainRideSegment();
        $segment1->setTripid($entityTrainRide);
        $segment1->setDepcode('DS1');
        $segment1->setArrcode('AS1');
        $segment1->setDepname('departure station 1');
        $segment1->setArrname('arrival station 1');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));
        $segment1->setFlightNumber('SN1');

        // This segment will be matched and updated
        $segment1 = new EntityTrainRideSegment();
        $segment1->setTripid($entityTrainRide);
        $segment1->setDepcode('DS2');
        $segment1->setArrcode('AS2');
        $segment1->setDepname('departure station 2');
        $segment1->setArrname('arrival station 2');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));
        $segment1->setFlightNumber('SN2');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityTrainRide);
        $entityManager->flush();
    }

    private function createEntityBusRide(\TestSymfonyGuy $I, SchemaBusRide $schemaBusRide): void
    {
        $entityBusRide = new EntityBusRide();
        $entityBusRide->setCategory(Trip::CATEGORY_BUS);
        $entityBusRide->setConfirmationNumber($schemaBusRide->confirmationNumbers[1]->number);
        $entityBusRide->setOwner($this->options->getOwner());
        $entityBusRide->setAccount($this->options->getAccount());
        $entityBusRide->setCategory(EntityFlight::CATEGORY_BUS);

        // This segment will be absent from schema and shall be removed
        $segment1 = new EntityBusRideSegment();
        $segment1->setTripid($entityBusRide);
        $segment1->setDepcode('DS1');
        $segment1->setArrcode('AS1');
        $segment1->setDepname('departure station 1');
        $segment1->setArrname('arrival station 1');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));
        $segment1->setFlightNumber('SN1');

        // This segment will be matched and updated
        $segment1 = new EntityBusRideSegment();
        $segment1->setTripid($entityBusRide);
        $segment1->setDepcode('DS2');
        $segment1->setArrcode('AS2');
        $segment1->setDepname('departure station 2');
        $segment1->setArrname('arrival station 2');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));
        $segment1->setFlightNumber('SN2');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityBusRide);
        $entityManager->flush();
    }

    private function createEntityCruise(\TestSymfonyGuy $I, SchemaCruise $schemaCruise): void
    {
        $entityCruise = new EntityCruise();
        $entityCruise->setCategory(Trip::CATEGORY_CRUISE);
        $entityCruise->setConfirmationNumber($schemaCruise->confirmationNumbers[1]->number);
        $entityCruise->setOwner($this->options->getOwner());
        $entityCruise->setAccount($this->options->getAccount());
        $entityCruise->setCategory(EntityFlight::CATEGORY_CRUISE);

        // This segment will be absent from schema and shall be removed
        $segment1 = new EntityCruiseSegment();
        $segment1->setTripid($entityCruise);
        $segment1->setDepcode('DP1');
        $segment1->setArrcode('AP1');
        $segment1->setDepname('departure port 1');
        $segment1->setArrname('arrival port 1');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));

        // This segment will be matched and updated
        $segment1 = new EntityCruiseSegment();
        $segment1->setTripid($entityCruise);
        $segment1->setDepcode('DP2');
        $segment1->setArrcode('AP2');
        $segment1->setDepname('departure port 2');
        $segment1->setArrname('arrival port 2');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityCruise);
        $entityManager->flush();
    }

    private function createEntityFerry(\TestSymfonyGuy $I, SchemaFerry $schemaFerry): void
    {
        $entityFerry = new EntityFerry();
        $entityFerry->setCategory(Trip::CATEGORY_FERRY);
        $entityFerry->setConfirmationNumber($schemaFerry->confirmationNumbers[1]->number);
        $entityFerry->setOwner($this->options->getOwner());
        $entityFerry->setAccount($this->options->getAccount());
        $entityFerry->setCategory(EntityFlight::CATEGORY_FERRY);

        // This segment will be absent from schema and shall be removed
        $segment1 = new EntityFerrySegment();
        $segment1->setTripid($entityFerry);
        $segment1->setDepcode('DP1');
        $segment1->setArrcode('AP1');
        $segment1->setDepname('departure port 1');
        $segment1->setArrname('arrival port 1');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));

        // This segment will be matched and updated
        $segment1 = new EntityFerrySegment();
        $segment1->setTripid($entityFerry);
        $segment1->setDepcode('DP2');
        $segment1->setArrcode('AP2');
        $segment1->setDepname('departure port 2');
        $segment1->setArrname('arrival port 2');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityFerry);
        $entityManager->flush();
    }

    private function createEntityTransfer(\TestSymfonyGuy $I, SchemaTransfer $schemaTransfer): void
    {
        $entityTransfer = new EntityTransfer();
        $entityTransfer->setCategory(Trip::CATEGORY_TRANSFER);
        $entityTransfer->setConfirmationNumber($schemaTransfer->confirmationNumbers[1]->number);
        $entityTransfer->setOwner($this->options->getOwner());
        $entityTransfer->setAccount($this->options->getAccount());

        // This segment will be absent from schema and shall be removed
        $segment1 = new EntityTransferSegment();
        $segment1->setTripid($entityTransfer);
        $segment1->setDepcode('JFK');
        $segment1->setArrcode('LHR');
        $segment1->setDepname('John F. Kennedy International Airport');
        $segment1->setArrname('Heathrow Airport');
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));

        // This segment will be matched and updated
        $segment1 = new EntityCruiseSegment();
        $segment1->setTripid($entityTransfer);
        $segment1->setDepcode('DME');
        $segment1->setArrcode('PEE');
        $segment1->setDepname('Domodedovo Moscow Airport');
        $segment1->setArrname("Aeroport Bol'shoye Savino");
        $segment1->setDepartureDate(new \DateTime('+1 day'));
        $segment1->setArrivalDate(new \DateTime('+2 days'));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityTransfer);
        $entityManager->flush();
    }

    private function createEntityReservation(\TestSymfonyGuy $I, SchemaReservation $schemaReservation): EntityReservation
    {
        $entityReservation = new EntityReservation();
        $entityReservation->setConfirmationNumber($schemaReservation->confirmationNumbers[1]->number);
        $entityReservation->setOwner($this->options->getOwner());
        $entityReservation->setAccount($this->options->getAccount());
        $entityReservation->setHotelname('some wrong name');
        $entityReservation->setCheckindate(new \DateTime('+1 day'));
        $entityReservation->setCheckoutdate(new \DateTime('+2 days'));
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityReservation);
        $entityManager->flush();

        return $entityReservation;
    }

    private function createEntityRental(\TestSymfonyGuy $I, SchemaRental $schemaRental): EntityRental
    {
        $entityRental = new EntityRental();
        $entityRental->setConfirmationNumber($schemaRental->confirmationNumbers[1]->number);
        $entityRental->setOwner($this->options->getOwner());
        $entityRental->setAccount($this->options->getAccount());
        $entityRental->setPickuplocation('wrong location');
        $entityRental->setDropofflocation('wrong location');
        $entityRental->setPickupdatetime(new \DateTime('+1 day'));
        $entityRental->setDropoffdatetime(new \DateTime('+2 days'));
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityRental);
        $entityManager->flush();

        return $entityRental;
    }

    private function createEntityEvent(\TestSymfonyGuy $I, SchemaEvent $schemaEvent): EntityEvent
    {
        $entityEvent = new EntityEvent();
        $entityEvent->setConfirmationNumber($schemaEvent->confirmationNumbers[1]->number);
        $entityEvent->setOwner($this->options->getOwner());
        $entityEvent->setAccount($this->options->getAccount());
        $entityEvent->setEventtype(EntityEvent::EVENT_EVENT);
        $entityEvent->setName('old event name');
        $entityEvent->setAddress('wrong location');
        $entityEvent->setStartdate(new \DateTime('today'));
        $entityEvent->setEnddate(new \DateTime('tomorrow'));
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->grabService('doctrine.orm.entity_manager');
        $entityManager->persist($entityEvent);
        $entityManager->flush();

        return $entityEvent;
    }

    private function checkFlightFields(
        \TestSymfonyGuy $I,
        EntityFlight $entityFlight,
        SchemaFlight $schemaFlight,
        string $mode
    ) {
        $this->checkGeneralItineraryFields($I, $entityFlight, $mode);
        $I->assertSame('agency_conf1', $entityFlight->getConfirmationNumber());
        $I->assertSame('Aloha Air Cargo', $entityFlight->getAirlineName());
        // Itineraries for now can only save one phone number
        $I->assertSame('IA 111', $entityFlight->getPhone());
        $I->assertSame(['First Traveler', 'Second Traveler'], $entityFlight->getTravelerNames());
        $I->assertSame('first ticket number', $entityFlight->getticketnumbers()[0]->getNumber());
        $I->assertSame(false, $entityFlight->getticketnumbers()[0]->isMasked());
        $I->assertSame('***second', $entityFlight->getticketnumbers()[1]->getNumber());
        $I->assertSame(true, $entityFlight->getticketnumbers()[1]->isMasked());
        $segments = $entityFlight->getVisibleSegments();
        // Indexes get funny here, so let's reset them
        /** @var EntityFlightSegment[] $segments */
        $segments = array_values($segments);

        $I->assertSame('MAFN2', $segments[0]->getFlightNumber());
        $I->assertSame('John F. Kennedy International Airport', $segments[0]->getDepname());
        $I->assertSame('Queens, NY 11430, USA', $segments[0]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaFlight->segments[0]->departure->localDateTime, $segments[0]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('JFK', $segments[0]->getDepcode());
        $I->assertSame('T2-1', $segments[0]->getDepartureTerminal());
        $I->assertSame('Heathrow Airport', $segments[0]->getArrname());
        $I->assertSame('Longford TW6, UK', $segments[0]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaFlight->segments[0]->arrival->localDateTime, $segments[0]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('LHR', $segments[0]->getArrcode());
        $I->assertSame('T2-2', $segments[0]->getArrivalTerminal());
        $I->assertSame('MACN2', $segments[0]->getMarketingAirlineConfirmationNumber());
        $I->assertSame('MAFN2', $segments[0]->getFlightNumber());
        $I->assertSame(['AA-222-111', 'AA-222-222'], $segments[0]->getMarketingAirlinePhoneNumbers());
        $I->assertNotNull($segments[0]->getAirline());
        $I->assertSame('American Airlines', $segments[0]->getAirline()->getName());
        $I->assertNotNull($segments[0]->getOperatingAirline());
        $I->assertSame('Aeroflot', $segments[0]->getOperatingAirline()->getName());
        $I->assertSame('OACN2', $segments[0]->getOperatingAirlineConfirmationNumber());
        $I->assertSame('OAFN2', $segments[0]->getOperatingAirlineFlightNumber());
        $I->assertSame(['AE-222-111', 'AE-222-222'], $segments[0]->getOperatingAirlinePhoneNumbers());
        $I->assertNotNull($segments[0]->getWetLeaseAirline());
        $I->assertSame('ACE Air Cargo', $segments[0]->getWetLeaseAirline()->getName());
        $I->assertSame('Boeing 737MAX 7 Passenger', $segments[0]->getAircraft()->getName());
        $I->assertSame(['seat 1', 'seat 2'], $segments[0]->getSeats());
        $I->assertSame('2000 miles', $segments[0]->getTraveledMiles());
        $I->assertSame('Economy', $segments[0]->getCabinClass());
        $I->assertSame('T', $segments[0]->getBookingClass());
        $I->assertSame('19h', $segments[0]->getDuration());
        $I->assertSame('yes', $segments[0]->getMeal());
        $I->assertSame(false, $segments[0]->isSmoking());
        $I->assertSame(2, $segments[0]->getStops());
        $I->assertSame('SchemaStatus1', $segments[0]->getParsedStatus());

        $I->assertSame('MAFN3', $segments[1]->getFlightNumber());
        $I->assertSame('Domodedovo Moscow Airport', $segments[1]->getDepname());
        $I->assertSame('Moscow Oblast, Russia', $segments[1]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaFlight->segments[1]->departure->localDateTime, $segments[1]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DME', $segments[1]->getDepcode());
        $I->assertSame('T3-1', $segments[1]->getDepartureTerminal());
        $I->assertSame('Aeroport Bol\'shoye Savino', $segments[1]->getArrname());
        // "'" from address gets sanitized
        $I->assertSame("Bol shoye Savino, Perm Krai, Russia, 614515", $segments[1]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaFlight->segments[1]->arrival->localDateTime, $segments[1]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('PEE', $segments[1]->getArrcode());
        $I->assertSame('T3-2', $segments[1]->getArrivalTerminal());
        $I->assertSame('MACN3', $segments[1]->getMarketingAirlineConfirmationNumber());
        $I->assertSame('MAFN3', $segments[1]->getFlightNumber());
        $I->assertSame(['AA-333-111', 'AA-333-222'], $segments[1]->getMarketingAirlinePhoneNumbers());
        $I->assertNotNull($segments[1]->getAirline());
        $I->assertSame('Alliance Airlines', $segments[1]->getAirline()->getName());
        $I->assertNotNull($segments[1]->getOperatingAirline());
        $I->assertSame('Ariana Afghan Airlines', $segments[1]->getOperatingAirline()->getName());
        $I->assertSame('OACN3', $segments[1]->getOperatingAirlineConfirmationNumber());
        $I->assertSame('OAFN3', $segments[1]->getOperatingAirlineFlightNumber());
        $I->assertSame(['AE-333-111', 'AE-333-222'], $segments[1]->getOperatingAirlinePhoneNumbers());
        $I->assertNotNull($segments[1]->getWetLeaseAirline());
        $I->assertSame('PopulAir', $segments[1]->getWetLeaseAirline()->getName());
        $I->assertSame('Boeing 737-300 Passenger', $segments[1]->getAircraft()->getName());
        $I->assertSame(['seat 3', 'seat 4'], $segments[1]->getSeats());
        $I->assertSame('3000 miles', $segments[1]->getTraveledMiles());
        $I->assertSame('Business', $segments[1]->getCabinClass());
        $I->assertSame('R', $segments[1]->getBookingClass());
        $I->assertSame('22h', $segments[1]->getDuration());
        $I->assertSame('yes', $segments[1]->getMeal());
        $I->assertSame(false, $segments[1]->isSmoking());
        $I->assertSame(3, $segments[1]->getStops());
        $I->assertSame('SchemaStatus2', $segments[1]->getParsedStatus());
    }

    private function checkTrainRideFields(
        \TestSymfonyGuy $I,
        EntityTrainRide $entityTrainRide,
        SchemaTrainRide $schemaTrainRide,
        string $mode
    ) {
        $this->checkGeneralItineraryFields($I, $entityTrainRide, $mode);
        $I->assertSame($schemaTrainRide->confirmationNumbers[1]->number, $entityTrainRide->getConfirmationNumber());
        $I->assertSame('first ticket number', $entityTrainRide->getticketnumbers()[0]->getNumber());
        $I->assertSame(false, $entityTrainRide->getticketnumbers()[0]->isMasked());
        $I->assertSame('***second', $entityTrainRide->getticketnumbers()[1]->getNumber());
        $I->assertSame(true, $entityTrainRide->getticketnumbers()[1]->isMasked());
        $I->assertSame(['First Traveler', 'Second Traveler'], $entityTrainRide->getTravelerNames());
        $I->assertCount(2, $entityTrainRide->getVisibleSegments());
        $segments = $entityTrainRide->getVisibleSegments();
        // Indexes get funny here, so let's reset them
        /** @var EntityTrainRideSegment[] $segments */
        $segments = array_values($segments);

        $I->assertSame('SN2', $segments[0]->getFlightNumber());
        $I->assertSame('Second Segment Departure Station', $segments[0]->getDepname());
        $I->assertSame('Queens, NY 11430, USA', $segments[0]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaTrainRide->segments[0]->departure->localDateTime, $segments[0]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DS2', $segments[0]->getDepcode());
        $I->assertSame('Second Segment Arrival Station', $segments[0]->getArrname());
        $I->assertSame('Longford TW6, UK', $segments[0]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaTrainRide->segments[0]->arrival->localDateTime, $segments[0]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('AS2', $segments[0]->getArrcode());
        $I->assertSame('second segment service', $segments[0]->getServiceName());
        $I->assertSame('2', $segments[0]->getCarNumber());
        $I->assertSame(['seat 1', 'seat 2'], $segments[0]->getSeats());
        $I->assertSame('2000 miles', $segments[0]->getTraveledMiles());
        $I->assertSame('Economy', $segments[0]->getCabinClass());
        $I->assertSame('T', $segments[0]->getBookingClass());
        $I->assertSame('19h', $segments[0]->getDuration());
        $I->assertSame('yes', $segments[0]->getMeal());
        $I->assertSame(false, $segments[0]->isSmoking());
        $I->assertSame(2, $segments[0]->getStops());

        $I->assertSame('SN3', $segments[1]->getFlightNumber());
        $I->assertSame('Third Segment Departure Station', $segments[1]->getDepname());
        $I->assertSame('Moscow Oblast, Russia', $segments[1]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaTrainRide->segments[1]->departure->localDateTime, $segments[1]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DS3', $segments[1]->getDepcode());
        $I->assertSame('Third Segment Arrival Station', $segments[1]->getArrname());
        // "'" from address gets sanitized
        $I->assertSame("Bol shoye Savino, Perm Krai, Russia, 614515", $segments[1]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaTrainRide->segments[1]->arrival->localDateTime, $segments[1]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('AS3', $segments[1]->getArrcode());
        $I->assertSame('third segment service', $segments[1]->getServiceName());
        $I->assertSame('3', $segments[1]->getCarNumber());
        $I->assertSame(['seat 3', 'seat 4'], $segments[1]->getSeats());
        $I->assertSame('3000 miles', $segments[1]->getTraveledMiles());
        $I->assertSame('Business', $segments[1]->getCabinClass());
        $I->assertSame('R', $segments[1]->getBookingClass());
        $I->assertSame('19h', $segments[1]->getDuration());
        $I->assertSame('yes', $segments[1]->getMeal());
        $I->assertSame(false, $segments[1]->isSmoking());
        $I->assertSame(3, $segments[1]->getStops());
    }

    private function checkBusRideFields(
        \TestSymfonyGuy $I,
        EntityBusRide $entityBusRide,
        SchemaBusRide $schemaBusRide,
        string $mode
    ) {
        $this->checkGeneralItineraryFields($I, $entityBusRide, $mode);
        $I->assertSame($schemaBusRide->confirmationNumbers[1]->number, $entityBusRide->getConfirmationNumber());
        $I->assertSame($schemaBusRide->confirmationNumbers[0]->number, $entityBusRide->getProviderConfirmationNumbers()[0]);
        $I->assertSame($schemaBusRide->confirmationNumbers[1]->number, $entityBusRide->getProviderConfirmationNumbers()[1]);
        $I->assertSame('first ticket number', $entityBusRide->getticketnumbers()[0]->getNumber());
        $I->assertSame(false, $entityBusRide->getticketnumbers()[0]->isMasked());
        $I->assertSame('***second', $entityBusRide->getticketnumbers()[1]->getNumber());
        $I->assertSame(true, $entityBusRide->getticketnumbers()[1]->isMasked());
        $I->assertSame(['First Traveler', 'Second Traveler'], $entityBusRide->getTravelerNames());
        $I->assertCount(2, $entityBusRide->getVisibleSegments());
        $segments = $entityBusRide->getVisibleSegments();
        // Indexes get funny here, so let's reset them
        /** @var EntityBusRideSegment[] $segments */
        $segments = array_values($segments);

        $I->assertSame('SN2', $segments[0]->getFlightNumber());
        $I->assertSame('Second Segment Departure Station', $segments[0]->getDepname());
        $I->assertSame('Queens, NY 11430, USA', $segments[0]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaBusRide->segments[0]->departure->localDateTime, $segments[0]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DS2', $segments[0]->getDepcode());
        $I->assertSame('Second Segment Arrival Station', $segments[0]->getArrname());
        $I->assertSame('Longford TW6, UK', $segments[0]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaBusRide->segments[0]->arrival->localDateTime, $segments[0]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('AS2', $segments[0]->getArrcode());
        $I->assertSame(['seat 1', 'seat 2'], $segments[0]->getSeats());
        $I->assertSame('2000 miles', $segments[0]->getTraveledMiles());
        $I->assertSame('Economy', $segments[0]->getCabinClass());
        $I->assertSame('T', $segments[0]->getBookingClass());
        $I->assertSame('19h', $segments[0]->getDuration());
        $I->assertSame('yes', $segments[0]->getMeal());
        $I->assertSame(false, $segments[0]->isSmoking());
        $I->assertSame(2, $segments[0]->getStops());

        $I->assertSame('SN3', $segments[1]->getFlightNumber());
        $I->assertSame('Third Segment Departure Station', $segments[1]->getDepname());
        $I->assertSame('Moscow Oblast, Russia', $segments[1]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaBusRide->segments[1]->departure->localDateTime, $segments[1]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DS3', $segments[1]->getDepcode());
        $I->assertSame('Third Segment Arrival Station', $segments[1]->getArrname());
        // "'" from address gets sanitized
        $I->assertSame('Bol shoye Savino, Perm Krai, Russia, 614515', $segments[1]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaBusRide->segments[1]->arrival->localDateTime, $segments[1]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('AS3', $segments[1]->getArrcode());
        $I->assertSame(['seat 3', 'seat 4'], $segments[1]->getSeats());
        $I->assertSame('3000 miles', $segments[1]->getTraveledMiles());
        $I->assertSame('Business', $segments[1]->getCabinClass());
        $I->assertSame('R', $segments[1]->getBookingClass());
        $I->assertSame('19h', $segments[1]->getDuration());
        $I->assertSame('yes', $segments[1]->getMeal());
        $I->assertSame(false, $segments[1]->isSmoking());
        $I->assertSame(3, $segments[1]->getStops());
    }

    private function checkCruiseFields(
        \TestSymfonyGuy $I,
        EntityCruise $entityCruise,
        SchemaCruise $schemaCruise,
        string $mode
    ) {
        $this->checkGeneralItineraryFields($I, $entityCruise, $mode);
        $I->assertSame($schemaCruise->confirmationNumbers[1]->number, $entityCruise->getConfirmationNumber());
        $I->assertSame($schemaCruise->confirmationNumbers[0]->number, $entityCruise->getProviderConfirmationNumbers()[0]);
        $I->assertSame($schemaCruise->confirmationNumbers[1]->number, $entityCruise->getProviderConfirmationNumbers()[1]);
        $I->assertSame(['First Traveler', 'Second Traveler'], $entityCruise->getTravelerNames());
        $I->assertSame('Long cruise', $entityCruise->getCruiseName());
        $I->assertSame('Regular', $entityCruise->getShipCabinClass());
        $I->assertSame('3', $entityCruise->getDeck());
        $I->assertSame('342', $entityCruise->getCabinNumber());
        $I->assertSame('Disney Dream', $entityCruise->getShipName());
        $I->assertSame('SHCD', $entityCruise->getShipCode());
        $I->assertCount(2, $entityCruise->getVisibleSegments());
        $segments = $entityCruise->getVisibleSegments();
        // Indexes get funny here, so let's reset them
        /** @var EntityCruiseSegment[] $segments */
        $segments = array_values($segments);

        $I->assertSame('Second Segment Departure Port', $segments[0]->getDepname());
        $I->assertSame('Queens, NY 11430, USA', $segments[0]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaCruise->segments[0]->departure->localDateTime, $segments[0]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DP2', $segments[0]->getDepcode());
        $I->assertSame('Second Segment Arrival Port', $segments[0]->getArrname());
        $I->assertSame('Longford TW6, UK', $segments[0]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaCruise->segments[0]->arrival->localDateTime, $segments[0]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('AP2', $segments[0]->getArrcode());

        $I->assertSame('Third Segment Departure Port', $segments[1]->getDepname());
        $I->assertSame('Moscow Oblast, Russia', $segments[1]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaCruise->segments[1]->departure->localDateTime, $segments[1]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DP3', $segments[1]->getDepcode());
        $I->assertSame('Third Segment Arrival Port', $segments[1]->getArrname());
        // "'" from address gets sanitized
        $I->assertSame('Bol shoye Savino, Perm Krai, Russia, 614515', $segments[1]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaCruise->segments[1]->arrival->localDateTime, $segments[1]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('AP3', $segments[1]->getArrcode());
    }

    private function checkFerryFields(
        \TestSymfonyGuy $I,
        EntityFerry $entity,
        SchemaFerry $schema,
        string $mode
    ) {
        $this->checkGeneralItineraryFields($I, $entity, $mode);
        $I->assertSame($schema->confirmationNumbers[1]->number, $entity->getConfirmationNumber());
        $I->assertSame($schema->confirmationNumbers[0]->number, $entity->getProviderConfirmationNumbers()[0]);
        $I->assertSame($schema->confirmationNumbers[1]->number, $entity->getProviderConfirmationNumbers()[1]);
        $I->assertSame(['First Traveler', 'Second Traveler'], $entity->getTravelerNames());
        $I->assertCount(2, $entity->getVisibleSegments());
        $segments = $entity->getVisibleSegments();
        // Indexes get funny here, so let's reset them
        /** @var EntityCruiseSegment[] $segments */
        $segments = array_values($segments);

        $I->assertSame('Second Segment Departure Port', $segments[0]->getDepname());
        $I->assertSame('Queens, NY 11430, USA', $segments[0]->getDepgeotagid()->getAddress());
        $I->assertEquals($schema->segments[0]->departure->localDateTime, $segments[0]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DP2', $segments[0]->getDepcode());
        $I->assertSame('Second Segment Arrival Port', $segments[0]->getArrname());
        $I->assertSame('Longford TW6, UK', $segments[0]->getArrgeotagid()->getAddress());
        $I->assertEquals($schema->segments[0]->arrival->localDateTime, $segments[0]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('AP2', $segments[0]->getArrcode());

        $I->assertSame('Third Segment Departure Port', $segments[1]->getDepname());
        $I->assertSame('Moscow Oblast, Russia', $segments[1]->getDepgeotagid()->getAddress());
        $I->assertEquals($schema->segments[1]->departure->localDateTime, $segments[1]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DP3', $segments[1]->getDepcode());
        $I->assertSame('Third Segment Arrival Port', $segments[1]->getArrname());
        // "'" from address gets sanitized
        $I->assertSame('Bol shoye Savino, Perm Krai, Russia, 614515', $segments[1]->getArrgeotagid()->getAddress());
        $I->assertEquals($schema->segments[1]->arrival->localDateTime, $segments[1]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('AP3', $segments[1]->getArrcode());
    }

    private function checkTransferFields(
        \TestSymfonyGuy $I,
        EntityTransfer $entityTransfer,
        SchemaTransfer $schemaTransfer,
        string $mode
    ) {
        $this->checkGeneralItineraryFields($I, $entityTransfer, $mode);
        $I->assertSame($schemaTransfer->confirmationNumbers[1]->number, $entityTransfer->getConfirmationNumber());
        $I->assertSame($schemaTransfer->confirmationNumbers[0]->number, $entityTransfer->getProviderConfirmationNumbers()[0]);
        $I->assertSame($schemaTransfer->confirmationNumbers[1]->number, $entityTransfer->getProviderConfirmationNumbers()[1]);
        $I->assertSame(['First Traveler', 'Second Traveler'], $entityTransfer->getTravelerNames());
        $I->assertCount(2, $entityTransfer->getVisibleSegments());
        $segments = $entityTransfer->getVisibleSegments();
        // Indexes get funny here, so let's reset them
        /** @var EntityTransferSegment[] $segments */
        $segments = array_values($segments);

        $I->assertSame('John F. Kennedy International Airport', $segments[0]->getDepname());
        $I->assertSame('Queens, NY 11430, USA', $segments[0]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaTransfer->segments[0]->departure->localDateTime, $segments[0]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('JFK', $segments[0]->getDepcode());
        $I->assertSame('Heathrow Airport', $segments[0]->getArrname());
        $I->assertSame('Longford TW6, UK', $segments[0]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaTransfer->segments[0]->arrival->localDateTime, $segments[0]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('LHR', $segments[0]->getArrcode());
        $I->assertSame(2, $segments[0]->getAdultsCount());
        $I->assertSame(2, $segments[0]->getKidsCount());
        $I->assertSame('2000 miles', $segments[0]->getTraveledMiles());
        $I->assertSame('19h', $segments[0]->getDuration());

        $I->assertSame('Domodedovo Moscow Airport', $segments[1]->getDepname());
        $I->assertSame('Moscow Oblast, Russia', $segments[1]->getDepgeotagid()->getAddress());
        $I->assertEquals($schemaTransfer->segments[1]->departure->localDateTime, $segments[1]->getDepartureDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('DME', $segments[1]->getDepcode());
        $I->assertSame("Aeroport Bol'shoye Savino", $segments[1]->getArrname());
        // "'" from address gets sanitized
        $I->assertSame("Bol shoye Savino, Perm Krai, Russia, 614515", $segments[1]->getArrgeotagid()->getAddress());
        $I->assertEquals($schemaTransfer->segments[1]->arrival->localDateTime, $segments[1]->getArrivalDate()->format('Y-m-dTH:i:s'));
        $I->assertSame('PEE', $segments[1]->getArrcode());
        $I->assertSame(3, $segments[1]->getAdultsCount());
        $I->assertSame(3, $segments[1]->getKidsCount());
        $I->assertSame('3000 miles', $segments[1]->getTraveledMiles());
        $I->assertSame('22h', $segments[1]->getDuration());
    }

    private function checkReservationFields(
        \TestSymfonyGuy $I,
        EntityReservation $entityReservation,
        SchemaReservation $schemaReservation,
        string $mode
    ) {
        $this->checkGeneralItineraryFields($I, $entityReservation, $mode);
        $I->assertSame($schemaReservation->confirmationNumbers[1]->number, $entityReservation->getConfirmationNumber());
        $I->assertSame($schemaReservation->confirmationNumbers[0]->number, $entityReservation->getProviderConfirmationNumbers()[0]);
        $I->assertSame($schemaReservation->confirmationNumbers[1]->number, $entityReservation->getProviderConfirmationNumbers()[1]);
        $I->assertSame('Courtyard Santa Clarita Valencia', $entityReservation->getHotelname());
        $I->assertSame('28523 Westinghouse Place Valencia California 91355 USA', $entityReservation->getAddress());
        $I->assertEquals($schemaReservation->checkInDate, $entityReservation->getCheckindate()->format('Y-m-dTH:i:s'));
        $I->assertEquals($schemaReservation->checkOutDate, $entityReservation->getCheckoutdate()->format('Y-m-dTH:i:s'));
        $I->assertEquals(['+1-661-257-3220'], $entityReservation->getPhones());
        $I->assertSame('+44 161 999 8888', $entityReservation->getFax());
        $I->assertSame(2, $entityReservation->getGuestCount());
        $I->assertSame(2, $entityReservation->getKidsCount());
        $I->assertSame(2, $entityReservation->getRoomCount());
        $I->assertSame('Please note that a change in the length or dates may result in a rate change...', $entityReservation->getCancellationPolicy());
        $I->assertSame('1 KING BED NONSMOKING', $entityReservation->getRooms()[0]->getShortDescription());
        $I->assertSame('One big bed for a crowned man, but no smoking!', $entityReservation->getRooms()[0]->getLongDescription());
        $I->assertSame('1 KING BED NOSMOKING', $entityReservation->getRooms()[0]->getRateDescription());
        $I->assertSame('130 USD/night', $entityReservation->getRooms()[0]->getRate());
        $I->assertSame('1 PRINCESS BED NONSMOKING', $entityReservation->getRooms()[1]->getShortDescription());
        $I->assertSame('One average bed for a daughter of a crowned man, but no smoking either!', $entityReservation->getRooms()[1]->getLongDescription());
        $I->assertSame('1 PRINCESS BED NOSMOKING', $entityReservation->getRooms()[1]->getRateDescription());
        $I->assertSame('130 USD/night', $entityReservation->getRooms()[1]->getRate());
        $I->assertSame(['First Guest', 'Second Guest'], $entityReservation->getTravelerNames());
        $I->assertSame($schemaReservation->cancellationDeadline, $entityReservation->getCancellationDeadline()->format("Y-m-d\TH:i:s"));
        $I->assertSame(2, $entityReservation->getFreeNights());
    }

    private function checkRentalFields(
        \TestSymfonyGuy $I,
        EntityRental $entityRental,
        SchemaRental $schemaRental,
        string $mode
    ) {
        $this->checkGeneralItineraryFields($I, $entityRental, $mode);
        $I->assertSame($schemaRental->confirmationNumbers[1]->number, $entityRental->getConfirmationNumber());
        $I->assertSame($schemaRental->confirmationNumbers[0]->number, $entityRental->getProviderConfirmationNumbers()[0]);
        $I->assertSame($schemaRental->confirmationNumbers[1]->number, $entityRental->getProviderConfirmationNumbers()[1]);
        $I->assertSame('999 9th St NW, Washington, DC 20001, USA', $entityRental->getPickuplocation());
        $I->assertSame('50 Massachusetts Ave NE, Washington, DC 20002, USA', $entityRental->getDropofflocation());
        $I->assertSame('pickup phone', $entityRental->getPickupphone());
        $I->assertSame('drop-off phone', $entityRental->getDropoffphone());
        $I->assertEquals($schemaRental->pickup->localDateTime, $entityRental->getPickupdatetime()->format('Y-m-dTH:i:s'));
        $I->assertEquals($schemaRental->dropoff->localDateTime, $entityRental->getDropoffdatetime()->format('Y-m-dTH:i:s'));
        $I->assertSame('pickup opening hours', $entityRental->getPickuphours());
        $I->assertSame('drop-off opening hours', $entityRental->getDropoffhours());
        $I->assertSame('rental company name', $entityRental->getRentalCompanyName());
        $I->assertSame('car_image_url', $entityRental->getCarImageUrl());
        $I->assertSame('car type', $entityRental->getCarType());
        $I->assertSame('car model', $entityRental->getCarModel());
        $I->assertSame('pickup fax', $entityRental->getPickUpFax());
        $I->assertSame('drop-off fax', $entityRental->getDropOffFax());
    }

    private function checkEventFields(
        \TestSymfonyGuy $I,
        EntityEvent $entityEvent,
        SchemaEvent $schemaEvent,
        string $mode,
        ?\DateTime $expectedEndDate
    ) {
        $this->checkGeneralItineraryFields($I, $entityEvent, $mode);
        $I->assertSame($schemaEvent->confirmationNumbers[1]->number, $entityEvent->getConfirmationNumber());
        $I->assertSame($schemaEvent->confirmationNumbers[0]->number, $entityEvent->getProviderConfirmationNumbers()[0]);
        $I->assertSame($schemaEvent->confirmationNumbers[1]->number, $entityEvent->getProviderConfirmationNumbers()[1]);
        $I->assertSame('new event name', $entityEvent->getName());
        $I->assertSame('999 9th St (NW), Washington, DC 20001, USA', $entityEvent->getAddress());
        $I->assertSame('new event phone', $entityEvent->getPhone());
        $I->assertSame('new event fax', $entityEvent->getFax());
        $I->assertSame(Restaurant::EVENT_EVENT, $entityEvent->getEventtype());
        $I->assertEquals($schemaEvent->startDateTime, $entityEvent->getStartdate()->format('Y-m-dTH:i:s'));
        $I->assertEqualsWithDelta($expectedEndDate, $entityEvent->getEnddate(), 5, '');
        $I->assertSame(['Guest One', 'Guest Two'], $entityEvent->getTravelerNames());
        $I->assertSame(2, $entityEvent->getGuestCount());
    }

    private function checkGeneralItineraryFields(
        \TestSymfonyGuy $I,
        EntityItinerary $entityItinerary,
        string $mode
    ): void {
        if ('updated' === $mode) {
            // Not much meaning in switching providers on update
            $I->assertSame(null, $entityItinerary->getTravelAgency());
            $I->assertSame($this->account->getProviderid(), $entityItinerary->getProvider());
            $I->assertEqualsWithDelta(new \DateTime(), $entityItinerary->getCreateDate(), 5, '');
            $I->assertEqualsWithDelta(new \DateTime(), $entityItinerary->getUpdateDate(), 5, '');
            $I->assertEquals($this->owner->getUser()->getUserid(), $entityItinerary->getOwner()->getUser()->getUserid());
        } else {
            $I->assertTrue($this->owner->isSame($entityItinerary->getOwner()));
            $I->assertSame($this->travelAgency, $entityItinerary->getTravelAgency());
            $I->assertSame($this->provider, $entityItinerary->getRealProvider());
            $I->assertNull($entityItinerary->getUpdateDate());
        }
        $I->assertSame(['agency_conf1', 'agency_conf2'], $entityItinerary->getTravelAgencyConfirmationNumbers());
        $I->assertSame(['Agency Account Number 1', 'Agency****2'], $entityItinerary->getTravelAgencyParsedAccountNumbers());
        $I->assertSame(['Agency Account Number 1', 'Agency****2'], $entityItinerary->getTravelAgencyParsedAccountNumbers());
        $I->assertSame('agency earned rewards', $entityItinerary->getPricingInfo()->getTravelAgencyEarnedAwards());
        $I->assertSame(['agency_conf1', 'agency_conf2'], $entityItinerary->getTravelAgencyConfirmationNumbers());
        $I->assertSame(['777-777', '888-888'], $entityItinerary->getTravelAgencyPhones());
        $I->assertEqualsWithDelta(new \DateTime('tomorrow'), $entityItinerary->getReservationDate(), 1, '');
        $I->assertSame(['Account Number 1', '****ber 2'], $entityItinerary->getParsedAccountNumbers());
        $I->assertSame('earned rewards', $entityItinerary->getPricingInfo()->getEarnedAwards());
        $I->assertSame(721.7, $entityItinerary->getPricingInfo()->getTotal());
        $I->assertSame(600.0, $entityItinerary->getPricingInfo()->getCost());
        $I->assertSame(100.0, $entityItinerary->getPricingInfo()->getDiscount());
        $I->assertSame(null, $entityItinerary->getPricingInfo()->getSpentAwards());
        $I->assertSame('RUB', $entityItinerary->getPricingInfo()->getCurrencyCode());
        $I->assertSame('fee1', $entityItinerary->getPricingInfo()->getFees()[0]->getName());
        $I->assertSame('fee2', $entityItinerary->getPricingInfo()->getFees()[1]->getName());
        $I->assertSame(10., $entityItinerary->getPricingInfo()->getFees()[0]->getCharge());
        $I->assertSame(20., $entityItinerary->getPricingInfo()->getFees()[1]->getCharge());
    }

    private function getSchemaFlight(\TestSymfonyGuy $I): SchemaFlight
    {
        $flight = new SchemaFlight();
        $this->setGeneralSchemaItineraryFields($flight);
        $flight->travelers = [new Person(), new Person()];
        $flight->travelers[0]->name = 'First Traveler';
        $flight->travelers[0]->full = true;
        $flight->travelers[1]->name = 'Second Traveler';
        $flight->travelers[1]->full = true;
        $flight->issuingCarrier = new IssuingCarrier();
        $flight->issuingCarrier->confirmationNumber = 'IACN_' . $I->grabRandomString(6);
        $flight->issuingCarrier->airline = new Airline();
        $flight->issuingCarrier->airline->name = 'Aloha Air Cargo';
        $flight->issuingCarrier->airline->icao = 'AAH';
        $flight->issuingCarrier->airline->iata = 'KH';
        $flight->issuingCarrier->phoneNumbers = [new PhoneNumber(), new PhoneNumber()];
        $flight->issuingCarrier->phoneNumbers[0]->number = 'IA 111';
        $flight->issuingCarrier->phoneNumbers[0]->description = 'issuing carrier first number';
        $flight->issuingCarrier->phoneNumbers[1]->number = 'IA 222';
        $flight->issuingCarrier->phoneNumbers[1]->description = 'issuing carrier second number';
        $flight->issuingCarrier->ticketNumbers = [new ParsedNumber(), new ParsedNumber()];
        $flight->issuingCarrier->ticketNumbers[0]->number = 'first ticket number';
        $flight->issuingCarrier->ticketNumbers[0]->masked = false;
        $flight->issuingCarrier->ticketNumbers[1]->number = '***second';
        $flight->issuingCarrier->ticketNumbers[1]->masked = true;
        $flight->segments = [new SchemaFlightSegment(), new SchemaFlightSegment()];

        // This segment will be matched and updated
        $flight->segments[0]->departure = new TripLocation();
        $flight->segments[0]->departure->name = "John F. Kennedy International Airport";
        $flight->segments[0]->departure->address = new Address();
        $flight->segments[0]->departure->address->text = "Queens, NY 11430, USA";
        $flight->segments[0]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+5 days'));
        $flight->segments[0]->departure->airportCode = 'JFK';
        $flight->segments[0]->departure->terminal = 'T2-1';
        $flight->segments[0]->arrival = new TripLocation();
        $flight->segments[0]->arrival->name = "Heathrow Airport";
        $flight->segments[0]->arrival->address = new Address();
        $flight->segments[0]->arrival->address->text = "Longford TW6, UK";
        $flight->segments[0]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+6 days'));
        $flight->segments[0]->arrival->airportCode = 'LHR';
        $flight->segments[0]->arrival->terminal = 'T2-2';
        $flight->segments[0]->marketingCarrier = new MarketingCarrier();
        $flight->segments[0]->marketingCarrier->confirmationNumber = 'MACN2';
        $flight->segments[0]->marketingCarrier->flightNumber = 'MAFN2';
        $flight->segments[0]->marketingCarrier->phoneNumbers = [new PhoneNumber(), new PhoneNumber()];
        $flight->segments[0]->marketingCarrier->phoneNumbers[0]->number = 'AA-222-111';
        $flight->segments[0]->marketingCarrier->phoneNumbers[0]->description = 'second segment marketing airline first number';
        $flight->segments[0]->marketingCarrier->phoneNumbers[1]->number = 'AA-222-222';
        $flight->segments[0]->marketingCarrier->phoneNumbers[1]->description = 'second segment marketing airline second number';
        $flight->segments[0]->marketingCarrier->airline = new Airline();
        $flight->segments[0]->marketingCarrier->airline->name = 'American Airlines';
        $flight->segments[0]->marketingCarrier->airline->iata = 'AA';
        $flight->segments[0]->marketingCarrier->airline->icao = 'AAL';
        $flight->segments[0]->marketingCarrier->isCodeshare = true;
        $flight->segments[0]->operatingCarrier = new OperatingCarrier();
        $flight->segments[0]->operatingCarrier->airline = new Airline();
        $flight->segments[0]->operatingCarrier->airline->name = 'Aeroflot';
        $flight->segments[0]->operatingCarrier->airline->icao = 'AFL';
        $flight->segments[0]->operatingCarrier->airline->iata = 'SU';
        $flight->segments[0]->operatingCarrier->flightNumber = 'OAFN2';
        $flight->segments[0]->operatingCarrier->confirmationNumber = 'OACN2';
        $flight->segments[0]->operatingCarrier->phoneNumbers = [new PhoneNumber(), new PhoneNumber()];
        $flight->segments[0]->operatingCarrier->phoneNumbers[0]->number = 'AE-222-111';
        $flight->segments[0]->operatingCarrier->phoneNumbers[0]->description = 'second segment operating airline first number';
        $flight->segments[0]->operatingCarrier->phoneNumbers[1]->number = 'AE-222-222';
        $flight->segments[0]->operatingCarrier->phoneNumbers[1]->description = 'second segment operating airline second number';
        $flight->segments[0]->wetleaseCarrier = new Airline();
        $flight->segments[0]->wetleaseCarrier->icao = 'AER';
        $flight->segments[0]->wetleaseCarrier->iata = 'KO';
        $flight->segments[0]->wetleaseCarrier->name = 'Alaska Central Express';
        $flight->segments[0]->seats = ['seat 1', 'seat 2'];
        $flight->segments[0]->aircraft = new Aircraft();
        $flight->segments[0]->aircraft->iataCode = '7M7';
        $flight->segments[0]->aircraft->name = 'Boeing 737MAX 7 Passenger';
        $flight->segments[0]->aircraft->turboProp = false;
        $flight->segments[0]->aircraft->jet = true;
        $flight->segments[0]->aircraft->wideBody = false;
        $flight->segments[0]->aircraft->regional = false;
        $flight->segments[0]->traveledMiles = '2000 miles';
        $flight->segments[0]->cabin = 'Economy';
        $flight->segments[0]->bookingCode = 'T';
        $flight->segments[0]->duration = '19h';
        $flight->segments[0]->meal = 'yes';
        $flight->segments[0]->smoking = false;
        $flight->segments[0]->stops = 2;
        $flight->segments[0]->status = 'SchemaStatus1';

        // This segment is new
        $flight->segments[1]->departure = new TripLocation();
        $flight->segments[1]->departure->name = "Domodedovo Moscow Airport";
        $flight->segments[1]->departure->address = new Address();
        $flight->segments[1]->departure->address->text = "Moscow Oblast, Russia";
        $flight->segments[1]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+7 days'));
        $flight->segments[1]->departure->airportCode = 'DME';
        $flight->segments[1]->departure->terminal = 'T3-1';
        $flight->segments[1]->arrival = new TripLocation();
        $flight->segments[1]->arrival->name = "Aeroport Bol'shoye Savino";
        $flight->segments[1]->arrival->address = new Address();
        $flight->segments[1]->arrival->address->text = "Bol'shoye Savino, Perm Krai, Russia, 614515";
        $flight->segments[1]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+8 days'));
        $flight->segments[1]->arrival->airportCode = 'PEE';
        $flight->segments[1]->arrival->terminal = 'T3-2';
        $flight->segments[1]->marketingCarrier = new MarketingCarrier();
        $flight->segments[1]->marketingCarrier->confirmationNumber = 'MACN3';
        $flight->segments[1]->marketingCarrier->flightNumber = 'MAFN3';
        $flight->segments[1]->marketingCarrier->phoneNumbers = [new PhoneNumber(), new PhoneNumber()];
        $flight->segments[1]->marketingCarrier->phoneNumbers[0]->number = 'AA-333-111';
        $flight->segments[1]->marketingCarrier->phoneNumbers[0]->description = 'third segment marketing airline first number';
        $flight->segments[1]->marketingCarrier->phoneNumbers[1]->number = 'AA-333-222';
        $flight->segments[1]->marketingCarrier->phoneNumbers[1]->description = 'third segment marketing airline second number';
        $flight->segments[1]->marketingCarrier->airline = new Airline();
        $flight->segments[1]->marketingCarrier->airline->name = 'Alliance Airlines';
        $flight->segments[1]->marketingCarrier->airline->iata = 'QQ';
        $flight->segments[1]->marketingCarrier->airline->icao = 'UTY';
        $flight->segments[1]->marketingCarrier->isCodeshare = true;
        $flight->segments[1]->operatingCarrier = new OperatingCarrier();
        $flight->segments[1]->operatingCarrier->airline = new Airline();
        $flight->segments[1]->operatingCarrier->airline->name = 'Ariana Afghan Airlines';
        $flight->segments[1]->operatingCarrier->airline->iata = 'FG';
        $flight->segments[1]->operatingCarrier->airline->icao = 'AFG';
        $flight->segments[1]->operatingCarrier->flightNumber = 'OAFN3';
        $flight->segments[1]->operatingCarrier->confirmationNumber = 'OACN3';
        $flight->segments[1]->operatingCarrier->phoneNumbers = [new PhoneNumber(), new PhoneNumber()];
        $flight->segments[1]->operatingCarrier->phoneNumbers[0]->number = 'AE-333-111';
        $flight->segments[1]->operatingCarrier->phoneNumbers[0]->description = 'third segment operating airline first number';
        $flight->segments[1]->operatingCarrier->phoneNumbers[1]->number = 'AE-333-222';
        $flight->segments[1]->operatingCarrier->phoneNumbers[1]->description = 'third segment operating airline second number';
        $flight->segments[1]->wetleaseCarrier = new Airline();
        $flight->segments[1]->wetleaseCarrier->iata = 'HP';
        $flight->segments[1]->wetleaseCarrier->icao = 'APF';
        $flight->segments[1]->wetleaseCarrier->name = 'Boeing 737-300 Passenger';
        $flight->segments[1]->seats = ['seat 3', 'seat 4'];
        $flight->segments[1]->aircraft = new Aircraft();
        $flight->segments[1]->aircraft->iataCode = '733';
        $flight->segments[1]->aircraft->name = 'Airbus A318';
        $flight->segments[1]->aircraft->turboProp = false;
        $flight->segments[1]->aircraft->jet = true;
        $flight->segments[1]->aircraft->wideBody = false;
        $flight->segments[1]->aircraft->regional = false;
        $flight->segments[1]->traveledMiles = '3000 miles';
        $flight->segments[1]->cabin = 'Business';
        $flight->segments[1]->bookingCode = 'R';
        $flight->segments[1]->duration = '22h';
        $flight->segments[1]->meal = 'yes';
        $flight->segments[1]->smoking = false;
        $flight->segments[1]->stops = 3;
        $flight->segments[1]->status = 'SchemaStatus2';

        return $flight;
    }

    private function getSchemaTrainRide(): SchemaTrainRide
    {
        $trainRide = new SchemaTrainRide();
        $this->setGeneralSchemaItineraryFields($trainRide);
        $trainRide->confirmationNumbers = [new ConfNo(), new ConfNo()];
        $trainRide->confirmationNumbers[0]->number = bin2hex(random_bytes(10));
        $trainRide->confirmationNumbers[1]->number = bin2hex(random_bytes(10));
        $trainRide->confirmationNumbers[1]->isPrimary = true;
        $trainRide->ticketNumbers = [new ParsedNumber(), new ParsedNumber()];
        $trainRide->ticketNumbers[0]->number = 'first ticket number';
        $trainRide->ticketNumbers[0]->masked = false;
        $trainRide->ticketNumbers[1]->number = '***second';
        $trainRide->ticketNumbers[1]->masked = true;
        $trainRide->travelers = [new Person(), new Person()];
        $trainRide->travelers[0]->name = 'First Traveler';
        $trainRide->travelers[0]->full = true;
        $trainRide->travelers[1]->name = 'Second Traveler';
        $trainRide->travelers[1]->full = true;
        $trainRide->segments = [new SchemaTrainRideSegment(), new SchemaTrainRideSegment()];

        // This segment will be matched and updated
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
        // Ignoring vehicle info for now
        $trainRide->segments[0]->trainInfo = new Vehicle();
        $trainRide->segments[0]->trainInfo->type = 'train2';
        $trainRide->segments[0]->trainInfo->model = 'train model 2';
        $trainRide->segments[0]->car = '2';
        $trainRide->segments[0]->seats = ['seat 1', 'seat 2'];
        $trainRide->segments[0]->traveledMiles = '2000 miles';
        $trainRide->segments[0]->cabin = 'Economy';
        $trainRide->segments[0]->bookingCode = 'T';
        $trainRide->segments[0]->duration = '19h';
        $trainRide->segments[0]->meal = 'yes';
        $trainRide->segments[0]->smoking = false;
        $trainRide->segments[0]->stops = 2;

        // This segment will be matched and updated
        $trainRide->segments[1]->scheduleNumber = 'SN3';
        $trainRide->segments[1]->departure = new TransportLocation();
        $trainRide->segments[1]->departure->name = "Third Segment Departure Station";
        $trainRide->segments[1]->departure->address = new Address();
        $trainRide->segments[1]->departure->address->text = "Moscow Oblast, Russia";
        $trainRide->segments[1]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+7 days'));
        $trainRide->segments[1]->departure->stationCode = 'DS3';
        $trainRide->segments[1]->arrival = new TransportLocation();
        $trainRide->segments[1]->arrival->name = "Third Segment Arrival Station";
        $trainRide->segments[1]->arrival->address = new Address();
        $trainRide->segments[1]->arrival->address->text = "Bol'shoye Savino, Perm Krai, Russia, 614515";
        $trainRide->segments[1]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+8 days'));
        $trainRide->segments[1]->arrival->stationCode = 'AS3';
        $trainRide->segments[1]->serviceName = 'third segment service';
        // Ignoring vehicle info for now
        $trainRide->segments[1]->trainInfo = new Vehicle();
        $trainRide->segments[1]->trainInfo->type = 'train3';
        $trainRide->segments[1]->trainInfo->model = 'train model 3';
        $trainRide->segments[1]->car = '3';
        $trainRide->segments[1]->seats = ['seat 3', 'seat 4'];
        $trainRide->segments[1]->traveledMiles = '3000 miles';
        $trainRide->segments[1]->cabin = 'Business';
        $trainRide->segments[1]->bookingCode = 'R';
        $trainRide->segments[1]->duration = '19h';
        $trainRide->segments[1]->meal = 'yes';
        $trainRide->segments[1]->smoking = false;
        $trainRide->segments[1]->stops = 3;

        return $trainRide;
    }

    private function getSchemaBusRide(): SchemaBusRide
    {
        $busRide = new SchemaBusRide();
        $this->setGeneralSchemaItineraryFields($busRide);
        $busRide->confirmationNumbers = [new ConfNo(), new ConfNo()];
        $busRide->confirmationNumbers[0]->number = bin2hex(random_bytes(10));
        $busRide->confirmationNumbers[1]->number = bin2hex(random_bytes(10));
        $busRide->confirmationNumbers[1]->isPrimary = true;
        $busRide->ticketNumbers = [new ParsedNumber(), new ParsedNumber()];
        $busRide->ticketNumbers[0]->number = 'first ticket number';
        $busRide->ticketNumbers[0]->masked = false;
        $busRide->ticketNumbers[1]->number = '***second';
        $busRide->ticketNumbers[1]->masked = true;
        $busRide->travelers = [new Person(), new Person()];
        $busRide->travelers[0]->name = 'First Traveler';
        $busRide->travelers[0]->full = true;
        $busRide->travelers[1]->name = 'Second Traveler';
        $busRide->travelers[1]->full = true;
        $busRide->segments = [new SchemaBusRideSegment(), new SchemaBusRideSegment()];

        // This segment will be matched and updated
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
        // Ignoring vehicle info for now
        $busRide->segments[0]->busInfo = new Vehicle();
        $busRide->segments[0]->busInfo->type = 'bus2';
        $busRide->segments[0]->busInfo->model = 'bus model 2';
        $busRide->segments[0]->seats = ['seat 1', 'seat 2'];
        $busRide->segments[0]->traveledMiles = '2000 miles';
        $busRide->segments[0]->cabin = 'Economy';
        $busRide->segments[0]->bookingCode = 'T';
        $busRide->segments[0]->duration = '19h';
        $busRide->segments[0]->meal = 'yes';
        $busRide->segments[0]->smoking = false;
        $busRide->segments[0]->stops = 2;

        // This segment will be matched and updated
        $busRide->segments[1]->scheduleNumber = 'SN3';
        $busRide->segments[1]->departure = new TransportLocation();
        $busRide->segments[1]->departure->name = "Third Segment Departure Station";
        $busRide->segments[1]->departure->address = new Address();
        $busRide->segments[1]->departure->address->text = "Moscow Oblast, Russia";
        $busRide->segments[1]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+7 days'));
        $busRide->segments[1]->departure->stationCode = 'DS3';
        $busRide->segments[1]->arrival = new TransportLocation();
        $busRide->segments[1]->arrival->name = "Third Segment Arrival Station";
        $busRide->segments[1]->arrival->address = new Address();
        $busRide->segments[1]->arrival->address->text = "Bol'shoye Savino, Perm Krai, Russia, 614515";
        $busRide->segments[1]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+8 days'));
        $busRide->segments[1]->arrival->stationCode = 'AS3';
        // Ignoring vehicle info for now
        $busRide->segments[1]->busInfo = new Vehicle();
        $busRide->segments[1]->busInfo->type = 'bus3';
        $busRide->segments[1]->busInfo->model = 'train bus 3';
        $busRide->segments[1]->seats = ['seat 3', 'seat 4'];
        $busRide->segments[1]->traveledMiles = '3000 miles';
        $busRide->segments[1]->cabin = 'Business';
        $busRide->segments[1]->bookingCode = 'R';
        $busRide->segments[1]->duration = '19h';
        $busRide->segments[1]->meal = 'yes';
        $busRide->segments[1]->smoking = false;
        $busRide->segments[1]->stops = 3;

        return $busRide;
    }

    private function getSchemaCruise(): SchemaCruise
    {
        $cruise = new SchemaCruise();
        $this->setGeneralSchemaItineraryFields($cruise);
        $cruise->confirmationNumbers = [new ConfNo(), new ConfNo()];
        $cruise->confirmationNumbers[0]->number = bin2hex(random_bytes(10));
        $cruise->confirmationNumbers[1]->number = bin2hex(random_bytes(10));
        $cruise->confirmationNumbers[1]->isPrimary = true;
        $cruise->travelers = [new Person(), new Person()];
        $cruise->travelers[0]->name = 'First Traveler';
        $cruise->travelers[0]->full = true;
        $cruise->travelers[1]->name = 'Second Traveler';
        $cruise->travelers[1]->full = true;
        $cruise->cruiseDetails = new CruiseDetails();
        $cruise->cruiseDetails->description = 'Long cruise';
        $cruise->cruiseDetails->class = 'Regular';
        $cruise->cruiseDetails->deck = '3';
        $cruise->cruiseDetails->room = '342';
        $cruise->cruiseDetails->ship = 'Disney Dream';
        $cruise->cruiseDetails->shipCode = 'SHCD';
        $cruise->segments = [new SchemaCruiseSegment(), new SchemaCruiseSegment()];

        // This segment will be matched and updated
        $cruise->segments[0]->departure = new TransportLocation();
        $cruise->segments[0]->departure->name = "Second Segment Departure Port";
        $cruise->segments[0]->departure->address = new Address();
        $cruise->segments[0]->departure->address->text = "Queens, NY 11430, USA";
        $cruise->segments[0]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+5 days'));
        $cruise->segments[0]->departure->stationCode = 'DP2';
        $cruise->segments[0]->arrival = new TransportLocation();
        $cruise->segments[0]->arrival->name = "Second Segment Arrival Port";
        $cruise->segments[0]->arrival->address = new Address();
        $cruise->segments[0]->arrival->address->text = "Longford TW6, UK";
        $cruise->segments[0]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+6 days'));
        $cruise->segments[0]->arrival->stationCode = 'AP2';

        // This segment will be matched and updated
        $cruise->segments[1]->departure = new TransportLocation();
        $cruise->segments[1]->departure->name = "Third Segment Departure Port";
        $cruise->segments[1]->departure->address = new Address();
        $cruise->segments[1]->departure->address->text = "Moscow Oblast, Russia";
        $cruise->segments[1]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+7 days'));
        $cruise->segments[1]->departure->stationCode = 'DP3';
        $cruise->segments[1]->arrival = new TransportLocation();
        $cruise->segments[1]->arrival->name = "Third Segment Arrival Port";
        $cruise->segments[1]->arrival->address = new Address();
        $cruise->segments[1]->arrival->address->text = "Bol'shoye Savino, Perm Krai, Russia, 614515";
        $cruise->segments[1]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+8 days'));
        $cruise->segments[1]->arrival->stationCode = 'AP3';

        return $cruise;
    }

    private function getSchemaFerry(): SchemaFerry
    {
        return create(function (SchemaFerry $ferry) {
            $this->setGeneralSchemaItineraryFields($ferry);

            $ferry->confirmationNumbers = [
                create(function (ConfNo $confNo) {
                    $confNo->number = bin2hex(random_bytes(10));
                }),
                create(function (ConfNo $confNo) {
                    $confNo->number = bin2hex(random_bytes(10));
                    $confNo->isPrimary = true;
                }),
            ];

            $ferry->travelers = [
                create(function (Person $person) {
                    $person->name = 'First Traveler';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Second Traveler';
                    $person->full = true;
                }),
            ];

            $ferry->segments = [
                create(function (SchemaFerrySegment $segment) {
                    $segment->departure = create(function (TransportLocation $location) {
                        $location->name = "Second Segment Departure Port";
                        $location->address = create(function (Address $address) {
                            $address->text = "Queens, NY 11430, USA";
                        });
                        $location->localDateTime = date('Y-m-dTH:i:s', strtotime('+5 days'));
                        $location->stationCode = "DP2";
                    });
                    $segment->arrival = create(function (TransportLocation $location) {
                        $location->name = "Second Segment Arrival Port";
                        $location->address = create(function (Address $address) {
                            $address->text = "Longford TW6, UK";
                        });
                        $location->localDateTime = date('Y-m-dTH:i:s', strtotime('+6 days'));
                        $location->stationCode = "AP2";
                    });
                }),
                create(function (SchemaFerrySegment $segment) {
                    $segment->departure = create(function (TransportLocation $location) {
                        $location->name = "Third Segment Departure Port";
                        $location->address = create(function (Address $address) {
                            $address->text = "Moscow Oblast, Russia";
                        });
                        $location->localDateTime = date('Y-m-dTH:i:s', strtotime('+7 days'));
                        $location->stationCode = "DP3";
                    });
                    $segment->arrival = create(function (TransportLocation $location) {
                        $location->name = "Third Segment Arrival Port";
                        $location->address = create(function (Address $address) {
                            $address->text = "Bol'shoye Savino, Perm Krai, Russia, 614515";
                        });
                        $location->localDateTime = date('Y-m-dTH:i:s', strtotime('+8 days'));
                        $location->stationCode = "AP3";
                    });
                }),
            ];
        });
    }

    private function getSchemaTransfer(): SchemaTransfer
    {
        $transfer = new SchemaTransfer();
        $this->setGeneralSchemaItineraryFields($transfer);
        $transfer->confirmationNumbers = [new ConfNo(), new ConfNo()];
        $transfer->confirmationNumbers[0]->number = bin2hex(random_bytes(10));
        $transfer->confirmationNumbers[1]->number = bin2hex(random_bytes(10));
        $transfer->confirmationNumbers[1]->isPrimary = true;
        $transfer->travelers = [new Person(), new Person()];
        $transfer->travelers[0]->name = 'First Traveler';
        $transfer->travelers[0]->full = true;
        $transfer->travelers[1]->name = 'Second Traveler';
        $transfer->travelers[1]->full = true;
        $transfer->segments = [new SchemaTransferSegment(), new SchemaTransferSegment()];

        // This segment will be matched and updated
        $transfer->segments[0]->departure = new TransferLocation();
        $transfer->segments[0]->departure->name = "John F. Kennedy International Airport";
        $transfer->segments[0]->departure->airportCode = "JFK";
        $transfer->segments[0]->departure->address = new Address();
        $transfer->segments[0]->departure->address->text = "Queens, NY 11430, USA";
        $transfer->segments[0]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+5 days'));
        $transfer->segments[0]->arrival = new TransferLocation();
        $transfer->segments[0]->arrival->name = "Heathrow Airport";
        $transfer->segments[0]->arrival->airportCode = "LHR";
        $transfer->segments[0]->arrival->address = new Address();
        $transfer->segments[0]->arrival->address->text = "Longford TW6, UK";
        $transfer->segments[0]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+6 days'));
        // Ignoring vehicle info for now
        $transfer->segments[0]->vehicleInfo = new Car();
        $transfer->segments[0]->vehicleInfo->model = 'Ford Focus';
        $transfer->segments[0]->vehicleInfo->type = 'Regular';
        $transfer->segments[0]->vehicleInfo->imageUrl = 'http://car.image/url';
        $transfer->segments[0]->adults = 2;
        $transfer->segments[0]->kids = 2;
        $transfer->segments[0]->traveledMiles = '2000 miles';
        $transfer->segments[0]->duration = '19h';

        // This segment will be matched and updated
        $transfer->segments[1]->departure = new TransferLocation();
        $transfer->segments[1]->departure->name = "Domodedovo Moscow Airport";
        $transfer->segments[1]->departure->airportCode = 'DME';
        $transfer->segments[1]->departure->address = new Address();
        $transfer->segments[1]->departure->address->text = 'Moscow Oblast, Russia';
        $transfer->segments[1]->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+7 days'));
        $transfer->segments[1]->arrival = new TransferLocation();
        $transfer->segments[1]->arrival->name = "Aeroport Bol'shoye Savino";
        $transfer->segments[1]->arrival->airportCode = 'PEE';
        $transfer->segments[1]->arrival->address = new Address();
        $transfer->segments[1]->arrival->address->text = "Bol'shoye Savino, Perm Krai, Russia, 614515";
        $transfer->segments[1]->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+8 days'));
        // Ignoring vehicle info for now
        $transfer->segments[1]->vehicleInfo = new Car();
        $transfer->segments[1]->vehicleInfo->model = 'Lada Calina';
        $transfer->segments[1]->vehicleInfo->type = 'Extreme';
        $transfer->segments[1]->vehicleInfo->imageUrl = 'http://car.image/url2';
        $transfer->segments[1]->adults = 3;
        $transfer->segments[1]->kids = 3;
        $transfer->segments[1]->traveledMiles = '3000 miles';
        $transfer->segments[1]->duration = '22h';

        return $transfer;
    }

    /**
     * Version 0.3.17.
     */
    private function getSchemaReservation(): SchemaReservation
    {
        $hotelReservation = new SchemaReservation();
        $this->setGeneralSchemaItineraryFields($hotelReservation);
        $hotelReservation->confirmationNumbers = [new ConfNo(), new ConfNo()];
        $hotelReservation->confirmationNumbers[0]->number = bin2hex(random_bytes(10));
        $hotelReservation->confirmationNumbers[1]->number = bin2hex(random_bytes(10));
        $hotelReservation->confirmationNumbers[1]->isPrimary = true;
        $hotelReservation->hotelName = 'Courtyard Santa Clarita Valencia';
        $hotelReservation->chainName = 'Some Chain'; // We're not saving chain name?
        $hotelReservation->address = new Address();
        // Set address text to reservation, ignore all other fields
        $hotelReservation->address->text = '28523 Westinghouse Place Valencia California 91355 USA';
        $hotelReservation->checkInDate = date('Y-m-dTH:i:s', strtotime('+2 days'));
        $hotelReservation->checkOutDate = date('Y-m-dTH:i:s', strtotime('+3 days'));
        $hotelReservation->phone = '+1-661-257-3220';
        $hotelReservation->fax = '+44 161 999 8888';
        $hotelReservation->guests = [new Person(), new Person()];
        $hotelReservation->guests[0]->full = true;
        $hotelReservation->guests[0]->name = 'First Guest';
        $hotelReservation->guests[1]->full = false;
        $hotelReservation->guests[1]->name = 'Second Guest';
        $hotelReservation->guestCount = 2;
        $hotelReservation->kidsCount = 2;
        $hotelReservation->roomsCount = 2;
        $hotelReservation->cancellationPolicy = 'Please note that a change in the length or dates may result in a rate change...';
        $hotelReservation->rooms = [new Room(), new Room()];
        $hotelReservation->rooms[0]->description = 'One big bed for a crowned man, but no smoking!';
        $hotelReservation->rooms[0]->type = '1 KING BED NONSMOKING';
        $hotelReservation->rooms[0]->rate = '130 USD/night';
        $hotelReservation->rooms[0]->rateType = '1 KING BED NOSMOKING';
        $hotelReservation->rooms[1]->description = 'One average bed for a daughter of a crowned man, but no smoking either!';
        $hotelReservation->rooms[1]->type = '1 PRINCESS BED NONSMOKING';
        $hotelReservation->rooms[1]->rate = '130 USD/night';
        $hotelReservation->rooms[1]->rateType = '1 PRINCESS BED NOSMOKING';
        $hotelReservation->cancellationDeadline = date('Y-m-d\TH:i:s', strtotime('+1 day'));
        $hotelReservation->freeNights = 2;

        return $hotelReservation;
    }

    private function getSchemaRental(): SchemaRental
    {
        $carRental = new SchemaRental();
        $this->setGeneralSchemaItineraryFields($carRental);
        $carRental->confirmationNumbers = [new ConfNo(), new ConfNo()];
        $carRental->confirmationNumbers[0]->number = bin2hex(random_bytes(10));
        $carRental->confirmationNumbers[1]->number = bin2hex(random_bytes(10));
        $carRental->confirmationNumbers[1]->isPrimary = true;
        $carRental->pickup = new CarRentalLocation();
        $carRental->pickup->address = new Address();
        // Set address text, ignore all other fields
        $carRental->pickup->address->text = '999 9th St NW, Washington, DC 20001, USA';
        $carRental->pickup->localDateTime = date('Y-m-dTH:i:s', strtotime('+2 days'));
        $carRental->pickup->openingHours = 'pickup opening hours';
        $carRental->pickup->phone = 'pickup phone';
        $carRental->pickup->fax = 'pickup fax';
        $carRental->dropoff = new CarRentalLocation();
        $carRental->dropoff->address = new Address();
        // Set address text, ignore all other fields
        $carRental->dropoff->address->text = '50 Massachusetts Ave NE, Washington, DC 20002, USA';
        $carRental->dropoff->localDateTime = date('Y-m-dTH:i:s', strtotime('+3 days'));
        $carRental->dropoff->openingHours = 'drop-off opening hours';
        $carRental->dropoff->phone = 'drop-off phone';
        $carRental->dropoff->fax = 'drop-off fax';
        $carRental->car = new Car();
        $carRental->car->type = 'car type';
        $carRental->car->model = 'car model';
        $carRental->car->imageUrl = 'car_image_url';
        $carRental->discounts = [new CarRentalDiscount(), new CarRentalDiscount()];
        $carRental->discounts[0]->name = 'first discount name';
        $carRental->discounts[0]->code = 'first discount code';
        $carRental->discounts[1]->name = 'second discount name';
        $carRental->discounts[1]->code = 'second discount code';
        $carRental->driver = new Person();
        $carRental->driver->name = 'Driver Name';
        $carRental->driver->full = true;
        $carRental->pricedEquipment = [new Fee(), new Fee()];
        $carRental->pricedEquipment[0]->name = 'priced equipment 1';
        $carRental->pricedEquipment[0]->charge = 10.0;
        $carRental->pricedEquipment[1]->name = 'priced equipment 2';
        $carRental->pricedEquipment[1]->charge = 20.0;
        $carRental->rentalCompany = 'rental company name';

        return $carRental;
    }

    private function getSchemaEvent(): SchemaEvent
    {
        $schemaEvent = new SchemaEvent();
        $this->setGeneralSchemaItineraryFields($schemaEvent);
        $schemaEvent->confirmationNumbers = [new ConfNo(), new ConfNo()];
        $schemaEvent->confirmationNumbers[0]->number = bin2hex(random_bytes(10));
        $schemaEvent->confirmationNumbers[1]->number = bin2hex(random_bytes(10));
        $schemaEvent->confirmationNumbers[1]->isPrimary = true;
        $schemaEvent->address = new Address();
        // Set address text, ignore all other fields
        $schemaEvent->address->text = '999 9th St (NW), Washington, DC 20001, USA';
        $schemaEvent->eventName = 'new event name';
        $schemaEvent->eventType = 4;
        $schemaEvent->startDateTime = date('Y-m-dTH:i:s', strtotime('+2 days'));
        $schemaEvent->endDateTime = date('Y-m-dTH:i:s', strtotime('+3 days'));
        $schemaEvent->phone = 'new event phone';
        $schemaEvent->fax = 'new event fax';
        $schemaEvent->guestCount = 2;
        $schemaEvent->guests = [new Person(), new Person()];
        $schemaEvent->guests[0]->name = 'Guest One';
        $schemaEvent->guests[0]->full = true;
        $schemaEvent->guests[1]->name = 'Guest Two';
        $schemaEvent->guests[1]->full = true;
        $schemaEvent->seats = ['seat 1', 'seat 2'];

        return $schemaEvent;
    }

    private function setGeneralSchemaItineraryFields(Itinerary $itinerary): void
    {
        $itinerary->travelAgency = create(function (TravelAgency $travelAgency) {
            // Just save all the numbers into Reservation::TravelAgencyConfirmationNumbers
            $travelAgency->confirmationNumbers = [
                create(function (ConfNo $confNo) {
                    $confNo->number = 'agency_conf1';
                    $confNo->isPrimary = false;
                }),
                create(function (ConfNo $confNo) {
                    $confNo->number = 'agency_conf2';
                    $confNo->isPrimary = false;
                }),
            ];
            // We're ignoring travel agency phone numbers for now
            $travelAgency->phoneNumbers = [
                create(function (PhoneNumber $phoneNumber) {
                    $phoneNumber->description = 'travel agency phone 1';
                    $phoneNumber->number = '777-777';
                }),
                create(function (PhoneNumber $phoneNumber) {
                    $phoneNumber->description = 'travel agency phone 2';
                    $phoneNumber->number = '888-888';
                }),
            ];
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgency->getName(); // Ignore
                $providerInfo->accountNumbers = [
                    create(function (ParsedNumber $parsedNumber) {
                        $parsedNumber->number = 'Agency Account Number 1';
                        $parsedNumber->masked = false;
                    }),
                    create(function (ParsedNumber $parsedNumber) {
                        $parsedNumber->number = 'Agency****2';
                        $parsedNumber->masked = true;
                    }),
                ];
                $providerInfo->code = $this->travelAgency->getCode(); // Find Provider with this code if present
                $providerInfo->earnedRewards = 'agency earned rewards';
            });
        });
        // Just save all the numbers into Reservation::TravelAgencyConfirmationNumbers

        $itinerary->reservationDate = date('Y-m-dTH:i:s', strtotime('tomorrow'));
        $itinerary->providerInfo = create(function (ProviderInfo $providerInfo) {
            $providerInfo->name = $this->provider->getName(); // Ignore
            $providerInfo->accountNumbers = [
                create(function (ParsedNumber $parsedNumber) {
                    $parsedNumber->number = 'Account Number 1';
                    $parsedNumber->masked = false;
                }),
                create(function (ParsedNumber $parsedNumber) {
                    $parsedNumber->number = '****ber 2';
                    $parsedNumber->masked = true;
                }),
            ];
            $providerInfo->code = $this->provider->getCode(); // Find Provider with this code if present
            $providerInfo->earnedRewards = 'earned rewards';
        });

        $itinerary->status = 'confirmed';
        $itinerary->pricingInfo = create(function (PricingInfo $pricingInfo) {
            $pricingInfo->total = 721.7;
            $pricingInfo->cost = 600;
            $pricingInfo->discount = 100;
            $pricingInfo->spentAwards = 'spent awards';
            $pricingInfo->currencyCode = 'RUB';
            $pricingInfo->fees = [
                create(function (Fee $fee) {
                    $fee->name = 'fee1';
                    $fee->charge = 10;
                }),
                create(function (Fee $fee) {
                    $fee->name = 'fee2';
                    $fee->charge = 20;
                }),
            ];
        });
    }
}
