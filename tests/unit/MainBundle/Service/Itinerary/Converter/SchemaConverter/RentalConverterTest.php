<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\PricedEquipment as EntityPricedEquipment;
use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\MainBundle\Entity\RentalDiscountDetails as EntityRentalDiscountDetails;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\RentalConverter;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\CarRental as SchemaRental;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @group frontend-unit
 */
class RentalConverterTest extends AbstractConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaRental::class, SchemaFlight::class));
        $this->getConverter()->convert(
            new SchemaFlight(),
            null,
            $this->getEmailSavingOptions()
        );
    }

    public function testValidateEntity()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', EntityRental::class, EntityReservation::class));
        $this->getConverter()->convert(
            new SchemaRental(),
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
        $this->assertNotNull($entityItinerary->getPickupgeotagid());
        $this->assertNotNull($entityItinerary->getPickupdatetime());
        $this->assertEquals('2000-01-03 00:00:00', $entityItinerary->getPickupdatetime()->format('Y-m-d H:i:s'));
        $this->assertEquals('Sun - Sat open 24 hrs', $entityItinerary->getPickuphours());
        $this->assertEquals('111-333', $entityItinerary->getPickupphone());
        $this->assertEquals('333', $entityItinerary->getPickUpFax());

        $this->assertNotNull($entityItinerary->getDropoffgeotagid());
        $this->assertNotNull($entityItinerary->getDropoffdatetime());
        $this->assertEquals('2000-01-04 00:00:00', $entityItinerary->getDropoffdatetime()->format('Y-m-d H:i:s'));
        $this->assertEquals('Sun - Sat open 24 hrs', $entityItinerary->getDropoffhours());
        $this->assertEquals('111-333', $entityItinerary->getDropoffphone());
        $this->assertEquals('333', $entityItinerary->getDropOffFax());

        $this->assertEquals('new car', $entityItinerary->getCarType());
        $this->assertEquals('new bmw', $entityItinerary->getCarModel());
        $this->assertEquals('img link', $entityItinerary->getCarImageUrl());
        $this->assertEquals([
            new EntityRentalDiscountDetails('new first discount name', 'new first discount code'),
            new EntityRentalDiscountDetails('new second discount name', 'new second discount code'),
        ], $entityItinerary->getDiscountDetails());

        if ($update) {
            $this->assertEquals(['John Smith'], $entityItinerary->getTravelerNames());
        } else {
            $this->assertEquals(['Billy'], $entityItinerary->getTravelerNames());
        }

        $this->assertEquals([
            new EntityPricedEquipment('new priced equipment 1', 10.0),
            new EntityPricedEquipment('new priced equipment 2', 20.0),
        ], $entityItinerary->getPricedEquipment());
        $this->assertEquals('new rental company', $entityItinerary->getRentalCompanyName());
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): RentalConverter {
        return new RentalConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo)
        );
    }

    /**
     * @return EntityItinerary|EntityRental
     */
    protected function getDefaultEntityItinerary(): EntityItinerary
    {
        $this->setupEntityItinerary($entityItinerary = new EntityRental());

        $entityItinerary->setPickuplocation('old pickup location');
        $entityItinerary->setPickUpFax('old pickup fax');
        $entityItinerary->setPickupphone('old pickup phone');
        $entityItinerary->setPickuphours('old pickup hours');
        $entityItinerary->setPickupdatetime(new \DateTime('2000-01-01 00:00:00'));
        $entityItinerary->setDropofflocation('old droppoff location');
        $entityItinerary->setDropOffFax('old droppoff fax');
        $entityItinerary->setDropoffphone('old droppoff phone');
        $entityItinerary->setDropoffhours('old droppoff hours');
        $entityItinerary->setDropoffdatetime(new \DateTime('2000-01-02 00:00:00'));
        $entityItinerary->setRentalCompanyName('old company');
        $entityItinerary->setCarImageUrl('link');
        $entityItinerary->setCarModel('old model');
        $entityItinerary->setCarType('old type');
        $entityItinerary->setDiscountDetails([
            new EntityRentalDiscountDetails('name 1', 'code 1'),
        ]);
        $entityItinerary->setPricedEquipment([
            new EntityPricedEquipment('name 1', 90.0),
        ]);

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaRental
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaRental(
            SchemaBuilder::makeSchemaCarRentalLocation(
                $this->getSchemaAddress(),
                new \DateTime('2000-01-03 00:00:00'),
                'Sun - Sat open 24 hrs',
                '111-333',
                '333'
            ),
            SchemaBuilder::makeSchemaCarRentalLocation(
                $this->getSchemaAddress(),
                new \DateTime('2000-01-04 00:00:00'),
                'Sun - Sat open 24 hrs',
                '111-333',
                '333'
            ),
            SchemaBuilder::makeSchemaPerson('Billy'),
            [SchemaBuilder::makeSchemaConfNo('SCHEMA_CONF_1', true)],
            SchemaBuilder::makeSchemaCar('new car', 'new bmw', 'img link'),
            [
                SchemaBuilder::makeSchemaPricedEquipment('new priced equipment 1', 10.0),
                SchemaBuilder::makeSchemaPricedEquipment('new priced equipment 2', 20.0),
            ],
            'new rental company',
            [
                SchemaBuilder::makeSchemaCarRentalDiscount('new first discount name', 'new first discount code'),
                SchemaBuilder::makeSchemaCarRentalDiscount('new second discount name', 'new second discount code'),
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
