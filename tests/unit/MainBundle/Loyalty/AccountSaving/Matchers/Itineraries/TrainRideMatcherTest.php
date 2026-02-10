<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTrainRide;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\TrainRideMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TrainRideSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\Train as SchemaTrainRide;
use AwardWallet\Schema\Itineraries\TrainSegment;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class TrainRideMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var TrainRideMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        /** @var TrainRideSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(TrainRideSegmentMatcher::class);
        $this->matcher = new TrainRideMatcher(
            $helper,
            $segmentMatcher,
            $this->makeEmpty(GeoLocationMatcher::class),
            new NullLogger()
        );
    }

    public function testSupports()
    {
        /** @var SchemaTrainRide $schemaTrainRide */
        $schemaTrainRide = new SchemaTrainRide();
        /** @var EntityTrainRide $entityTrainRide */
        $entityTrainRide = new EntityTrainRide();
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
        $this->assertTrue($this->matcher->supports($entityTrainRide, $schemaTrainRide));
        $this->assertFalse($this->matcher->supports($entityTrainRide, new Cruise()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaTrainRide));
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
        $this->matcher->match($invalidEntity, new SchemaTrainRide());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityTrainRide(), new Cruise());
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
        /** @var TrainRideSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(TrainRideSegmentMatcher::class, [
            'match' => .0,
        ]);
        $matcher = new TrainRideMatcher($helper, $segmentMatcher, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityTrainRide $entityTrainRide */
        $entityTrainRide = $this->makeEmpty(EntityTrainRide::class, [
            'getConfirmationNumber' => $confirmationNumber,
            'getSegments' => new ArrayCollection(),
        ]);
        /** @var SchemaTrainRide $schemaTrainRide */
        $schemaTrainRide = $this->getSchemaTrainRide();
        $this->assertSame($expectedConfidence, $matcher->match($entityTrainRide, $schemaTrainRide));
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
        /** @var TrainRideSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(TrainRideSegmentMatcher::class, [
            'match' => 1.0,
        ]);
        $matcher = new TrainRideMatcher($helper, $segmentMatcher, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityTrainRide $entityTrainRide */
        $entityTrainRide = $this->makeEmpty(EntityTrainRide::class, [
            'getConfirmationNumber' => 'different_number',
            'getSegments' => new ArrayCollection([$this->makeEmpty(Tripsegment::class)]),
        ]);
        /** @var SchemaTrainRide $schemaTrainRide */
        $schemaTrainRide = $this->getSchemaTrainRide();
        $this->assertSame(1.0, $matcher->match($entityTrainRide, $schemaTrainRide));
    }

    private function getSchemaTrainRide(): SchemaTrainRide
    {
        $TrainRide = new SchemaTrainRide();
        $TrainRide->confirmationNumbers = [new ConfNo()];
        $TrainRide->confirmationNumbers[0]->number = 'same_number';
        $TrainRide->confirmationNumbers[0]->isPrimary = true;
        $TrainRide->segments = [new TrainSegment()];

        return $TrainRide;
    }
}
