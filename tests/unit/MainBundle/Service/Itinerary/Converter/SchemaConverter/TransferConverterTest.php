<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\Common\Entity\Geotag as EntityGeotag;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TransferSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\TransferConverter;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;

/**
 * @group frontend-unit
 */
class TransferConverterTest extends AbstractSegmentConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaTransfer::class, SchemaEvent::class));
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

        $this->assertEquals('new model', $entitySegment->getAircraftName());
        $this->assertEquals(5, $entitySegment->getAdultsCount());
        $this->assertEquals(4, $entitySegment->getKidsCount());
        $this->assertEquals('new miles', $entitySegment->getTraveledMiles());
        $this->assertEquals('new duration', $entitySegment->getDuration());
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): TransferConverter {
        return new TransferConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo),
            $this->container->get(TransferSegmentMatcher::class),
            $this->makeEmpty(Validator::class, ['getLiveSources' => fn (array $sources) => $sources])
        );
    }

    /**
     * @return EntityItinerary|EntityTrip
     */
    protected function getDefaultEntityItinerary(): EntityItinerary
    {
        $this->setupEntityItinerary($entityItinerary = new EntityTrip());

        $segment1 = new EntityTripSegment();
        $segment1->setDepcode('old dep code');
        $segment1->setDepname('old dep name');
        $segment1->setDepartureDate(new \DateTime('2000-01-01 00:00:00'));
        $segment1->setDepgeotagid((new EntityGeotag())->setAddress('old dep geotag'));
        $segment1->setArrcode('old arr code');
        $segment1->setArrname('old arr name');
        $segment1->setArrivalDate(new \DateTime('2000-01-01 03:00:00'));
        $segment1->setArrgeotagid((new EntityGeotag())->setAddress('old arr geotag'));
        $segment1->setAircraftName('old aircraft');
        $segment1->setAdultsCount(10);
        $segment1->setKidsCount(9);
        $segment1->setTraveledMiles('old traveled miles');
        $segment1->setDuration('old duration');
        $segment1->cancel();
        $entityItinerary->addSegment($segment1);

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaTransfer
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaTransfer(
            [
                SchemaBuilder::makeSchemaTransferSegment(
                    SchemaBuilder::makeSchemaTransferLocation(
                        'new dep name',
                        new \DateTime('2000-01-05 01:00:00'),
                        $this->getSchemaAddress('new dep address'),
                        'new dep code'
                    ),
                    SchemaBuilder::makeSchemaTransferLocation(
                        'new arr name',
                        new \DateTime('2000-01-05 05:00:00'),
                        $this->getSchemaAddress('new arr address'),
                        'new arr code'
                    ),
                    SchemaBuilder::makeSchemaCar('new type', 'new model', 'new url'),
                    5,
                    4,
                    'new miles',
                    'new duration'
                ),
            ],
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
            $this->getSchemaNotes(),
        );
    }
}
