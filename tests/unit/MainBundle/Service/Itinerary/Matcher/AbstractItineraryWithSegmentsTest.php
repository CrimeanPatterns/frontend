<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\ItineraryMatcherInterface;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\SegmentMatcherInterface;
use AwardWallet\Tests\Modules\DbBuilder\AbstractItinerary;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;

abstract class AbstractItineraryWithSegmentsTest extends AbstractItineraryMatcherTest
{
    public function dataProvider(): array
    {
        return array_merge(
            parent::dataProvider(),
            [
                'match by segment' => [0.6, static::getEntity(null), static::getSchema(null), false, 1],
                'match by segment, different conf no' => [
                    0.6,
                    static::getEntity('12345'),
                    static::getSchema('67890'),
                    false,
                    1,
                ],
                'match by segment, different travel agency conf no' => [
                    0.6,
                    static::getEntity('12345', ['abc']),
                    static::getSchema('67890', ['def']),
                    false,
                    1,
                ],
                'not match by segment' => [0, static::getEntity(null), static::getSchema(null), false, 0],
            ]
        );
    }

    abstract protected function getMatcher(GeoLocationMatcher $locationMatcher, SegmentMatcherInterface $segmentMatcher): ItineraryMatcherInterface;

    /**
     * @return TripSegment[]
     */
    abstract protected static function getEntitySegments(): array;

    /**
     * @return AbstractItinerary|Trip
     */
    protected static function getEntity(
        ?string $confNo,
        ?array $travelAgencyConfNumbers = null,
        ?string $providerCode = null,
        ?string $travelAgencyCode = null
    ): AbstractItinerary {
        $trip = new Trip(
            $confNo,
            static::getEntitySegments(),
            new User(), [
                'TravelAgencyConfirmationNumbers' => $travelAgencyConfNumbers ? implode(',', $travelAgencyConfNumbers) : null,
            ]);

        if ($providerCode) {
            $trip->setProvider(new Provider($providerCode, ['Code' => $providerCode]));
        }

        if ($travelAgencyCode) {
            $trip->setTravelAgency(new Provider($travelAgencyCode, ['Code' => $travelAgencyCode]));
        }

        return $trip;
    }
}
