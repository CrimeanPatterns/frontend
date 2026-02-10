<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Parking as EntityParking;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\ParkingConverter;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Parking as SchemaParking;

/**
 * @group frontend-unit
 */
class ParkingConverterTest extends AbstractConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaParking::class, SchemaFlight::class));
        $this->getConverter()->convert(
            new SchemaFlight(),
            null,
            $this->getEmailSavingOptions()
        );
    }

    public function testValidateEntity()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', EntityParking::class, EntityReservation::class));
        $this->getConverter()->convert(
            new SchemaParking(),
            new EntityReservation(),
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
        $this->assertNotNull($entityItinerary->getGeoTagID());
        $this->assertEquals('4', $entityItinerary->getSpot());
        $this->assertEquals('ABC GHY', $entityItinerary->getPlate());
        $this->assertNotNull($entityItinerary->getStartDate());
        $this->assertEquals('2000-01-03 00:00:00', $entityItinerary->getStartDate()->format('Y-m-d H:i:s'));
        $this->assertNotNull($entityItinerary->getEndDate());
        $this->assertEquals('2000-01-04 00:00:00', $entityItinerary->getEndDate()->format('Y-m-d H:i:s'));
        $this->assertEquals('333-444', $entityItinerary->getPhone());

        if ($update) {
            $this->assertEquals(['John Smith'], $entityItinerary->getTravelerNames());
        } else {
            $this->assertEquals(['Jess Smith'], $entityItinerary->getTravelerNames());
        }

        $this->assertEquals('STANDARD', $entityItinerary->getRateType());
        $this->assertEquals('Volkswagen', $entityItinerary->getCarDescription());
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): ParkingConverter {
        return new ParkingConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo)
        );
    }

    /**
     * @return EntityItinerary|EntityParking
     */
    protected function getDefaultEntityItinerary(): EntityItinerary
    {
        $this->setupEntityItinerary($entityItinerary = new EntityParking());

        $entityItinerary->setStartDatetime(new \DateTime('2000-01-02 00:00:00'));
        $entityItinerary->setEndDatetime(new \DateTime('2000-01-03 00:00:00'));
        $entityItinerary->setParkingCompanyName('The Parking Spot');
        $entityItinerary->setLocation('Downtown 3 hr');
        $entityItinerary->setSpot('3');
        $entityItinerary->setPlate('ABC');
        $entityItinerary->setCarDescription('Volkswagen white');
        $entityItinerary->setRateType('STANDARD SPOT');

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaParking
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaParking(
            new \DateTime('2000-01-03 00:00:00'),
            new \DateTime('2000-01-04 00:00:00'),
            SchemaBuilder::makeSchemaPerson('Jess Smith'),
            [SchemaBuilder::makeSchemaConfNo('SCHEMA_CONF_1', true)],
            $this->getSchemaAddress(),
            'Downtown 2 hr',
            '4',
            'ABC GHY',
            '333-444',
            null,
            'Volkswagen',
            'STANDARD',
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
