<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\Common\Entity\Geotag as EntityGeotag;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\CruiseSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\CruiseConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @group frontend-unit
 */
class CruiseConverterTest extends AbstractSegmentConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaCruise::class, SchemaEvent::class));
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

        $this->assertEquals('new desc', $entityItinerary->getCruiseName());
        $this->assertEquals('new class', $entityItinerary->getShipCabinClass());
        $this->assertEquals('new deck', $entityItinerary->getDeck());
        $this->assertEquals('new room', $entityItinerary->getCabinNumber());
        $this->assertEquals('new ship', $entityItinerary->getShipName());
        $this->assertEquals('new ship code', $entityItinerary->getShipCode());

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
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): CruiseConverter {
        return new CruiseConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo),
            $this->container->get(CruiseSegmentMatcher::class),
            $this->makeEmpty(Validator::class, ['getLiveSources' => fn (array $sources) => $sources])
        );
    }

    /**
     * @return EntityItinerary|EntityTrip
     */
    protected function getDefaultEntityItinerary(): EntityItinerary
    {
        $this->setupEntityItinerary($entityItinerary = new EntityTrip());

        $entityItinerary->setCruiseName('old cruise name');
        $entityItinerary->setShipCabinClass('old ship cabin');
        $entityItinerary->setDeck('old deck');
        $entityItinerary->setCabinNumber('old cabin number');
        $entityItinerary->setShipName('old ship name');
        $entityItinerary->setShipCode('old ship code');

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
        $segment1->cancel();
        $entityItinerary->addSegment($segment1);

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaCruise
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaCruise(
            [
                SchemaBuilder::makeSchemaCruiseSegment(
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
                    )
                ),
            ],
            SchemaBuilder::makeSchemaCruiseDetails(
                'new desc',
                'new class',
                'new deck',
                'new room',
                'new ship',
                'new ship code',
                'new number'
            ),
            [
                SchemaBuilder::makeSchemaPerson('Jess Sisi'),
                SchemaBuilder::makeSchemaPerson('Den Sisi'),
            ],
            [SchemaBuilder::makeSchemaConfNo('new conf no', true)],
            $this->getSchemaProviderInfo(),
            $this->getSchemaTravelAgency(),
            $this->getSchemaPricingInfo(),
            $this->getSchemaStatus(),
            $this->getSchemaReservationDate(),
            $this->getSchemaCancellationPolicy(),
            $this->getSchemaNotes()
        );
    }
}
