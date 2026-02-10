<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityCruise;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\CruiseMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\CruiseSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\CarRental;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\CruiseSegment;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class CruiseMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var CruiseMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        /** @var CruiseSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(CruiseSegmentMatcher::class);
        $this->matcher = new CruiseMatcher(
            $helper,
            $segmentMatcher,
            $this->makeEmpty(GeoLocationMatcher::class),
            new NullLogger()
        );
    }

    public function testSupports()
    {
        /** @var SchemaCruise $schemaCruise */
        $schemaCruise = new SchemaCruise();
        /** @var EntityCruise $entityCruise */
        $entityCruise = new EntityCruise();
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
        $this->assertTrue($this->matcher->supports($entityCruise, $schemaCruise));
        $this->assertFalse($this->matcher->supports($entityCruise, new CarRental()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaCruise));
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
        $this->matcher->match($invalidEntity, new SchemaCruise());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityCruise(), new CarRental());
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
        /** @var CruiseSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(CruiseSegmentMatcher::class, [
            'match' => .0,
        ]);
        $matcher = new CruiseMatcher($helper, $segmentMatcher, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityCruise $entityCruise */
        $entityCruise = $this->makeEmpty(EntityCruise::class, [
            'getConfirmationNumber' => $confirmationNumber,
            'getSegments' => new ArrayCollection(),
        ]);
        /** @var SchemaCruise $schemaCruise */
        $schemaCruise = $this->getSchemaCruise();
        $this->assertSame($expectedConfidence, $matcher->match($entityCruise, $schemaCruise));
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
        /** @var CruiseSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(CruiseSegmentMatcher::class, [
            'match' => 1.0,
        ]);
        $matcher = new CruiseMatcher($helper, $segmentMatcher, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityCruise $entityCruise */
        $entityCruise = $this->makeEmpty(EntityCruise::class, [
            'getConfirmationNumber' => 'different_number',
            'getSegments' => new ArrayCollection([$this->makeEmpty(Tripsegment::class)]),
        ]);
        /** @var SchemaCruise $schemaCruise */
        $schemaCruise = $this->getSchemaCruise();
        $this->assertSame(1.0, $matcher->match($entityCruise, $schemaCruise));
    }

    private function getSchemaCruise(): SchemaCruise
    {
        $Cruise = new SchemaCruise();
        $Cruise->confirmationNumbers = [new ConfNo()];
        $Cruise->confirmationNumbers[0]->number = 'same_number';
        $Cruise->confirmationNumbers[0]->isPrimary = true;
        $Cruise->segments = [new CruiseSegment()];

        return $Cruise;
    }
}
