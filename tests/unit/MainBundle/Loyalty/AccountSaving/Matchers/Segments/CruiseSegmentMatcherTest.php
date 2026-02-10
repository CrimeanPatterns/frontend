<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityCruiseSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\CruiseSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\SegmentMatcherInterface;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\CarRental;
use AwardWallet\Schema\Itineraries\CruiseSegment as SchemaCruiseSegment;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class CruiseSegmentMatcherTest extends BaseContainerTest
{
    /**
     * @var CruiseSegmentMatcher
     */
    private $matcher;

    public function _before()
    {
        parent::_before();

        $this->matcher = new CruiseSegmentMatcher($this->container->get(GeoLocationMatcher::class));
    }

    public function testSupports()
    {
        /** @var SchemaCruiseSegment $schemaCruiseSegment */
        $schemaCruiseSegment = new SchemaCruiseSegment();
        /** @var EntityCruiseSegment $entityCruiseSegment */
        $entityCruiseSegment = new EntityCruiseSegment();
        $invalidSchema = new class() {
        };
        $this->assertTrue($this->matcher->supports($entityCruiseSegment, $schemaCruiseSegment));
        $this->assertFalse($this->matcher->supports($entityCruiseSegment, $invalidSchema));
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityCruiseSegment(), new CarRental(), SegmentMatcherInterface::SAME_TRIP);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatch(
        float $expectedConfidence,
        string $departureCode,
        string $arrivalCode,
        string $departureName,
        string $arrivalName,
        \DateTime $departureDate,
        \DateTime $arrivalDate,
        string $scope
    ) {
        /** @var EntityCruiseSegment $entityCruiseSegment */
        $entityCruiseSegment = $this->makeEmpty(EntityCruiseSegment::class, [
            'getDepcode' => $departureCode,
            'getArrcode' => $arrivalCode,
            'getDepname' => $departureName,
            'getArrname' => $arrivalName,
            'getDepartureDate' => $departureDate,
            'getArrivalDate' => $arrivalDate,
        ]);
        /** @var SchemaCruiseSegment $schemaCruiseSegment */
        $schemaCruiseSegment = $this->getSchemaCruiseSegment();
        $this->assertSame($expectedConfidence, $this->matcher->match($entityCruiseSegment, $schemaCruiseSegment, $scope));
    }

    public function dataProvider()
    {
        $sameDepartureCode = 'same departure code';
        $differentDepartureCode = 'different departure code';

        $sameArrivalCode = 'same arrival code';
        $differentArrivalCode = 'different arrival code';

        $sameDepartureName = 'same departure name';
        $differentDepartureName = 'different departure name';

        $sameArrivalName = 'same arrival name';
        $differentArrivalName = 'different arrival name';

        $sameDepartureDate = new \DateTime('+1 day 12:00');
        $differentDepartureDate = new \DateTime('+2 days');

        $sameArrivalDate = new \DateTime('+1 day 16:00');
        $differentArrivalDate = new \DateTime('+2 days');

        return [
            [
                .98,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $sameDepartureDate,
                $sameArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .96,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .95,
                $differentDepartureCode,
                $differentArrivalCode,
                $sameDepartureName,
                $sameArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .00,
                $differentDepartureCode,
                $differentArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .98,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $sameDepartureDate,
                $sameArrivalDate,
                SegmentMatcherInterface::ANY,
            ],
            [
                .00,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::ANY,
            ],
            [
                .00,
                $differentDepartureCode,
                $differentArrivalCode,
                $sameDepartureName,
                $sameArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::ANY,
            ],
            [
                .00,
                $differentDepartureCode,
                $differentArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::ANY,
            ],
        ];
    }

    private function getSchemaCruiseSegment(): SchemaCruiseSegment
    {
        $cruiseSegment = new SchemaCruiseSegment();
        $cruiseSegment->departure = new TransportLocation();
        $cruiseSegment->arrival = new TransportLocation();
        $cruiseSegment->departure->stationCode = 'same departure code';
        $cruiseSegment->departure->name = 'same departure name';
        $cruiseSegment->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $cruiseSegment->arrival->stationCode = 'same arrival code';
        $cruiseSegment->arrival->name = 'same arrival name';
        $cruiseSegment->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 16:00'));

        return $cruiseSegment;
    }
}
