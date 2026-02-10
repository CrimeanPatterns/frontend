<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\CruiseSegment as SchemaCruiseSegment;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI()
 */
class Cruise extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaCruise
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaCruise $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function portCanaveraToNassau(
        ?array $confNumbers = [
            ['A04-33984-12', true, 'Confirmation #'],
            ['887756', false, 'Transaction number'],
        ],
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaCruise {
        return SchemaBuilder::makeSchemaCruise(
            [
                SchemaBuilder::makeSchemaCruiseSegment(
                    SchemaBuilder::makeSchemaTransportLocation(
                        'PORT CANAVERAL',
                        date_create('2030-01-01T13:30:00'),
                        static::address('PORT CANAVERAL')
                    ),
                    SchemaBuilder::makeSchemaTransportLocation(
                        'NASSAU',
                        date_create('2030-01-02T08:00:00'),
                        static::address(
                            'Nassau',
                            null,
                            null,
                            null,
                            'Bahamas',
                            'BS',
                            null,
                            25.0479835,
                            -77.355413,
                            -14400,
                            'America/Nassau'
                        )
                    ),
                ),
                SchemaBuilder::makeSchemaCruiseSegment(
                    SchemaBuilder::makeSchemaTransportLocation(
                        'NASSAU',
                        date_create('2030-01-02T12:00:00'),
                        static::address(
                            'Nassau',
                            null,
                            null,
                            null,
                            'Bahamas',
                            'BS',
                            null,
                            25.0479835,
                            -77.355413,
                            -14400,
                            'America/Nassau'
                        )
                    ),
                    SchemaBuilder::makeSchemaTransportLocation(
                        'PORT CANAVERAL',
                        date_create('2030-01-03T14:00:00'),
                        static::address('PORT CANAVERAL')
                    ),
                ),
            ],
            SchemaBuilder::makeSchemaCruiseDetails(
                'Long cruise',
                'Regular',
                '7',
                '342',
                'Disney Dream',
                'SHCD',
                'K229'
            ),
            static::persons(['John Smith', 'Jess Sisi']),
            static::confNumbers($confNumbers ?? []),
            static::providerInfo('Disney Cruise Line', 'disneycruise', ['AM3398'], '50 points'),
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
            'Do not forget to check in with the receptionist at the main deck',
            false
        );
    }

    public function addSegment(SchemaCruiseSegment $segment): self
    {
        $this->schemaItinerary->segments[] = $segment;

        return $this;
    }

    /**
     * @param SchemaCruiseSegment[] $segments
     */
    public function setSegments(array $segments): self
    {
        $this->schemaItinerary->segments = $segments;

        return $this;
    }
}
