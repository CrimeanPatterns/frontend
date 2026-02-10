<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaHotelReservation;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI()
 */
class HotelReservation extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaHotelReservation
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaHotelReservation $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function sheratonPhiladelphiaDowntownHotel(
        ?array $confNumbers = [
            ['1122334455', true, 'Confirmation number'],
            ['887756', false, 'Reference'],
        ],
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaHotelReservation {
        return SchemaBuilder::makeSchemaReservation(
            'Sheraton Philadelphia Downtown Hotel',
            static::address(
                '201 North 17th Street, Philadelphia, Pennsylvania 19103 United States',
                '201 North 17th Street',
                'Philadelphia',
                'Pennsylvania',
                'United States',
                'US',
                '19103',
                39.9569828,
                -75.1674669,
                -14400,
                'America/New_York'
            ),
            date_create('2030-01-01T13:30:00'),
            date_create('2030-01-05T12:00:00'),
            static::persons(['John Smith', 'Jess Sisi']),
            2,
            3,
            static::confNumbers($confNumbers ?? []),
            [
                SchemaBuilder::makeSchemaRoom('King bed', 'Traditional, TV, free wi-fi', '30$/night', 'King bed'),
            ],
            1,
            '+1-22-3333',
            '+1-66-77899',
            null,
            null,
            static::providerInfo('Starwood Hotels', 'spg', [['xxxxxx345', true]], '4 nights'),
            static::travelAgency(
                'Expedia.com',
                'expedia',
                $travelAgencyConfNumbers
            ),
            static::pricingInfo(
                300,
                200,
                'USD',
                40,
                '10000 points',
                [
                    ['Tax', 100],
                ]
            ),
            'Confirmed',
            date_create('2030-01-01T13:30:00')->modify('-1 day'),
            'Cancellation is free 24 hours prior to check-in',
            "Use the secondary entrance if you're carrying large luggage",
            false,
            null,
            date_create('2029-12-31T13:30:00')->modify('-1 day'),
        );
    }
}
