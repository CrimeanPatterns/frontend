<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\Common\Entity\Geotag as EntityGeotag;
use AwardWallet\MainBundle\Entity\Aircraft as EntityAircraft;
use AwardWallet\MainBundle\Entity\Airline as EntityAirline;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Repositories\AircraftRepository;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Entity\TicketNumber as EntityTicketNumber;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityTripSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\FlightSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\FlightConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

/**
 * @group frontend-unit
 */
class FlightConverterTest extends AbstractSegmentConverterTest
{
    public function testValidateSchema()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', SchemaFlight::class, SchemaEvent::class));
        $this->getConverter()->convert(
            new SchemaEvent(),
            null,
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

        $entityItinerary = $this->getConverter([], [], [
            'search' => (new EntityAirline())->setName('Test Airline'),
        ])->convert(
            $schemaItinerary,
            $entityItinerary ?? null,
            $this->getEmailSavingOptions()
        );

        if ($update) {
            $this->assertEquals(['John Smith'], $entityItinerary->getTravelerNames());
        } else {
            $this->assertEquals(['Jess Sisi', 'Den Sisi'], $entityItinerary->getTravelerNames());
        }

        $this->assertNotNull($entityItinerary->getAirline());
        $this->assertEquals('Test Airline', $entityItinerary->getAirlineName());
        $this->assertEquals('new conf', $entityItinerary->getIssuingAirlineConfirmationNumber());
        $this->assertEquals(['new conf'], $entityItinerary->getProviderConfirmationNumbers());
        $this->assertEquals('9999', $entityItinerary->getPhone());
        $this->assertEquals([
            new EntityTicketNumber('5555'),
            new EntityTicketNumber('33**', true),
        ], $entityItinerary->getTicketNumbers());

        if ($update) {
            $this->assertCount(3, $segments = $entityItinerary->getSegments());
            $this->assertFalse($segments[0]->isHiddenByUpdater());
            $this->assertTrue($segments[1]->isHiddenByUpdater());
            $this->assertFalse($segments[2]->isHiddenByUpdater());
        } else {
            $this->assertCount(2, $segments = $entityItinerary->getSegments());
            $this->assertTrue($segments[0]->isHiddenByUpdater());
            $this->assertFalse($segments[1]->isHiddenByUpdater());
        }
    }

    /**
     * Tests that segments with the same origin and destination but different intermediate stops
     * are handled correctly when updating a Trip.
     */
    public function testUpdateWithDifferentStops()
    {
        // Create a trip with segments CTG-MDE-GRU
        $trip = $this->getDefaultEntityItinerary();

        // Replace segments with our test route
        $trip->getSegments()->clear();

        // Create geotags for segments
        $ctgGeotag = (new EntityGeotag())->setAddress('CTG Address');
        $mdeGeotag = (new EntityGeotag())->setAddress('MDE Address');
        $gruGeotag = (new EntityGeotag())->setAddress('GRU Address');

        // First segment: CTG to MDE
        $segment1 = new EntityTripSegment();
        $segment1->setDepcode('CTG');
        $segment1->setDepname('CTG Airport');
        $segment1->setDepgeotagid($ctgGeotag);
        $segment1->setArrcode('MDE');
        $segment1->setArrname('MDE Airport');
        $segment1->setArrgeotagid($mdeGeotag);
        $segment1->setDepartureDate(new \DateTime('2023-01-01 08:00:00'));
        $segment1->setArrivalDate(new \DateTime('2023-01-01 10:00:00'));
        $segment1->setMarketingAirlineConfirmationNumber('confNumber1');
        $trip->addSegment($segment1);

        // Second segment: MDE to GRU
        $segment2 = new EntityTripSegment();
        $segment2->setDepcode('MDE');
        $segment2->setDepname('MDE Airport');
        $segment2->setDepgeotagid($mdeGeotag);
        $segment2->setArrcode('GRU');
        $segment2->setArrname('GRU Airport');
        $segment2->setArrgeotagid($gruGeotag);
        $segment2->setDepartureDate(new \DateTime('2023-01-01 12:00:00'));
        $segment2->setArrivalDate(new \DateTime('2023-01-01 16:00:00'));
        $segment2->setMarketingAirlineConfirmationNumber('confNumber1');
        $trip->addSegment($segment2);

        // Set confirmation number for the trip
        $trip->setIssuingAirlineConfirmationNumber('confNumber1');

        // Create a schema with segments CTG-BOG-GRU (different intermediate stop)
        $schemaItinerary = $this->getSchemaItinerary();
        $schemaItinerary->segments = [
            // First segment: CTG to BOG
            SchemaBuilder::makeSchemaFlightSegment(
                SchemaBuilder::makeSchemaTripLocation(
                    'CTG Airport',
                    new \DateTime('2023-01-01 08:30:00'),
                    $this->getSchemaAddress('CTG Address'),
                    'CTG'
                ),
                SchemaBuilder::makeSchemaTripLocation(
                    'BOG Airport',
                    new \DateTime('2023-01-01 10:30:00'),
                    $this->getSchemaAddress('BOG Address'),
                    'BOG'
                ),
                SchemaBuilder::makeSchemaMarketingCarrier(
                    SchemaBuilder::makeSchemaAirline('Test Airline', 'TA'),
                    '123',
                    'confNumber1'
                )
            ),
            // Second segment: BOG to GRU
            SchemaBuilder::makeSchemaFlightSegment(
                SchemaBuilder::makeSchemaTripLocation(
                    'BOG Airport',
                    new \DateTime('2023-01-01 12:30:00'),
                    $this->getSchemaAddress('BOG Address'),
                    'BOG'
                ),
                SchemaBuilder::makeSchemaTripLocation(
                    'GRU Airport',
                    new \DateTime('2023-01-01 16:30:00'),
                    $this->getSchemaAddress('GRU Address'),
                    'GRU'
                ),
                SchemaBuilder::makeSchemaMarketingCarrier(
                    SchemaBuilder::makeSchemaAirline('Test Airline', 'TA'),
                    '124',
                    'confNumber1'
                )
            ),
        ];

        // Make sure the schema passes validation
        $schemaItinerary->issuingCarrier = SchemaBuilder::makeSchemaIssuingCarrier(
            SchemaBuilder::makeSchemaAirline('Test Airline', 'TA'),
            'confNumber1'
        );

        // Convert the schema to update the trip
        $converter = $this->getConverter([], [], [
            'search' => fn (?string $icao, ?string $iata) => (new EntityAirline())->setName('Test Airline'),
        ]);

        $options = $this->getEmailSavingOptions();

        // Get IDs of original segments for later comparison
        $originalSegmentIds = array_map(function ($segment) {
            return spl_object_hash($segment);
        }, $trip->getSegments()->toArray());

        // Update the trip
        $updatedTrip = $converter->convert($schemaItinerary, $trip, $options);

        // After update, check:
        // 1. That original segments are now hidden/cancelled
        $hiddenCount = 0;

        foreach ($updatedTrip->getSegments() as $segment) {
            if (in_array(spl_object_hash($segment), $originalSegmentIds) && $segment->getHidden()) {
                $hiddenCount++;
            }
        }

        // Since we had 2 original segments, both should be hidden now
        $this->assertEquals(2, $hiddenCount, 'Original segments should be hidden when route has different stops');

        // 2. That new segments were added
        $newSegments = 0;

        foreach ($updatedTrip->getSegments() as $segment) {
            if (!in_array(spl_object_hash($segment), $originalSegmentIds) && !$segment->getHidden()) {
                $newSegments++;
            }
        }

        // Should have 2 new visible segments
        $this->assertEquals(2, $newSegments, 'New segments should be added for the updated route');

        // 3. Check that the new segments are for the correct route (CTG-BOG-GRU)
        $segmentRoutes = [];

        foreach ($updatedTrip->getSegments() as $segment) {
            if (!in_array(spl_object_hash($segment), $originalSegmentIds) && !$segment->getHidden()) {
                $segmentRoutes[] = $segment->getDepcode() . '-' . $segment->getArrcode();
            }
        }

        sort($segmentRoutes); // Sort to ensure consistent order
        $this->assertEquals(['BOG-GRU', 'CTG-BOG'], $segmentRoutes, 'New segments should have the correct route');
    }

    /**
     * @dataProvider modesProvider
     */
    public function testConvertSegment(bool $update)
    {
        $schemaItinerary = $this->getSchemaItinerary();
        $entityItinerary = $this->getDefaultEntityItinerary();
        $entitySegment = $this->getConverter([], [], [
            'search' => fn (?string $icao, ?string $iata) => (new EntityAirline())->setName('new test airline ' . $iata),
        ], [
            'findOneBy' => fn (array $criteria) => (new EntityAircraft())->setName('new aircraft ' . $criteria['IataCode'] ?? null),
        ])->convertSegment(
            $schemaItinerary,
            $schemaItinerary->segments[0],
            $entityItinerary,
            $update ? $entityItinerary->getSegments()[0] : null,
            $this->getEmailSavingOptions()
        );

        $this->assertEquals('new dep code', $entitySegment->getDepcode());
        $this->assertEquals('new dep terminal', $entitySegment->getDepartureTerminal());
        $this->assertEquals('new dep name', $entitySegment->getDepname());
        $this->assertEquals('2000-01-05 01:00:00', $entitySegment->getDepartureDate()->format('Y-m-d H:i:s'));
        $this->assertEquals('2000-01-05 01:00:00', $entitySegment->getScheduledDepDate()->format('Y-m-d H:i:s'));
        $this->assertNotNull($entitySegment->getDepgeotagid());
        $this->assertEquals('new dep address', $entitySegment->getDepgeotagid()->getAddress());

        $this->assertEquals('new arr code', $entitySegment->getArrcode());
        $this->assertEquals('new arr terminal', $entitySegment->getArrivalTerminal());
        $this->assertEquals('new arr name', $entitySegment->getArrname());
        $this->assertEquals('2000-01-05 05:00:00', $entitySegment->getArrivalDate()->format('Y-m-d H:i:s'));
        $this->assertEquals('2000-01-05 05:00:00', $entitySegment->getScheduledArrDate()->format('Y-m-d H:i:s'));
        $this->assertNotNull($entitySegment->getArrgeotagid());
        $this->assertEquals('new arr address', $entitySegment->getArrgeotagid()->getAddress());

        $this->assertEquals('new test airline AA', $entitySegment->getAirlineName());
        $this->assertNotNull($entitySegment->getAirline());
        $this->assertEquals('new test airline AA', $entitySegment->getAirline()->getName());
        $this->assertEquals('300', $entitySegment->getFlightNumber());
        $this->assertEquals('new conf', $entitySegment->getMarketingAirlineConfirmationNumber());
        $this->assertEquals(['new phone 1', 'new phone 2'], $entitySegment->getMarketingAirlinePhoneNumbers());
        $this->assertEquals('new test airline BB', $entitySegment->getOperatingAirlineName());
        $this->assertNotNull($entitySegment->getOperatingAirline());
        $this->assertEquals('new test airline BB', $entitySegment->getOperatingAirline()->getName());
        $this->assertEquals('400', $entitySegment->getOperatingAirlineFlightNumber());
        $this->assertEquals('new oper conf', $entitySegment->getOperatingAirlineConfirmationNumber());
        $this->assertEquals(['new phone 1', 'new phone 2'], $entitySegment->getOperatingAirlinePhoneNumbers());
        $this->assertEquals('new test airline AA', $entitySegment->getWetLeaseAirlineName());
        $this->assertNotNull($entitySegment->getWetLeaseAirline());
        $this->assertEquals('new test airline AA', $entitySegment->getWetLeaseAirline()->getName());
        $this->assertEquals(['new seat 1', 'new seat 2'], $entitySegment->getSeats());
        $this->assertEquals('new aircraft CC', $entitySegment->getAircraftName());
        $this->assertNotNull($entitySegment->getAircraft());
        $this->assertEquals('new aircraft CC', $entitySegment->getAircraft()->getName());
        $this->assertEquals('new miles', $entitySegment->getTraveledMiles());
        $this->assertEquals('new cabin', $entitySegment->getCabinClass());
        $this->assertEquals('new book', $entitySegment->getBookingClass());
        $this->assertEquals('new duration', $entitySegment->getDuration());
        $this->assertEquals('new meal', $entitySegment->getMeal());
        $this->assertFalse($entitySegment->isSmoking());
        $this->assertEquals('new status', $entitySegment->getParsedStatus());
        $this->assertTrue($entitySegment->isHiddenByUpdater());
        $this->assertEquals(3, $entitySegment->getStops());
    }

    protected function getConverter(
        array $geo = [],
        array $providerRep = [],
        array $airlineRep = [],
        array $aircraftRep = []
    ): FlightConverter {
        return new FlightConverter(
            new LoggerFactory($this->getLogger(true)),
            $this->getBaseConverter($providerRep),
            $this->getHelper($geo),
            $this->container->get(FlightSegmentMatcher::class),
            $this->makeEmpty(Validator::class, ['getLiveSources' => fn (array $sources) => $sources]),
            $this->makeEmpty(AirlineRepository::class, $airlineRep),
            $this->makeEmpty(AircraftRepository::class, $aircraftRep)
        );
    }

    /**
     * @return EntityItinerary|EntityTrip
     */
    protected function getDefaultEntityItinerary(): EntityItinerary
    {
        $this->setupEntityItinerary($entityItinerary = new EntityTrip());

        $entityItinerary->setAirline((new EntityAirline())->setName('Old Test Airline'));
        $entityItinerary->setAirlineName('Old Test Airline');
        $entityItinerary->setIssuingAirlineConfirmationNumber('Old Issuing Airline Confirmation Number');
        $entityItinerary->setProviderConfirmationNumbers(['Old Provider Confirmation Number']);
        $entityItinerary->setPhone('old phone');
        $entityItinerary->setTicketNumbers([
            new EntityTicketNumber('old number'),
            new EntityTicketNumber('old number 2', true),
        ]);

        $segment1 = new EntityTripSegment();
        $segment1->setDepcode('old dep code');
        $segment1->setDepartureTerminal('old dep terminal');
        $segment1->setDepname('old dep name');
        $segment1->setDepartureDate(new \DateTime('2000-01-01 00:00:00'));
        $segment1->setScheduledDepDate(new \DateTime('2000-01-01 00:00:00'));
        $segment1->setDepgeotagid((new EntityGeotag())->setAddress('old dep geotag'));
        $segment1->setArrcode('old arr code');
        $segment1->setArrivalTerminal('old arr terminal');
        $segment1->setArrname('old arr name');
        $segment1->setArrivalDate(new \DateTime('2000-01-01 03:00:00'));
        $segment1->setScheduledArrDate(new \DateTime('2000-01-01 03:00:00'));
        $segment1->setArrgeotagid((new EntityGeotag())->setAddress('old arr geotag'));
        $segment1->setAirline((new EntityAirline())->setName('old airline'));
        $segment1->setAirlineName('old airline');
        $segment1->setFlightNumber('old flight number');
        $segment1->setMarketingAirlineConfirmationNumber('old marketing airline conf number');
        $segment1->setMarketingAirlinePhoneNumbers(['old marketing phone number']);
        $segment1->setOperatingAirline((new EntityAirline())->setName('old airline'));
        $segment1->setOperatingAirlineName('old airline');
        $segment1->setOperatingAirlineFlightNumber('old operating flight number');
        $segment1->setOperatingAirlineConfirmationNumber('old operating conf number');
        $segment1->setOperatingAirlinePhoneNumbers(['old operating phone number']);
        $segment1->setWetLeaseAirline((new EntityAirline())->setName('old airline'));
        $segment1->setWetLeaseAirlineName('old airline');
        $segment1->setSeats(['old seat 1', 'old seat 2']);
        $segment1->setAircraft((new EntityAircraft())->setName('old aircraft'));
        $segment1->setAircraftName('old aircraft');
        $segment1->setTraveledMiles('old traveled miles');
        $segment1->setCabinClass('old cabin');
        $segment1->setBookingClass('old booking class');
        $segment1->setDuration('old duration');
        $segment1->setMeal('old meal');
        $segment1->setSmoking(true);
        $segment1->setParsedStatus('old status');
        $segment1->setStops(1);
        $entityItinerary->addSegment($segment1);

        return $entityItinerary;
    }

    /**
     * @return SchemaItinerary|SchemaFlight
     */
    protected function getSchemaItinerary(): SchemaItinerary
    {
        return SchemaBuilder::makeSchemaFlight(
            [
                SchemaBuilder::makeSchemaFlightSegment(
                    SchemaBuilder::makeSchemaTripLocation(
                        'new dep name',
                        new \DateTime('2000-01-05 01:00:00'),
                        $this->getSchemaAddress('new dep address'),
                        'new dep code',
                        'new dep terminal'
                    ),
                    SchemaBuilder::makeSchemaTripLocation(
                        'new arr name',
                        new \DateTime('2000-01-05 05:00:00'),
                        $this->getSchemaAddress('new arr address'),
                        'new arr code',
                        'new arr terminal'
                    ),
                    SchemaBuilder::makeSchemaMarketingCarrier(
                        SchemaBuilder::makeSchemaAirline('new test airline AA', 'AA'),
                        '300',
                        'new conf',
                        [
                            SchemaBuilder::makeSchemaPhoneNumber('new phone 1'),
                            SchemaBuilder::makeSchemaPhoneNumber('new phone 2'),
                        ]
                    ),
                    SchemaBuilder::makeSchemaOperatingCarrier(
                        SchemaBuilder::makeSchemaAirline('new test airline BB', 'BB'),
                        '400',
                        'new oper conf',
                        [
                            SchemaBuilder::makeSchemaPhoneNumber('new phone 1'),
                            SchemaBuilder::makeSchemaPhoneNumber('new phone 2'),
                        ]
                    ),
                    SchemaBuilder::makeSchemaAirline('new test airline AA', 'AA'),
                    ['new seat 1', 'new seat 2'],
                    SchemaBuilder::makeSchemaAircraft('new aircraft CC', 'CC'),
                    'new miles',
                    'new cabin',
                    'new book',
                    'new duration',
                    'new meal',
                    false,
                    'new status',
                    true,
                    3
                ),
                SchemaBuilder::makeSchemaFlightSegment(
                    SchemaBuilder::makeSchemaTripLocation(
                        'new dep name 2',
                        new \DateTime('2000-01-06 01:00:00'),
                        $this->getSchemaAddress('new dep address 2'),
                        'new dep code 2',
                        'new dep terminal 2'
                    ),
                    SchemaBuilder::makeSchemaTripLocation(
                        'new arr name 2',
                        new \DateTime('2000-01-06 05:00:00'),
                        $this->getSchemaAddress('new arr address 2'),
                        'new arr code 2',
                        'new arr terminal 2'
                    ),
                    SchemaBuilder::makeSchemaMarketingCarrier(
                        SchemaBuilder::makeSchemaAirline('new test marketing airline'),
                        '400'
                    )
                ),
            ],
            [
                SchemaBuilder::makeSchemaPerson('Jess Sisi'),
                SchemaBuilder::makeSchemaPerson('Den Sisi'),
            ],
            SchemaBuilder::makeSchemaIssuingCarrier(
                SchemaBuilder::makeSchemaAirline('Test Airline', 'TT'),
                'new conf',
                [
                    SchemaBuilder::makeSchemaPhoneNumber('9999'),
                    SchemaBuilder::makeSchemaPhoneNumber('1111'),
                ],
                [
                    SchemaBuilder::makeSchemaParsedNumber('5555'),
                    SchemaBuilder::makeSchemaParsedNumber('33**', true),
                ]
            ),
            $this->getSchemaProviderInfo(),
            $this->getSchemaTravelAgency(),
            $this->getSchemaPricingInfo(),
            $this->getSchemaStatus(),
            $this->getSchemaReservationDate(),
            $this->getSchemaCancellationPolicy(),
            $this->getSchemaNotes()
        );
    }
}
