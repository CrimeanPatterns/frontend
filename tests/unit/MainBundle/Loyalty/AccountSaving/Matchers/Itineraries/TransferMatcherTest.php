<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTransfer;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\TransferMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TransferSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use AwardWallet\Schema\Itineraries\TransferSegment;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class TransferMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var TransferMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        /** @var TransferSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(TransferSegmentMatcher::class);
        $this->matcher = new TransferMatcher(
            $helper,
            $segmentMatcher,
            $this->makeEmpty(GeoLocationMatcher::class),
            new NullLogger()
        );
    }

    public function testSupports()
    {
        /** @var SchemaTransfer $schemaTransfer */
        $schemaTransfer = new SchemaTransfer();
        /** @var EntityTransfer $entityTransfer */
        $entityTransfer = new EntityTransfer();
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
        $this->assertTrue($this->matcher->supports($entityTransfer, $schemaTransfer));
        $this->assertFalse($this->matcher->supports($entityTransfer, new Cruise()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaTransfer));
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
        $this->matcher->match($invalidEntity, new SchemaTransfer());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityTransfer(), new Cruise());
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
        /** @var TransferSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(TransferSegmentMatcher::class, [
            'match' => .0,
        ]);
        $matcher = new TransferMatcher($helper, $segmentMatcher, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityTransfer $entityTransfer */
        $entityTransfer = $this->makeEmpty(EntityTransfer::class, [
            'getConfirmationNumber' => $confirmationNumber,
            'getSegments' => new ArrayCollection(),
        ]);
        /** @var SchemaTransfer $schemaTransfer */
        $schemaTransfer = $this->getSchemaTransfer();
        $this->assertSame($expectedConfidence, $matcher->match($entityTransfer, $schemaTransfer));
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
        /** @var TransferSegmentMatcher $segmentMatcher */
        $segmentMatcher = $this->makeEmpty(TransferSegmentMatcher::class, [
            'match' => 1.0,
        ]);
        $matcher = new TransferMatcher($helper, $segmentMatcher, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityTransfer $entityTransfer */
        $entityTransfer = $this->makeEmpty(EntityTransfer::class, [
            'getConfirmationNumber' => 'different_number',
            'getSegments' => new ArrayCollection([$this->makeEmpty(Tripsegment::class)]),
        ]);
        /** @var SchemaTransfer $schemaTransfer */
        $schemaTransfer = $this->getSchemaTransfer();
        $this->assertSame(1.0, $matcher->match($entityTransfer, $schemaTransfer));
    }

    private function getSchemaTransfer(): SchemaTransfer
    {
        $Transfer = new SchemaTransfer();
        $Transfer->confirmationNumbers = [new ConfNo()];
        $Transfer->confirmationNumbers[0]->number = 'same_number';
        $Transfer->confirmationNumbers[0]->isPrimary = true;
        $Transfer->segments = [new TransferSegment()];

        return $Transfer;
    }
}
