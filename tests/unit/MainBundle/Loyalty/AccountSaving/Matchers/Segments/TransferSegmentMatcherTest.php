<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityTransferSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\SegmentMatcherInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TransferSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\TransferLocation;
use AwardWallet\Schema\Itineraries\TransferSegment as SchemaTransferSegment;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class TransferSegmentMatcherTest extends BaseContainerTest
{
    /**
     * @var TransferSegmentMatcher
     */
    private $matcher;

    public function _before()
    {
        parent::_before();

        $this->matcher = new TransferSegmentMatcher($this->container->get(GeoLocationMatcher::class));
    }

    public function testSupports()
    {
        /** @var SchemaTransferSegment $schemaTransferSegment */
        $schemaTransferSegment = new SchemaTransferSegment();
        /** @var EntityTransferSegment $entityTransferSegment */
        $entityTransferSegment = new EntityTransferSegment();
        $this->assertTrue($this->matcher->supports($entityTransferSegment, $schemaTransferSegment));
        $this->assertFalse($this->matcher->supports($entityTransferSegment, new Cruise()));
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityTransferSegment(), new Cruise(), SegmentMatcherInterface::SAME_TRIP);
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
        /** @var EntityTransferSegment $entityTransferSegment */
        $entityTransferSegment = $this->makeEmpty(EntityTransferSegment::class, [
            'getDepcode' => $departureCode,
            'getArrcode' => $arrivalCode,
            'getDepname' => $departureName,
            'getArrname' => $arrivalName,
            'getDepartureDate' => $departureDate,
            'getArrivalDate' => $arrivalDate,
        ]);
        /** @var SchemaTransferSegment $schemaTransferSegment */
        $schemaTransferSegment = $this->getSchemaTransferSegment();
        $this->assertSame($expectedConfidence, $this->matcher->match($entityTransferSegment, $schemaTransferSegment, $scope));
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

    private function getSchemaTransferSegment(): SchemaTransferSegment
    {
        $transferSegment = new SchemaTransferSegment();
        $transferSegment->departure = new TransferLocation();
        $transferSegment->arrival = new TransferLocation();
        $transferSegment->departure->airportCode = 'same departure code';
        $transferSegment->departure->name = 'same departure name';
        $transferSegment->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $transferSegment->arrival->airportCode = 'same arrival code';
        $transferSegment->arrival->name = 'same arrival name';
        $transferSegment->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 16:00'));

        return $transferSegment;
    }
}
