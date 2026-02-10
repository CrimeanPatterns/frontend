<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Ferry as SchemaFerry;
use AwardWallet\Schema\Itineraries\FerrySegment as SchemaFerrySegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI()
 */
class Ferry extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaFerry
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaFerry $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function osloToKiel(
        ?array $confNumbers = [
            ['A04-33984-12', true, 'Confirmation #'],
            ['887756', false, 'Transaction number'],
        ],
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaFerry {
        return SchemaBuilder::makeSchemaFerry(
            [
                SchemaBuilder::makeSchemaFerrySegment(
                    SchemaBuilder::makeSchemaTransportLocation(
                        'Oslo, Norway',
                        date_create('2030-01-01T14:00:00'),
                        static::address(
                            'Akershusstranda 19, 0102 Oslo, Norway',
                            'Akershusstranda 19',
                            'Oslo',
                            null,
                            'Norway',
                            'NO',
                            '0102',
                            59.904139,
                            10.738278,
                        ),
                        'NO OSL'
                    ),
                    SchemaBuilder::makeSchemaTransportLocation(
                        'Kiel, Germany',
                        date_create('2030-01-02T10:00:00'),
                        static::address(
                            'Norwegenkai, 24143 Kiel, Germany',
                            null,
                            'Kiel',
                            null,
                            'Germany',
                            'DE',
                            null,
                            54.316333,
                            10.138806
                        ),
                        'DEKEL'
                    ),
                    [
                        '4-Bed inside cabin, shower/WC',
                        '4-Bed inside cabin, shower/WC',
                    ],
                    'ANEK SUPERFAST',
                    'Hellenic Spirit',
                    '10km',
                    '2h',
                    'none',
                    'economy',
                    false,
                    2,
                    1,
                    '1 cat',
                    [
                        SchemaBuilder::makeSchemaVehicleExt(
                            'Auto',
                            'Audi',
                            '<5m',
                            '1.8',
                            '1.8-3m'
                        ),
                    ],
                    [
                        SchemaBuilder::makeSchemaVehicleExt(
                            'Regular',
                            'Standard',
                            '<=2m',
                            '<=2',
                            '<=2'
                        ),
                    ],
                ),
            ],
            static::persons(['John Smith', 'Jess Sisi']),
            static::confNumbers($confNumbers ?? []),
            static::parsedNumbers(['345667', '345668']),
            static::providerInfo('AFerry', 'aferry', ['AM3398'], '50 points'),
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
            date_create('2030-01-01T14:00:00')->modify('-1 day'),
            'Ticket is non-refundable',
            'Access to your vehicle will be restricted during the trip',
            false
        );
    }

    public function addSegment(SchemaFerrySegment $segment): self
    {
        $this->schemaItinerary->segments[] = $segment;

        return $this;
    }

    /**
     * @param SchemaFerrySegment[] $segments
     */
    public function setSegments(array $segments): self
    {
        $this->schemaItinerary->segments = $segments;

        return $this;
    }
}
