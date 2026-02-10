<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Bus as SchemaBus;
use AwardWallet\Schema\Itineraries\BusSegment as SchemaBusSegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI()
 */
class Bus extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaBus
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaBus $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function bostonToNewYork(
        ?array $confNumbers = [
            ['A04-33984-12', true, 'Confirmation #'],
            ['887756', false, 'Transaction number'],
        ],
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaBus {
        return SchemaBuilder::makeSchemaBus(
            [
                SchemaBuilder::makeSchemaBusSegment(
                    SchemaBuilder::makeSchemaTransportLocation(
                        'Boston South Station - Gate 9 NYC-Gate 10 NWK/PHL',
                        date_create('2030-01-01T13:30:00'),
                        static::address(
                            'Boston South Station - Gate 9 NYC-Gate 10 NWK/PHL',
                            '700 Atlantic Avenue',
                            'Boston',
                            'Massachusetts',
                            'United States',
                            'US',
                            '02111',
                            42.3504505,
                            -71.0561242,
                            -14400,
                            'America/New_York'
                        )
                    ),
                    SchemaBuilder::makeSchemaTransportLocation(
                        'New York W 33rd St & 11-12th Ave (DC,BAL,BOS,PHL)',
                        date_create('2030-01-01T20:34:00'),
                        static::address('New York W 33rd St & 11-12th Ave (DC,BAL,BOS,PHL)')
                    ),
                    '2023',
                    SchemaBuilder::makeSchemaVehicle('Regular', 'Mercedes'),
                    ['11', '12'],
                    '43mi',
                    null,
                    null,
                    '7h',
                ),
            ],
            static::persons(['John Smith', 'Jess Sisi']),
            static::confNumbers($confNumbers ?? []),
            static::parsedNumbers(['345667', '345668']),
            static::providerInfo('BoltBus', 'boltbus', ['BB3398'], '50 points'),
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
            'Ticket is non-refundable',
            'Keep your ticket at your person at all times',
            false
        );
    }

    public function addSegment(SchemaBusSegment $segment): self
    {
        $this->schemaItinerary->segments[] = $segment;

        return $this;
    }

    /**
     * @param SchemaBusSegment[] $segments
     */
    public function setSegments(array $segments): self
    {
        $this->schemaItinerary->segments = $segments;

        return $this;
    }
}
