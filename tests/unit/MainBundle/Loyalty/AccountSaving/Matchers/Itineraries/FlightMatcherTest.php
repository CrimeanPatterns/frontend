<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityFlight;
use AwardWallet\MainBundle\Entity\Tripsegment as EntityFlightSegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\FlightMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\FlightSegment as SchemaFlightSegment;
use AwardWallet\Schema\Itineraries\IssuingCarrier;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\TravelAgency;
use Codeception\Test\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class FlightMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var FlightMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        $this->matcher = new FlightMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
    }

    public function testSupports()
    {
        /** @var SchemaFlight $schemaFlight */
        $schemaFlight = new SchemaFlight();
        /** @var EntityFlight $entityFlight */
        $entityFlight = new EntityFlight();
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
        $this->assertTrue($this->matcher->supports($entityFlight, $schemaFlight));
        $this->assertFalse($this->matcher->supports($entityFlight, new Cruise()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaFlight));
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
        $this->matcher->match($invalidEntity, new SchemaFlight());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityFlight(), new Cruise());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatch(
        float $expectedConfidence,
        string $marketingAirlineConfirmationNumber,
        string $issuingAirlineConfirmationNumber,
        array $travelingAgencyConfirmationNumbers
    ) {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        $matcher = new FlightMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        $entityFlightSegment = $this->makeEmpty(EntityFlightSegment::class, [
            'getMarketingAirlineConfirmationNumber' => $marketingAirlineConfirmationNumber,
        ]);
        /** @var EntityFlight $entityFlight */
        $entityFlight = $this->makeEmpty(EntityFlight::class, [
            'getIssuingAirlineConfirmationNumber' => $issuingAirlineConfirmationNumber,
            'getTravelAgencyConfirmationNumbers' => $travelingAgencyConfirmationNumbers,
            'getSegments' => new ArrayCollection([$entityFlightSegment]),
        ]);
        /** @var SchemaFlight $schemaFlight */
        $schemaFlight = $this->getSchemaFlight();
        $this->assertSame($expectedConfidence, $matcher->match($entityFlight, $schemaFlight));
    }

    public function dataProvider()
    {
        $sameMarketingAirlineConfirmationNumber = 'same_ma_number';
        $sameIssuingAirlineConfirmationNumber = 'same_ia_number';
        $sameTravelingAgencyConfirmationNumber = 'same_ta_number';
        $differentConfirmationNumber = 'different_number';

        return [
            [
                .99,
                $sameMarketingAirlineConfirmationNumber,
                $differentConfirmationNumber,
                [],
            ],
            [
                .99,
                strtoupper($sameMarketingAirlineConfirmationNumber),
                $differentConfirmationNumber,
                [],
            ],
            [
                .99,
                $differentConfirmationNumber,
                $sameIssuingAirlineConfirmationNumber,
                [],
            ],
            [
                .99,
                $differentConfirmationNumber,
                strtoupper($sameIssuingAirlineConfirmationNumber),
                [],
            ],
            [
                .00,
                $differentConfirmationNumber,
                $differentConfirmationNumber,
                [],
            ],
            [
                .99,
                $differentConfirmationNumber,
                $differentConfirmationNumber,
                [$sameTravelingAgencyConfirmationNumber],
            ],
            [
                .99,
                $differentConfirmationNumber,
                $differentConfirmationNumber,
                [strtoupper($sameTravelingAgencyConfirmationNumber)],
            ],
            [
                .99,
                $differentConfirmationNumber,
                $differentConfirmationNumber,
                [$differentConfirmationNumber, $sameTravelingAgencyConfirmationNumber],
            ],
            [
                .0,
                $differentConfirmationNumber,
                $differentConfirmationNumber,
                [$differentConfirmationNumber],
            ],
        ];
    }

    private function getSchemaFlight(): SchemaFlight
    {
        $flight = new SchemaFlight();
        $flight->issuingCarrier = new IssuingCarrier();
        $flight->issuingCarrier->confirmationNumber = 'same_ia_number';
        $flight->travelAgency = new TravelAgency();
        $flight->travelAgency->confirmationNumbers = [new ConfNo()];
        $flight->travelAgency->confirmationNumbers[0]->number = 'same_ta_number';
        $flight->segments = [new SchemaFlightSegment()];
        $flight->segments[0]->marketingCarrier = new MarketingCarrier();
        $flight->segments[0]->marketingCarrier->confirmationNumber = 'same_ma_number';

        return $flight;
    }
}
