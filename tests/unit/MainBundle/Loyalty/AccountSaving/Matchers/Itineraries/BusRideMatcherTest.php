<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityBusRide;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\BusRideMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\BusRideSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\Bus as SchemaBusRide;
use AwardWallet\Schema\Itineraries\BusSegment;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class BusRideMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var BusRideMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        /** @var BusRideSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(BusRideSegmentMatcher::class);
        $this->matcher = new BusRideMatcher(
            $helper,
            $segmentMatcher,
            $this->makeEmpty(GeoLocationMatcher::class),
            new NullLogger()
        );
    }

    public function testSupports()
    {
        /** @var SchemaBusRide $schemaBusRide */
        $schemaBusRide = new SchemaBusRide();
        /** @var EntityBusRide $entityBusRide */
        $entityBusRide = new EntityBusRide();
        $invalidEntity = new class() extends EntityItinerary {
            public function getStartDate()
            {
            }

            public function getEndDate()
            {
            }

            public function getUTCStartDate()
            {
            }

            public function getUTCEndDate()
            {
            }

            public function getPhones()
            {
            }

            public function getGeoTags()
            {
            }

            public function getType(): string
            {
                return '';
            }

            public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
            {
                return [];
            }

            public function getKind(): string
            {
                return 'T';
            }
        };
        $this->assertTrue($this->matcher->supports($entityBusRide, $schemaBusRide));
        $this->assertFalse($this->matcher->supports($entityBusRide, new Cruise()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaBusRide));
    }

    public function testUpdateWithWrongEntityType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $invalidEntity = new class() extends EntityItinerary {
            public function getStartDate()
            {
            }

            public function getEndDate()
            {
            }

            public function getUTCStartDate()
            {
            }

            public function getUTCEndDate()
            {
            }

            public function getPhones()
            {
            }

            public function getGeoTags()
            {
            }

            public function getType(): string
            {
                return '';
            }

            public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
            {
                return [];
            }

            public function getKind(): string
            {
                return 'T';
            }
        };
        $this->matcher->match($invalidEntity, new SchemaBusRide());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityBusRide(), new Cruise());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatch(
        float $expectedConfidence,
        string $confirmationNumber
    ) {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class, [
            'extractPrimaryConfirmationNumber' => 'same_number',
        ]);
        /** @var BusRideSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(BusRideSegmentMatcher::class, [
            'match' => .0,
        ]);
        $matcher = new BusRideMatcher($helper, $segmentMatcher, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityBusRide $entityBusRide */
        $entityBusRide = $this->makeEmpty(EntityBusRide::class, [
            'getConfirmationNumber' => $confirmationNumber,
            'getSegments' => new ArrayCollection(),
        ]);
        /** @var SchemaBusRide $schemaBusRide */
        $schemaBusRide = $this->getSchemaBusRide();
        $this->assertSame($expectedConfidence, $matcher->match($entityBusRide, $schemaBusRide));
    }

    public function dataProvider()
    {
        $sameConfirmationNumber = 'same_number';
        $differentConfirmationNumber = 'different_number';

        return [
            [
                .99,
                $sameConfirmationNumber,
            ],
            [
                .99,
                strtoupper($sameConfirmationNumber),
            ],
            [
                .00,
                $differentConfirmationNumber,
            ],
        ];
    }

    public function testMatchBySegments()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class, [
            'extractPrimaryConfirmationNumber' => 'same_number',
        ]);
        /** @var BusRideSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(BusRideSegmentMatcher::class, [
            'match' => 1.0,
        ]);
        $matcher = new BusRideMatcher($helper, $segmentMatcher, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityBusRide $entityBusRide */
        $entityBusRide = $this->makeEmpty(EntityBusRide::class, [
            'getConfirmationNumber' => 'different_number',
            'getSegments' => new ArrayCollection([$this->makeEmpty(Tripsegment::class)]),
        ]);
        /** @var SchemaBusRide $schemaBusRide */
        $schemaBusRide = $this->getSchemaBusRide();
        $this->assertSame(1.0, $matcher->match($entityBusRide, $schemaBusRide));
    }

    private function getSchemaBusRide(): SchemaBusRide
    {
        $busRide = new SchemaBusRide();
        $busRide->confirmationNumbers = [new ConfNo()];
        $busRide->confirmationNumbers[0]->number = 'same_number';
        $busRide->confirmationNumbers[0]->isPrimary = true;
        $busRide->segments = [new BusSegment()];

        return $busRide;
    }
}
