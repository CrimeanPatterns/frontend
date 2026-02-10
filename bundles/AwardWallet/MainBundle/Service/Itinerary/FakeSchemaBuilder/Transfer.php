<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use AwardWallet\Schema\Itineraries\TransferSegment as SchemaTransferSegment;

/**
 * @NoDI()
 */
class Transfer extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaTransfer
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaTransfer $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function fromSfo(
        ?array $confNumbers = [
            ['A04-33984-12', true, 'Transcation number'],
            ['887756', false, 'Invoice number'],
        ],
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaTransfer {
        return SchemaBuilder::makeSchemaTransfer(
            [
                SchemaBuilder::makeSchemaTransferSegment(
                    SchemaBuilder::makeSchemaTransferLocation(
                        'San Francisco International Airport',
                        date_create('2030-01-01T13:30:00'),
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
                        'SFO'
                    ),
                    SchemaBuilder::makeSchemaTransferLocation(
                        '315 Walnut Ave, South San Francisco, CA 94080, USA',
                        date_create('2030-01-01T14:34:00'),
                        static::address(
                            '315 Walnut Ave, South San Francisco, CA 94080, USA',
                            '315 Walnut Avenue',
                            'South San Francisco',
                            'California',
                            'United States',
                            'US',
                            '94080',
                            37.6569251,
                            -122.4143844,
                            -25200,
                            'America/Los_Angeles'
                        ),
                        'SFO'
                    ),
                    SchemaBuilder::makeSchemaCar('Regular', 'Ford Focus', 'http://car.image/url'),
                    1,
                    0,
                    '4.3mi',
                    '7h'
                ),
            ],
            static::persons(['John Smith']),
            static::confNumbers($confNumbers ?? []),
            static::providerInfo('Uber.com', 'uber', ['AM3398'], '50 points'),
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
            'You may be asked to provide an ID',
            false
        );
    }

    public function addSegment(SchemaTransferSegment $segment): self
    {
        $this->schemaItinerary->segments[] = $segment;

        return $this;
    }

    /**
     * @param SchemaTransferSegment[] $segments
     */
    public function setSegments(array $segments): self
    {
        $this->schemaItinerary->segments = $segments;

        return $this;
    }
}
