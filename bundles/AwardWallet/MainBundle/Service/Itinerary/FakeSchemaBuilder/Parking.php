<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Parking as SchemaParking;

/**
 * @NoDI()
 */
class Parking extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaParking
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaParking $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function downtown2hr(
        ?array $confNumbers = [
            ['A04-33984-12', true, 'Transcation number'],
            ['887756', false, 'Invoice number'],
        ],
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaParking {
        return SchemaBuilder::makeSchemaParking(
            date_create('2030-01-01T18:00:00'),
            date_create('2030-01-01T23:00:00'),
            static::person('John Doe'),
            static::confNumbers($confNumbers ?? []),
            static::address(
                '132 West 58th Street New York, NY 10019',
                '132 West 58th Street',
                'New York',
                'New York',
                'United States',
                'US',
                '10019',
                40.7653771,
                -73.9779742,
                -14400,
                'America/New_York'
            ),
            'Downtown 2 hr',
            '4',
            'ABC GHY',
            '+1-234-56789',
            'Sun - Sat open 24 hrs',
            'Volkswagen white',
            'STANDARD SPOT',
            static::providerInfo('The Parking Spot', 'parkingspot', ['AM3398'], '50 points'),
            static::travelAgency(
                'Expedia.com',
                'expedia',
                $travelAgencyConfNumbers
            ),
            static::pricingInfo(
                105.15,
                99.12,
                'USD',
                null,
                null,
                [
                    ['Taxes', 6.03],
                ]
            ),
            'Confirmed',
            date_create('2030-01-01T18:00:00')->modify('-1 day'),
            'Ticket is non-refundable',
            'Look at the overhead signs for directions to the desired spot',
            false
        );
    }
}
