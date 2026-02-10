<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\Common\Entity\Geotag as EntityGeotag;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\TicketNumber as EntityTicketNumber;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Entity\Vehicle as EntityVehicle;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\FerrySegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\FerryConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Ferry as SchemaFerry;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @group frontend-unit
 */
class FerryConverterTest extends AbstractSegmentConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaFerry::class, SchemaEvent::class));
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

        $this->assertEquals([
            '4-Bed inside cabin, shower/WC',
            '4-Bed inside cabin, shower/WC',
        ], $entitySegment->getAccommodations());
        $this->assertEquals('new carrier', $entitySegment->getAircraftName());
        $this->assertEquals('new vessel', $entitySegment->getVessel());
        $this->assertEquals('new miles', $entitySegment->getTraveledMiles());
        $this->assertEquals('new duration', $entitySegment->getDuration());
        $this->assertEquals('new meal', $entitySegment->getMeal());
        $this->assertEquals('new cabin', $entitySegment->getCabinClass());
        $this->assertFalse($entitySegment->isSmoking());
        $this->assertEquals(2, $entitySegment->getAdultsCount());
        $this->assertEquals(1, $entitySegment->getKidsCount());
        $this->assertEquals('1 cat', $entitySegment->getPets());
        $this->assertEquals([
            (new EntityVehicle())
                ->setType('new type')
                ->setModel('new model')
                ->setLength('new length')
                ->setWidth('new width')
                ->setHeight('new height'),
        ], $entitySegment->getVehicles());
        $this->assertEquals([
            (new EntityVehicle())
                ->setType('new tr type')
                ->setModel('new tr model')
                ->setLength('new tr length')
                ->setWidth('new tr width')
                ->setHeight('new tr height'),
        ], $entitySegment->getTrailers());
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): FerryConverter {
        return new FerryConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo),
            $this->container->get(FerrySegmentMatcher::class),
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
        $segment1->setAccommodations(['old acc']);
        $segment1->setAircraftName('old aircraft');
        $segment1->setVessel('old vessel');
        $segment1->setTraveledMiles('old traveled miles');
        $segment1->setDuration('old duration');
        $segment1->setMeal('old meal');
        $segment1->setCabinClass('old cabin');
        $segment1->setSmoking(true);
        $segment1->setAdultsCount(10);
        $segment1->setKidsCount(9);
        $segment1->setPets('none');
        $segment1->setVehicles([
            (new EntityVehicle())
                ->setType('old type')
                ->setModel('old model')
                ->setLength('old length')
                ->setWidth('old width')
                ->setHeight('old height'),
        ]);
        $segment1->setTrailers([
            (new EntityVehicle())
                ->setType('old tr type')
                ->setModel('old tr model')
                ->setLength('old tr length')
                ->setWidth('old tr width')
                ->setHeight('old tr height'),
        ]);
        $segment1->cancel();
        $entityItinerary->addSegment($segment1);

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaFerry
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaFerry(
            [
                SchemaBuilder::makeSchemaFerrySegment(
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
                    [
                        '4-Bed inside cabin, shower/WC',
                        '4-Bed inside cabin, shower/WC',
                    ],
                    'new carrier',
                    'new vessel',
                    'new miles',
                    'new duration',
                    'new meal',
                    'new cabin',
                    false,
                    2,
                    1,
                    '1 cat',
                    [
                        SchemaBuilder::makeSchemaVehicleExt('new type', 'new model', 'new length', 'new width', 'new height'),
                    ],
                    [
                        SchemaBuilder::makeSchemaVehicleExt('new tr type', 'new tr model', 'new tr length', 'new tr width', 'new tr height'),
                    ]
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
            $this->getSchemaNotes()
        );
    }
}
