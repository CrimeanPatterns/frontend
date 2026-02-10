<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Parking as EntityParking;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\ParkingMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\Parking as SchemaParking;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class ParkingMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var ParkingMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        $this->matcher = new ParkingMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
    }

    public function testSupports()
    {
        /** @var SchemaParking $schemaParking */
        $schemaParking = new SchemaParking();
        /** @var EntityParking $entityParking */
        $entityParking = new EntityParking();
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
                return 'R';
            }
        };
        $this->assertTrue($this->matcher->supports($entityParking, $schemaParking));
        $this->assertFalse($this->matcher->supports($entityParking, new Cruise()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaParking));
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
                return 'R';
            }
        };
        $this->matcher->match($invalidEntity, new SchemaParking());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityParking(), new Cruise());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatch(
        float $expectedConfidence,
        ?string $schemaConfNo,
        ?string $entityConfNo,
        \DateTime $startDate,
        \DateTime $endDate,
        ?string $location
    ) {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class, [
            'extractPrimaryConfirmationNumber' => $schemaConfNo,
        ]);
        $matcher = new ParkingMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class, ['match' => true]), new NullLogger());
        /** @var EntityParking $entityParking */
        $entityParking = $this->makeEmpty(EntityParking::class, [
            'getConfirmationNumber' => $entityConfNo,
            'getStartDatetime' => $startDate,
            'getEndDatetime' => $endDate,
            'getLocation' => $location,
        ]);
        /** @var SchemaParking $schemaParking */
        $schemaParking = $this->getSchemaParking();
        $this->assertSame($expectedConfidence, $matcher->match($entityParking, $schemaParking));
    }

    public function dataProvider()
    {
        $sameConfirmationNumber = 'same_number';
        $differentConfirmationNumber = 'different_number';

        $sameStartDate = new \DateTime('+1 day 12:00');
        $differentStartDate = new \DateTime('+2 days');

        $sameEndDate = new \DateTime('+1 day 16:00');
        $differentEndDate = new \DateTime('+2 days');

        $sameLocation = 'same location';
        $differentLocation = 'different location';

        return [
            [.99, $sameConfirmationNumber, $sameConfirmationNumber, $differentStartDate, $differentEndDate, $differentLocation],
            [.99, $sameConfirmationNumber, strtoupper($sameConfirmationNumber), $differentStartDate, $differentEndDate, $differentLocation],
            [.97, null, null, $sameStartDate, $sameEndDate, $sameLocation],
            [.97, null, $sameConfirmationNumber, $sameStartDate, $sameEndDate, $sameLocation],
            [.00, $sameConfirmationNumber, null, $sameStartDate, $sameEndDate, $sameLocation],
            [.00, $sameConfirmationNumber, $differentConfirmationNumber, $differentStartDate, $sameEndDate, $sameLocation],
        ];
    }

    private function getSchemaParking(): SchemaParking
    {
        $parking = new SchemaParking();
        $parking->startDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $parking->endDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 16:00'));
        $parking->confirmationNumbers = [new ConfNo()];
        $parking->confirmationNumbers[0]->number = 'same_number';
        $parking->confirmationNumbers[0]->isPrimary = true;
        $parking->address = new Address();
        $parking->address->text = 'same location';

        return $parking;
    }
}
