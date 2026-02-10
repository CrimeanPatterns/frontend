<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\Common\Entity\Geotag as EntityGeotag;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\TicketNumber as EntityTicketNumber;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TrainRideSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\TrainConverter;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Train as SchemaTrain;

/**
 * @group frontend-unit
 */
class TrainConverterTest extends AbstractSegmentConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaTrain::class, SchemaEvent::class));
        $this->getConverter()->convert(
            new SchemaEvent(),
            null,
            $this->getEmailSavingOptions()
        );
    }

    /**
     * @dataProvider modesProvider
     */
    public function testConvert(bool $update)
    {
        $schemaItinerary = $this->getSchemaItinerary();

        if ($update) {
            $entityItinerary = $this->getDefaultEntityItinerary();
        }

        $entityItinerary = $this->getConverter()->convert(
            $schemaItinerary,
            $entityItinerary ?? null,
            $this->getEmailSavingOptions()
        );

        $this->assertEquals(['new conf no'], $entityItinerary->getProviderConfirmationNumbers());

        if ($update) {
            $this->assertEquals(['John Smith'], $entityItinerary->getTravelerNames());
        } else {
            $this->assertEquals(['Jess Sisi', 'Den Sisi'], $entityItinerary->getTravelerNames());
        }

        $this->assertEquals([
            new EntityTicketNumber('new number'),
            new EntityTicketNumber('new number 2', true),
        ], $entityItinerary->getTicketNumbers());

        if ($update) {
            $this->assertCount(2, $segments = $entityItinerary->getSegments());
            $this->assertTrue($segments[0]->isHiddenByUpdater());
            $this->assertFalse($segments[1]->isHiddenByUpdater());
        } else {
            $this->assertCount(1, $segments = $entityItinerary->getSegments());
            $this->assertFalse($segments[0]->isHiddenByUpdater());
        }
    }

    /**
     * @dataProvider modesProvider
     */
    public function testConvertSegment(bool $update)
    {
        $schemaItinerary = $this->getSchemaItinerary();
        $entityItinerary = $this->getDefaultEntityItinerary();
        $entitySegment = $this->getConverter()->convertSegment(
            $schemaItinerary,
            $schemaItinerary->segments[0],
            $entityItinerary,
            $update ? $entityItinerary->getSegments()[0] : null,
            $this->getEmailSavingOptions()
        );

        $this->assertEquals('new dep code', $entitySegment->getDepcode());
        $this->assertEquals('new dep name', $entitySegment->getDepname());
        $this->assertEquals('2000-01-05 01:00:00', $entitySegment->getDepartureDate()->format('Y-m-d H:i:s'));
        $this->assertNotNull($entitySegment->getDepgeotagid());
        $this->assertEquals('new dep address', $entitySegment->getDepgeotagid()->getAddress());

        $this->assertEquals('new arr code', $entitySegment->getArrcode());
        $this->assertEquals('new arr name', $entitySegment->getArrname());
        $this->assertEquals('2000-01-05 05:00:00', $entitySegment->getArrivalDate()->format('Y-m-d H:i:s'));
        $this->assertNotNull($entitySegment->getArrgeotagid());
        $this->assertEquals('new arr address', $entitySegment->getArrgeotagid()->getAddress());

        $this->assertEquals('new number', $entitySegment->getFlightNumber());
        $this->assertEquals('new service name', $entitySegment->getServiceName());
        $this->assertEquals('new model', $entitySegment->getAircraftName());
        $this->assertEquals('new car', $entitySegment->getCarNumber());
        $this->assertEquals(['new seat 1', 'new seat 2'], $entitySegment->getSeats());
        $this->assertEquals('new miles', $entitySegment->getTraveledMiles());
        $this->assertEquals('new cabin', $entitySegment->getCabinClass());
        $this->assertEquals('new book', $entitySegment->getBookingClass());
        $this->assertEquals('new duration', $entitySegment->getDuration());
        $this->assertEquals('new meal', $entitySegment->getMeal());
        $this->assertFalse($entitySegment->isSmoking());
        $this->assertEquals(3, $entitySegment->getStops());
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): TrainConverter {
        return new TrainConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo),
            $this->container->get(TrainRideSegmentMatcher::class),
            $this->makeEmpty(Validator::class, ['getLiveSources' => fn (array $sources) => $sources])
        );
    }

    /**
     * @return EntityItinerary|EntityTrip
     */
    protected function getDefaultEntityItinerary(): EntityItinerary
    {
        $this->setupEntityItinerary($entityItinerary = new EntityTrip());

        $entityItinerary->setTicketNumbers([
            new EntityTicketNumber('old number'),
            new EntityTicketNumber('old number 2', true),
        ]);

        $segment1 = new EntityTripSegment();
        $segment1->setDepcode('old dep code');
        $segment1->setDepname('old dep name');
        $segment1->setDepartureDate(new \DateTime('2000-01-01 00:00:00'));
        $segment1->setDepgeotagid((new EntityGeotag())->setAddress('old dep geotag'));
        $segment1->setArrcode('old arr code');
        $segment1->setArrname('old arr name');
        $segment1->setArrivalDate(new \DateTime('2000-01-01 03:00:00'));
        $segment1->setArrgeotagid((new EntityGeotag())->setAddress('old arr geotag'));
        $segment1->setFlightNumber('old number');
        $segment1->setServiceName('old service name');
        $segment1->setAircraftName('old aircraft');
        $segment1->setCarNumber('old car number');
        $segment1->setSeats(['old seat 1', 'old seat 2']);
        $segment1->setTraveledMiles('old traveled miles');
        $segment1->setCabinClass('old cabin');
        $segment1->setBookingClass('old booking class');
        $segment1->setDuration('old duration');
        $segment1->setMeal('old meal');
        $segment1->setSmoking(true);
        $segment1->setStops(1);
        $segment1->cancel();
        $entityItinerary->addSegment($segment1);

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaTrain
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaTrain(
            [
                SchemaBuilder::makeSchemaTrainSegment(
                    SchemaBuilder::makeSchemaTransportLocation(
                        'new dep name',
                        new \DateTime('2000-01-05 01:00:00'),
                        $this->getSchemaAddress('new dep address'),
                        'new dep code'
                    ),
                    SchemaBuilder::makeSchemaTransportLocation(
                        'new arr name',
                        new \DateTime('2000-01-05 05:00:00'),
                        $this->getSchemaAddress('new arr address'),
                        'new arr code'
                    ),
                    'new number',
                    'new service name',
                    SchemaBuilder::makeSchemaVehicle('new type', 'new model'),
                    'new car',
                    ['new seat 1', 'new seat 2'],
                    'new miles',
                    'new cabin',
                    'new book',
                    'new duration',
                    'new meal',
                    false,
                    3
                ),
            ],
            [
                SchemaBuilder::makeSchemaPerson('Jess Sisi'),
                SchemaBuilder::makeSchemaPerson('Den Sisi'),
            ],
            [SchemaBuilder::makeSchemaConfNo('new conf no', true)],
            [
                SchemaBuilder::makeSchemaParsedNumber('new number'),
                SchemaBuilder::makeSchemaParsedNumber('new number 2', true),
            ],
            $this->getSchemaProviderInfo(),
            $this->getSchemaTravelAgency(),
            $this->getSchemaPricingInfo(),
            $this->getSchemaStatus(),
            $this->getSchemaReservationDate(),
            $this->getSchemaCancellationPolicy(),
            $this->getSchemaNotes(),
        );
    }
}
