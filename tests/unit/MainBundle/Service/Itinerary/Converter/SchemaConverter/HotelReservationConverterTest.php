<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\MainBundle\Entity\Room as EntityRoom;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\HotelReservationConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaReservation;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @group frontend-unit
 */
class HotelReservationConverterTest extends AbstractConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaReservation::class, SchemaFlight::class));
        $this->getConverter()->convert(
            new SchemaFlight(),
            null,
            $this->getEmailSavingOptions()
        );
    }

    public function testValidateEntity()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', EntityReservation::class, EntityRental::class));
        $this->getConverter()->convert(
            new SchemaReservation(),
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
        $this->assertEquals('new hotel name', $entityItinerary->getHotelname());
        $this->assertEquals('new chain name', $entityItinerary->getChainName());
        $this->assertNotNull($entityItinerary->getGeotagid());
        $this->assertEquals('new address', $entityItinerary->getAddress());
        $this->assertNotNull($entityItinerary->getCheckindate());
        $this->assertEquals('2000-01-03 16:00:00', $entityItinerary->getCheckindate()->format('Y-m-d H:i:s'));
        $this->assertNotNull($entityItinerary->getCheckoutdate());
        $this->assertEquals('2000-01-04 11:00:00', $entityItinerary->getCheckoutdate()->format('Y-m-d H:i:s'));
        $this->assertEquals('new phone', $entityItinerary->getPhone());
        $this->assertEquals('new fax', $entityItinerary->getFax());

        if ($update) {
            $this->assertEquals(['John Smith'], $entityItinerary->getTravelerNames());
        } else {
            $this->assertEquals(['Jess Sisi', 'Den Sisi'], $entityItinerary->getTravelerNames());
        }

        $this->assertEquals(3, $entityItinerary->getGuestCount());
        $this->assertEquals(1, $entityItinerary->getKidsCount());
        $this->assertEquals(3, $entityItinerary->getRoomCount());
        $this->assertEquals('new cancellation number', $entityItinerary->getCancellationNumber());
        $this->assertNotNull($entityItinerary->getCancellationDeadline());
        $this->assertEquals('2000-01-04 00:00:00', $entityItinerary->getCancellationDeadline()->format('Y-m-d H:i:s'));
        $this->assertTrue($entityItinerary->getNonRefundable());
        $this->assertEquals([
            new EntityRoom(
                'new type',
                'new desc',
                'new rate',
                'new rate type'
            ),
            new EntityRoom(
                'new type 2',
                'new desc 2',
                'new rate 2',
                'new rate type 2'
            ),
        ], $entityItinerary->getRooms());
        $this->assertEquals(5, $entityItinerary->getFreeNights());
    }

    /**
     * @dataProvider datesProvider
     */
    public function testRewriteDates(string $checkIn, string $checkOut, bool $same)
    {
        $schemaItinerary = $this->getSchemaItinerary();
        $schemaItinerary->checkInDate = $checkIn;
        $schemaItinerary->checkOutDate = $checkOut;
        $entityItinerary = $this->getDefaultEntityItinerary();
        $format = 'Y-m-d H:i';
        $checkIn = $entityItinerary->getCheckindate()->format($format);
        $checkOut = $entityItinerary->getCheckoutdate()->format($format);
        $entityItinerary = $this->getConverter()->convert(
            $schemaItinerary,
            $entityItinerary,
            $this->getEmailSavingOptions()
        );
        $this->assertEquals($same, $checkIn === $entityItinerary->getCheckindate()->format($format));
        $this->assertEquals($same, $checkOut === $entityItinerary->getCheckoutdate()->format($format));
    }

    public function datesProvider()
    {
        return [
            ['2000-01-02T00:00:00', '2000-01-03T00:00:00', true],
            ['2000-01-02T14:00:00', '2000-01-03T10:00:00', false],
            ['2000-01-04T00:00:00', '2000-01-05T00:00:00', false],
        ];
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): HotelReservationConverter {
        return new HotelReservationConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo)
        );
    }

    /**
     * @return EntityItinerary|EntityReservation
     */
    protected function getDefaultEntityItinerary(): EntityItinerary
    {
        $this->setupEntityItinerary($entityItinerary = new EntityReservation());

        $entityItinerary->setHotelname('old hotel name');
        $entityItinerary->setChainName('old chain name');
        $entityItinerary->setCheckindate(new \DateTime('2000-01-02 16:00:00'));
        $entityItinerary->setCheckoutdate(new \DateTime('2000-01-03 11:00:00'));
        $entityItinerary->setAddress('old address');
        $entityItinerary->setPhone('old phone');
        $entityItinerary->setFax('old fax');
        $entityItinerary->setFreeNights(0);
        $entityItinerary->setCancellationNumber('old cancellation number');
        $entityItinerary->setCancellationDeadline(new \DateTime('2000-01-03 00:00:00'));
        $entityItinerary->setNonRefundable(null);
        $entityItinerary->setGuestCount(1);
        $entityItinerary->setKidsCount(0);
        $entityItinerary->setRooms([
            new EntityRoom('old short description 1', 'old long description 1', 'old rate 1', 'old rate description 1'),
            new EntityRoom('old short description 2', 'old long description 2', 'old rate 2', 'old rate description 2'),
        ]);
        $entityItinerary->setRoomCount(2);

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaReservation
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaReservation(
            'new hotel name',
            $this->getSchemaAddress('new address'),
            new \DateTime('2000-01-03 00:00:00'),
            new \DateTime('2000-01-04 00:00:00'),
            [
                SchemaBuilder::makeSchemaPerson('Jess Sisi'),
                SchemaBuilder::makeSchemaPerson('Den Sisi'),
            ],
            3,
            1,
            [SchemaBuilder::makeSchemaConfNo('SCHEMA_CONF_1', true)],
            [
                SchemaBuilder::makeSchemaRoom(
                    'new type',
                    'new desc',
                    'new rate type',
                    'new rate'
                ),
                SchemaBuilder::makeSchemaRoom(
                    'new type 2',
                    'new desc 2',
                    'new rate type 2',
                    'new rate 2'
                ),
            ],
            3,
            'new phone',
            'new fax',
            5,
            'new chain name',
            $this->getSchemaProviderInfo(),
            $this->getSchemaTravelAgency(),
            $this->getSchemaPricingInfo(),
            $this->getSchemaStatus(),
            $this->getSchemaReservationDate(),
            $this->getSchemaCancellationPolicy(),
            $this->getSchemaNotes(),
            null,
            'new cancellation number',
            new \DateTime('2000-01-04 00:00:00'),
            true
        );
    }
}
