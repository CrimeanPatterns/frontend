<?php

namespace AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @NoDI()
 */
class Event extends AbstractFakeBuilder
{
    /**
     * @var SchemaItinerary|SchemaEvent
     */
    protected SchemaItinerary $schemaItinerary;

    public function __construct(SchemaEvent $schemaBus)
    {
        parent::__construct($schemaBus);
    }

    public static function restaurantLoiEstiatorio(
        ?array $confNumbers = [
            ['A04-33984-12', true, 'Confirmation #'],
            ['887756', false, 'Transaction number'],
        ],
        ?array $travelAgencyConfNumbers = ['98765', '11122', 'J3HND-8776']
    ): SchemaEvent {
        return SchemaBuilder::makeSchemaEvent(
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
            Restaurant::EVENT_RESTAURANT,
            'Loi Estiatorio',
            date_create('2030-01-01T18:00:00'),
            date_create('2030-01-01T23:00:00'),
            static::persons(['John Smith', 'Jess Sisi']),
            2,
            ['table 13'],
            static::confNumbers($confNumbers ?? []),
            '+1-23-44556',
            '+1-99-33434',
            static::providerInfo('OpenTable.com', 'opentable', ['AM3398'], '50 points'),
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
            date_create('2030-01-01T18:00:00')->modify('-1 day'),
            'Ticket is non-refundable',
            'Receipt will be available in your mobile application',
            false
        );
    }
}
