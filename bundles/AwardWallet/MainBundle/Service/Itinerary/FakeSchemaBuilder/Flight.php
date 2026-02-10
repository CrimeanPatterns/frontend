<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI()
 */
class Flight extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaFlight
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaFlight $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function laxToSfo(
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaFlight {
        return SchemaBuilder::makeSchemaFlight(
            [
                SchemaBuilder::makeSchemaFlightSegment(
                    SchemaBuilder::makeSchemaTripLocation(
                        'Los Angeles International Airport',
                        date_create('2030-01-01T13:30:00'),
                        static::address(
                            'LAX',
                            '1 World Way',
                            'Los Angeles',
                            'California',
                            'United States',
                            'US',
                            '90045',
                            33.9415889,
                            -118.40853,
                            -25200,
                            'America/Los_Angeles'
                        ),
                        'LAX',
                        'A'
                    ),
                    SchemaBuilder::makeSchemaTripLocation(
                        'San Francisco International Airport',
                        date_create('2030-01-01T15:00:00'),
                        static::address(
                            'SFO',
                            'San Francisco International Airport',
                            'San Francisco',
                            'California',
                            'United States',
                            'US',
                            null,
                            37.615215,
                            -122.389881,
                            -25200,
                            'America/Los_Angeles'
                        ),
                        'SFO',
                        '2'
                    ),
                    SchemaBuilder::makeSchemaMarketingCarrier(
                        SchemaBuilder::makeSchemaAirline('Delta Air Lines', 'DL', 'DAL'),
                        '0013',
                        'MRTG67',
                        [
                            SchemaBuilder::makeSchemaPhoneNumber('+1-404-714-2300'),
                        ]
                    ),
                    SchemaBuilder::makeSchemaOperatingCarrier(
                        SchemaBuilder::makeSchemaAirline('British Airways', 'BA', 'BAW'),
                        '5566',
                        'CARR23',
                        [
                            SchemaBuilder::makeSchemaPhoneNumber('+1-718-335-7070'),
                        ]
                    ),
                    SchemaBuilder::makeSchemaAirline('Sky Express'),
                    ['3E', '3F'],
                    SchemaBuilder::makeSchemaAircraft(
                        'Boeing 737MAX 7 Passenger',
                        '7M7',
                        false,
                        true,
                        false,
                        false,
                        'N345DL'
                    ),
                    '300mi',
                    'Coach',
                    'CL',
                    '1h30m',
                    'Snacks',
                    false,
                    'Confirmed',
                    false,
                    0
                ),

                SchemaBuilder::makeSchemaFlightSegment(
                    SchemaBuilder::makeSchemaTripLocation(
                        'San Francisco International Airport',
                        date_create('2030-01-05T06:00:00'),
                        static::address(
                            'SFO',
                            'San Francisco International Airport',
                            'San Francisco',
                            'California',
                            'United States',
                            'US',
                            null,
                            37.615215,
                            -122.389881,
                            -25200,
                            'America/Los_Angeles'
                        ),
                        'SFO',
                        '2'
                    ),
                    SchemaBuilder::makeSchemaTripLocation(
                        'Los Angeles International Airport',
                        date_create('2030-01-05T07:30:00'),
                        static::address(
                            'LAX',
                            '1 World Way',
                            'Los Angeles',
                            'California',
                            'United States',
                            'US',
                            '90045',
                            33.9415889,
                            -118.40853,
                            -25200,
                            'America/Los_Angeles'
                        ),
                        'LAX',
                        'A'
                    ),
                    SchemaBuilder::makeSchemaMarketingCarrier(
                        SchemaBuilder::makeSchemaAirline('Delta Air Lines', 'DL', 'DAL'),
                        '0014',
                        'MRTG67',
                        [
                            SchemaBuilder::makeSchemaPhoneNumber('+1-404-714-2300'),
                        ],
                        true
                    ),
                    SchemaBuilder::makeSchemaOperatingCarrier(
                        SchemaBuilder::makeSchemaAirline('British Airways', 'BA', 'BAW'),
                        '9009',
                        'CARR23',
                        [
                            SchemaBuilder::makeSchemaPhoneNumber('+1-718-335-7070'),
                        ]
                    ),
                    null,
                    ['1B', '1C'],
                    SchemaBuilder::makeSchemaAircraft(
                        'Boeing 737MAX 7 Passenger',
                        '7M7',
                        false,
                        true,
                        false,
                        false,
                        'N345DL'
                    ),
                    '300mi',
                    'First class',
                    'I',
                    '1h30m',
                    'Snacks',
                    null,
                    'Confirmed',
                    false
                ),
            ],
            static::persons(['John Smith', 'Jess Sisi']),
            SchemaBuilder::makeSchemaIssuingCarrier(
                SchemaBuilder::makeSchemaAirline('Delta Air Lines', 'DL', 'DAL'),
                'ISSD12',
                [
                    SchemaBuilder::makeSchemaPhoneNumber('+1-404-714-2300'),
                ],
                static::parsedNumbers(['006 123321', '006 456654'])
            ),
            static::providerInfo('Delta Air Lines', 'delta', [['1234****', true], ['4321****', true]], '300 award miles'),
            static::travelAgency(
                'Expedia.com',
                'expedia',
                $travelAgencyConfNumbers
            ),
            static::pricingInfo(
                150,
                100,
                'USD',
                28.34,
                '3 segments',
                [
                    ['Tax', 30],
                    ['Seat selection', 5.5],
                    ['Baggage fee', 14.5],
                ]
            ),
            'Confirmed',
            date_create('2030-01-01T13:30:00')->modify('-1 day'),
            'Ticket is non-refundable',
            'Transfer shuttle will be located at the terminal exit',
            false
        );
    }

    public function addSegment(SchemaFlightSegment $segment): self
    {
        $this->schemaItinerary->segments[] = $segment;

        return $this;
    }

    /**
     * @param SchemaFlightSegment[] $segments
     */
    public function setSegments(array $segments): self
    {
        $this->schemaItinerary->segments = $segments;

        return $this;
    }
}
