<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntityFlightSegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\FlightSegmentMatcher;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\SegmentMatcherInterface;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\OperatingCarrier;
use AwardWallet\Schema\Itineraries\TripLocation;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class FlightSegmentMatcherTest extends BaseContainerTest
{
    /**
     * @var FlightSegmentMatcher
     */
    private $matcher;

    public function _before()
    {
        parent::_before();

        $this->matcher = new FlightSegmentMatcher($this->container->get(GeoLocationMatcher::class));
    }

    public function testSupports()
    {
        /** @var SchemaFlightSegment $schemaFlightSegment */
        $schemaFlightSegment = new SchemaFlightSegment();
        /** @var EntityFlightSegment $entityFlightSegment */
        $entityFlightSegment = new EntityFlightSegment();
        $invalidSchema = new class() {
        };
        $this->assertTrue($this->matcher->supports($entityFlightSegment, $schemaFlightSegment));
        $this->assertFalse($this->matcher->supports($entityFlightSegment, $invalidSchema));
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityFlightSegment(), new Cruise(), SegmentMatcherInterface::SAME_TRIP);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatch(
        float $expectedConfidence,
        string $marketingAirlineFlightNumber,
        string $operatingAirlineFlightNumber,
        string $departureCode,
        string $arrivalCode,
        string $departureName,
        string $arrivalName,
        \DateTime $departureDate,
        \DateTime $arrivalDate,
        string $scope
    ) {
        /** @var EntityFlightSegment $entityFlightSegment */
        $entityFlightSegment = $this->makeEmpty(EntityFlightSegment::class, [
            'getFlightNumber' => $marketingAirlineFlightNumber,
            'getOperatingAirlineFlightNumber' => $operatingAirlineFlightNumber,
            'getDepcode' => $departureCode,
            'getArrcode' => $arrivalCode,
            'getDepname' => $departureName,
            'getArrname' => $arrivalName,
            'getDepartureDate' => $departureDate,
            'getArrivalDate' => $arrivalDate,
        ]);
        /** @var SchemaFlightSegment $schemaFlightSegment */
        $schemaFlightSegment = $this->getSchemaFlightSegment();
        $this->assertSame($expectedConfidence, $this->matcher->match($entityFlightSegment, $schemaFlightSegment, $scope));
    }

    public function dataProvider()
    {
        $sameMarketingFlgihtNumber = 'same marketing carrier flight number';
        $differentMarketingFlightNumber = 'different marketing carrier flight number';

        $sameOperatingFlgihtNumber = 'same operating carrier flight number';
        $differentOperatingFlgihtNumber = 'different operating carrier flight number';

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
                $sameMarketingFlgihtNumber,
                $differentOperatingFlgihtNumber,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $sameDepartureDate,
                $sameArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .98,
                $differentMarketingFlightNumber,
                $sameOperatingFlgihtNumber,
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
                $differentMarketingFlightNumber,
                $sameOperatingFlgihtNumber,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .90,
                $differentMarketingFlightNumber,
                $differentOperatingFlgihtNumber,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .85,
                $sameMarketingFlgihtNumber,
                $differentOperatingFlgihtNumber,
                $differentDepartureCode,
                $differentArrivalCode,
                $sameDepartureName,
                $sameArrivalName,
                $differentDepartureDate,
                $differentArrivalDate,
                SegmentMatcherInterface::SAME_TRIP,
            ],
            [
                .80,
                $differentMarketingFlightNumber,
                $differentOperatingFlgihtNumber,
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
                $differentMarketingFlightNumber,
                $differentOperatingFlgihtNumber,
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
                $sameMarketingFlgihtNumber,
                $differentOperatingFlgihtNumber,
                $sameDepartureCode,
                $sameArrivalCode,
                $differentDepartureName,
                $differentArrivalName,
                $sameDepartureDate,
                $sameArrivalDate,
                SegmentMatcherInterface::ANY,
            ],
            [
                .98,
                $differentMarketingFlightNumber,
                $sameOperatingFlgihtNumber,
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
                $differentMarketingFlightNumber,
                $sameOperatingFlgihtNumber,
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
                $differentMarketingFlightNumber,
                $differentOperatingFlgihtNumber,
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
                $sameMarketingFlgihtNumber,
                $differentOperatingFlgihtNumber,
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
                $differentMarketingFlightNumber,
                $differentOperatingFlgihtNumber,
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
                $differentMarketingFlightNumber,
                $differentOperatingFlgihtNumber,
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

    private function getSchemaFlightSegment(): SchemaFlightSegment
    {
        $flightSegment = new SchemaFlightSegment();
        $flightSegment->marketingCarrier = new MarketingCarrier();
        $flightSegment->marketingCarrier->flightNumber = 'same marketing carrier flight number';
        $flightSegment->operatingCarrier = new OperatingCarrier();
        $flightSegment->operatingCarrier->flightNumber = 'same operating carrier flight number';
        $flightSegment->departure = new TripLocation();
        $flightSegment->arrival = new TripLocation();
        $flightSegment->departure->airportCode = 'same departure code';
        $flightSegment->departure->name = 'same departure name';
        $flightSegment->departure->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $flightSegment->arrival->airportCode = 'same arrival code';
        $flightSegment->arrival->name = 'same arrival name';
        $flightSegment->arrival->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 16:00'));

        return $flightSegment;
    }
}
