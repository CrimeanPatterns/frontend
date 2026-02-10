<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Trip as EntityItinerary;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\FakeSchemaBuilder\Bus;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\BusMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\BusSegmentMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\ItineraryMatcherInterface;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\SegmentMatcherInterface;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;

class BusMatcherTest extends AbstractItineraryWithSegmentsTest
{
    /**
     * @dataProvider dataProvider
     */
    public function test(
        float $expected,
        Trip $entityItinerary,
        SchemaItinerary $schemaItinerary,
        bool $locationMatch = false,
        float $segmentMatch = 0
    ) {
        $entity = $this->em->find(
            EntityItinerary::class,
            $this->dbBuilder->makeTrip($entityItinerary)
        );
        $matcher = $this->getMatcher(
            $this->getGeoLocationMatcher($locationMatch),
            $this->makeEmpty(BusSegmentMatcher::class, ['match' => $segmentMatch])
        );
        $this->assertSame($expected, $matcher->match($entity, $schemaItinerary));
    }

    /**
     * @param SegmentMatcherInterface|BusSegmentMatcher $segmentMatcher
     */
    protected function getMatcher(GeoLocationMatcher $locationMatcher, SegmentMatcherInterface $segmentMatcher): ItineraryMatcherInterface
    {
        return new BusMatcher($this->getLogger(true), $locationMatcher, $segmentMatcher);
    }

    protected static function getEntitySegments(): array
    {
        return [
            new TripSegment(
                null,
                'Boston South Station - Gate 9 NYC-Gate 10 NWK/PHL',
                date_create('2024-01-01 12:00:00'),
                null,
                'New York W 33rd St & 11-12th Ave (DC,BAL,BOS,PHL)',
                date_create('2024-01-01 19:00:00')
            ),
        ];
    }

    protected static function getSchema(
        ?string $confNo,
        ?array $travelAgencyConfNumbers = null,
        ?string $providerCode = null,
        ?string $travelAgencyCode = null
    ): SchemaItinerary {
        $schema = Bus::bostonToNewYork(
            is_null($confNo) ? null : [[$confNo, true, 'Confirmation #']],
            $travelAgencyConfNumbers,
        );

        if ($providerCode && $schema->providerInfo) {
            $schema->providerInfo->code = $providerCode;
        }

        if ($travelAgencyCode && $schema->travelAgency->providerInfo ?? null) {
            $schema->travelAgency->providerInfo->code = $travelAgencyCode;
        }

        return $schema;
    }
}
