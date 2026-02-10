<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\CarRental as SchemaCarRental;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI()
 */
class CarRental extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaCarRental
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaCarRental $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function palmBeachIntlAirport(
        ?array $confNumbers = [
            ['1122334455', true, 'Confirmation number'],
            ['887756', false, 'Reference'],
        ],
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaCarRental {
        return SchemaBuilder::makeSchemaRental(
            SchemaBuilder::makeSchemaCarRentalLocation(
                static::address(
                    'Palm Beach Intl Airport,PBI, 2500 Turnage Boulevard, West Palm Beach, FL 33406 US',
                    '1000 James L Turnage Boulevard',
                    'West Palm Beach',
                    'Florida',
                    null,
                    'US',
                    '33415',
                    26.6857475,
                    -80.0928165,
                    -14400,
                    'America/New_York'
                ),
                date_create('2030-01-01T13:30:00'),
                'Sun - Sat open 24 hrs',
                '+1-13-PICKUP',
                '+1-14-FAX'
            ),
            SchemaBuilder::makeSchemaCarRentalLocation(
                static::address(
                    'Palm Beach Intl Airport,PBI, 2500 Turnage Boulevard, West Palm Beach, FL 33406 US',
                    '1000 James L Turnage Boulevard',
                    'West Palm Beach',
                    'Florida',
                    null,
                    'US',
                    '33415',
                    26.6857475,
                    -80.0928165,
                    -14400,
                    'America/New_York'
                ),
                date_create('2030-01-05T13:30:00'),
                'Sun - Sat open 24 hrs',
                '+1-13-PICKUP',
                '+1-14-FAX'
            ),
            static::person('John Smith'),
            static::confNumbers($confNumbers ?? []),
            SchemaBuilder::makeSchemaCar(
                'Regular',
                'Ford Edge or similar',
                'http://car.image/url'
            ),
            null,
            null,
            null,
            static::providerInfo('Avis', 'avis', ['AVS454545'], '50 points'),
            static::travelAgency(
                'Expedia.com',
                'expedia',
                $travelAgencyConfNumbers
            ),
            static::pricingInfo(
                251.41,
                193.75,
                'USD',
                40,
                '10000 points',
                [
                    ['Tax', 34.56],
                    ['Insurance', 23.1],
                ]
            ),
            'Confirmed',
            date_create('2030-01-01T13:30:00')->modify('-1 day'),
            '$50 penalty if cancelled',
            'Make a note of any damages before leaving the pickup area',
            false
        );
    }
}
