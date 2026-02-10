<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityTrainRideSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\SegmentMatcherInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TrainRideSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\TrainSegment as SchemaTrainRideSegment;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class TrainRideSegmentMatcherTest extends BaseContainerTest
{
    /**
     * @var TrainRideSegmentMatcher
     */
    private $matcher;

    public function _before()
    {
        parent::_before();

        $this->matcher = new TrainRideSegmentMatcher($this->container->get(GeoLocationMatcher::class));
    }

    public function testSupports()
    {
        /** @var SchemaTrainRideSegment $schemaTrainRideSegment */
        $schemaTrainRideSegment = new SchemaTrainRideSegment();
        /** @var EntityTrainRideSegment $entityTrainRideSegment */
        $entityTrainRideSegment = new EntityTrainRideSegment();
        $invalidSchema = new class() {
        };
        $this->assertTrue($this->matcher->supports($entityTrainRideSegment, $schemaTrainRideSegment));
        $this->assertFalse($this->matcher->supports($entityTrainRideSegment, $invalidSchema));
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityTrainRideSegment(), new Cruise(), SegmentMatcherInterface::SAME_TRIP);
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
        string $scope,
        bool $matchLocation = false
    ) {
        /** @var EntityTrainRideSegment $entityTrainRideSegment */
        $entityTrainRideSegment = $this->makeEmpty(EntityTrainRideSegment::class, [
            'getDepcode' => $departureCode,
            'getArrcode' => $arrivalCode,
            'getDepname' => $departureName,
            'getArrname' => $arrivalName,
            'getDepartureDate' => $departureDate,
            'getArrivalDate' => $arrivalDate,
        ]);
        /** @var SchemaTrainRideSegment $schemaTrainRideSegment */
        $schemaTrainRideSegment = $this->getSchemaTrainRideSegment();
        $this->assertSame(
            $expectedConfidence,
            (new TrainRideSegmentMatcher(
                $this->make(GeoLocationMatcher::class, ['match' => $matchLocation])
            ))->match($entityTrainRideSegment, $schemaTrainRideSegment, $scope)
        );
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
                .95,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .94,
                $differentDepartureCode,
                $differentArrivalCode,
                $sameDepartureName,
                $sameArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .93,
                $differentDepartureCode,
                $differentArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $sameDepartureDate,
                $sameArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
                true,
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

    private function getSchemaTrainRideSegment(): SchemaTrainRideSegment
    {
        $trainRideSegment = new SchemaTrainRideSegment();
        $trainRideSegment->departure = new TransportLocation();
        $trainRideSegment->arrival = new TransportLocation();
        $trainRideSegment->departure->stationCode = 'same departure code';
        $trainRideSegment->departure->name = 'same departure name';
        $trainRideSegment->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $trainRideSegment->arrival->stationCode = 'same arrival code';
        $trainRideSegment->arrival->name = 'same arrival name';
        $trainRideSegment->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 16:00'));

        return $trainRideSegment;
    }
}
