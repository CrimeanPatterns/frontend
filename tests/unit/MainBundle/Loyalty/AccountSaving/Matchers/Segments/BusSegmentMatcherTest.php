<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityBusRideSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\BusRideSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\SegmentMatcherInterface;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\BusSegment as SchemaBusRideSegment;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class BusSegmentMatcherTest extends BaseContainerTest
{
    /**
     * @var BusRideSegmentMatcher
     */
    private $matcher;

    public function _before()
    {
        parent::_before();

        $this->matcher = new BusRideSegmentMatcher($this->container->get(GeoLocationMatcher::class));
    }

    public function testSupports()
    {
        /** @var SchemaBusRideSegment $schemaBusRideSegment */
        $schemaBusRideSegment = new SchemaBusRideSegment();
        /** @var EntityBusRideSegment $entityBusRideSegment */
        $entityBusRideSegment = new EntityBusRideSegment();
        $invalidSchema = new class() {
        };
        $this->assertTrue($this->matcher->supports($entityBusRideSegment, $schemaBusRideSegment));
        $this->assertFalse($this->matcher->supports($entityBusRideSegment, $invalidSchema));
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityBusRideSegment(), new Cruise(), SegmentMatcherInterface::ANY);
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
        /** @var EntityBusRideSegment $entityBusRideSegment */
        $entityBusRideSegment = $this->makeEmpty(EntityBusRideSegment::class, [
            'getDepcode' => $departureCode,
            'getArrcode' => $arrivalCode,
            'getDepname' => $departureName,
            'getArrname' => $arrivalName,
            'getDepartureDate' => $departureDate,
            'getArrivalDate' => $arrivalDate,
        ]);
        /** @var SchemaBusRideSegment $schemaBusRideSegment */
        $schemaBusRideSegment = $this->getSchemaBusRideSegment();
        $this->assertSame($expectedConfidence, $this->matcher->match($entityBusRideSegment, $schemaBusRideSegment, $scope));
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
                .97,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $sameDepartureDate,
                $sameArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .94,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .93,
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
                .97,
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

    private function getSchemaBusRideSegment(): SchemaBusRideSegment
    {
        $busRideSegment = new SchemaBusRideSegment();
        $busRideSegment->departure = new TransportLocation();
        $busRideSegment->arrival = new TransportLocation();
        $busRideSegment->departure->stationCode = 'same departure code';
        $busRideSegment->departure->name = 'same departure name';
        $busRideSegment->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $busRideSegment->arrival->stationCode = 'same arrival code';
        $busRideSegment->arrival->name = 'same arrival name';
        $busRideSegment->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 16:00'));

        return $busRideSegment;
    }
}
