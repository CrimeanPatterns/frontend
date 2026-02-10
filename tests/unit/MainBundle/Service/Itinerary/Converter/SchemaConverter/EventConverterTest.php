<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\MainBundle\Entity\Restaurant as EntityEvent;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\EventConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @group frontend-unit
 */
class EventConverterTest extends AbstractConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaEvent::class, SchemaFlight::class));
        $this->getConverter()->convert(
            new SchemaFlight(),
            null,
            $this->getEmailSavingOptions()
        );
    }

    public function testValidateEntity()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', EntityEvent::class, EntityRental::class));
        $this->getConverter()->convert(
            new SchemaEvent(),
            new EntityRental(),
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

        $this->assertEquals(['SCHEMA_CONF_1'], $entityItinerary->getProviderConfirmationNumbers());
        $this->assertNotNull($entityItinerary->getGeotagid());
        $this->assertEquals('new address', $entityItinerary->getAddress());
        $this->assertEquals('new name', $entityItinerary->getName());
        $this->assertEquals(EntityEvent::EVENT_SHOW, $entityItinerary->getEventtype());
        $this->assertNotNull($entityItinerary->getStartdate());
        $this->assertEquals('2000-01-03 00:00:00', $entityItinerary->getStartdate()->format('Y-m-d H:i:s'));
        $this->assertNotNull($entityItinerary->getEnddate());
        $this->assertEquals('2000-01-04 00:00:00', $entityItinerary->getEnddate()->format('Y-m-d H:i:s'));
        $this->assertEquals('new phone', $entityItinerary->getPhone());
        $this->assertEquals('new fax', $entityItinerary->getFax());
        $this->assertEquals(3, $entityItinerary->getGuestCount());

        if ($update) {
            $this->assertEquals(['John Smith'], $entityItinerary->getTravelerNames());
        } else {
            $this->assertEquals(['Jess Sisi', 'Den Sisi'], $entityItinerary->getTravelerNames());
        }

        $this->assertEquals(['new seat 1', 'new seat 2'], $entityItinerary->getSeats());
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): EventConverter {
        return new EventConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo)
        );
    }

    /**
     * @return EntityItinerary|EntityEvent
     */
    protected function getDefaultEntityItinerary(): EntityItinerary
    {
        $this->setupEntityItinerary($entityItinerary = new EntityEvent());

        $entityItinerary->setAddress('old address');
        $entityItinerary->setName('old name');
        $entityItinerary->setEventtype(EntityEvent::EVENT_EVENT);
        $entityItinerary->setStartdate(new \DateTime('2000-01-02 00:00:00'));
        $entityItinerary->setEnddate(new \DateTime('2000-01-03 00:00:00'));
        $entityItinerary->setPhone('old phone');
        $entityItinerary->setFax('old fax');
        $entityItinerary->setGuestCount(1);
        $entityItinerary->setSeats(['old seat']);

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaEvent
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaEvent(
            SchemaBuilder::makeSchemaAddress('new address'),
            EntityEvent::EVENT_SHOW,
            'new name',
            new \DateTime('2000-01-03 00:00:00'),
            new \DateTime('2000-01-04 00:00:00'),
            [
                SchemaBuilder::makeSchemaPerson('Jess Sisi'),
                SchemaBuilder::makeSchemaPerson('Den Sisi'),
            ],
            3,
            ['new seat 1', 'new seat 2'],
            [SchemaBuilder::makeSchemaConfNo('SCHEMA_CONF_1', true)],
            'new phone',
            'new fax',
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
