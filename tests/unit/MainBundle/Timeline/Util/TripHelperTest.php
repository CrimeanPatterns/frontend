<?php

namespace AwardWallet\Tests\Unit\MainBundle\Timeline\Util;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Timeline\Item\AirTrip;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\Util\TripHelper;
use AwardWallet\Tests\Unit\BaseUserTest;
use Clock\ClockNative;
use Doctrine\ORM\EntityManager;

use function Codeception\Module\Utils\Reflection\setObjectProperty;

/**
 * @group frontend-unit
 */
class TripHelperTest extends BaseUserTest
{
    /**
     * @var TripHelper
     */
    private $tripHelper;

    public function _before()
    {
        parent::_before();
        $airline = $this->makeEmpty(Airline::class, ['getName' => 'AIRLINE_NAME']);
        $airlineRepository = $this->makeEmpty(AirlineRepository::class, ['findOneBy' => function (array $criteria) use ($airline) {
            if ('SP' === $criteria['code']) {
                return $airline;
            }
        }]);
        $entityManager = $this->makeEmpty(EntityManager::class, ['getRepository' => $airlineRepository]);
        /** @var OperatedByResolver $operatedByResolverMock */
        $operatedByResolverMock = $this->makeEmpty(OperatedByResolver::class, ['getManager' => $entityManager]);
        $localizeService = $this->container->get(LocalizeService::class);
        $translator = $this->container->get('translator');
        $referralId = $this->container->getParameter('booking_com_referral_id');

        $this->tripHelper = new TripHelper($operatedByResolverMock, $localizeService, $translator, new ClockNative(), $referralId);
    }

    /**
     * @dataProvider airlineNameProvider
     */
    public function testResolveFlightNameAirlineName(
        ?string $segmentAirlineName = null,
        ?string $tripAirlineName = null,
        ?Provider $provider = null,
        ?string $flightNumber = null,
        string $expectedName,
        ?string $expectedIata = null
    ) {
        $trip = new Trip();
        $trip->setCategory(Trip::CATEGORY_AIR);
        $trip->setAirlineName($tripAirlineName);
        $trip->setRealProvider($provider);

        $segment = new Tripsegment();
        setObjectProperty($segment, 'tripsegmentid', 1);
        $segment->setTripid($trip);
        $segment->setAirlineName($segmentAirlineName);
        $segment->setFlightNumber($flightNumber);
        $segment->setDepgeotagid(new Geotag());
        $segment->setArrgeotagid(new Geotag());
        $segment->setDepartureDate(new \DateTime());
        $segment->setArrivalDate(new \DateTime('tomorrow'));

        /** @var OperatedByResolver $resolver */
        $resolver = $this->makeEmpty(OperatedByResolver::class, ['resolveAirProvider' => null]);
        $opts = (new QueryOptions())->setOperatedByResolver($resolver)->lock();
        $timelineSegment = $segment->getTimelineItems($this->user, $opts)[0];
        $resolvedFlightName = $this->tripHelper->resolveFlightName($timelineSegment);
        $this->assertSame($expectedName, $resolvedFlightName->getAirlineName());
        $this->assertSame($flightNumber, $resolvedFlightName->getFlightNumber());
        $this->assertSame($expectedIata, $resolvedFlightName->getIataCode());
    }

    /**
     * @dataProvider providerSources
     */
    public function testProviderFromDifferentSources(?Provider $segmentProvider = null, ?Provider $tripProvider = null, ?string $expectedIata = null)
    {
        $trip = new Trip();
        $trip->setCategory(Trip::CATEGORY_AIR);
        $trip->setRealProvider($tripProvider);

        $segment = new Tripsegment();
        setObjectProperty($segment, 'tripsegmentid', 1);
        $segment->setTripid($trip);
        $segment->setDepgeotagid(new Geotag());
        $segment->setArrgeotagid(new Geotag());
        $segment->setDepartureDate(new \DateTime());
        $segment->setArrivalDate(new \DateTime('tomorrow'));

        /** @var OperatedByResolver $resolver */
        $resolver = $this->makeEmpty(OperatedByResolver::class, ['resolveAirProvider' => $segmentProvider]);
        $opts = (new QueryOptions())->setOperatedByResolver($resolver)->lock();
        $timelineSegment = $segment->getTimelineItems($this->user, $opts)[0];
        $resolvedFlightName = $this->tripHelper->resolveFlightName($timelineSegment);
        $this->assertSame($expectedIata, $resolvedFlightName->getIataCode());
    }

    public function airlineNameProvider()
    {
        return [
            [ // Airline name should be taken from segment
                'segmentAirlineName' => 'SEGMENT_AIRLINE_NAME',
                'tripAirlineName' => 'TRIP_AIRLINE_NAME',
                'provider' => $this->getProvider('PROVIDER_NAME'),
                'flightNumber' => 'SP1234',
                'expectedName' => 'SEGMENT_AIRLINE_NAME',
            ],
            [ // Airline name should be taken from trip
                'segmentAirlineName' => null,
                'tripAirlineName' => 'TRIP_AIRLINE_NAME',
                'provider' => $this->getProvider('PROVIDER_NAME'),
                'flightNumber' => 'SP1234',
                'expectedName' => 'TRIP_AIRLINE_NAME',
            ],
            [ // Provider name should be taken as airline name
                'segmentAirlineName' => null,
                'tripAirlineName' => null,
                'provider' => $this->getProvider('PROVIDER_NAME'),
                'flightNumber' => 'SP1234',
                'expectedName' => 'PROVIDER_NAME',
            ],
            [ // Airline name should be found by flight number
                'segmentAirlineName' => null,
                'tripAirlineName' => null,
                'provider' => null,
                'flightNumber' => 'SP1234',
                'expectedName' => 'AIRLINE_NAME',
            ],
            [ // Should not generate any exceptions
                'segmentAirlineName' => 'SEGMENT_AIRLINE_NAME',
                'tripAirlineName' => 'TRIP_AIRLINE_NAME',
                'provider' => $this->getProvider('PROVIDER_NAME'),
                'flightNumber' => null,
                'expectedName' => 'SEGMENT_AIRLINE_NAME',
            ],
            [ // Check IATA
                'segmentAirlineName' => 'SEGMENT_AIRLINE_NAME',
                'tripAirlineName' => 'TRIP_AIRLINE_NAME',
                'provider' => $this->getProvider('PROVIDER_NAME', 'IA'),
                'flightNumber' => 'SP1234',
                'expectedName' => 'SEGMENT_AIRLINE_NAME',
                'expectedIATA' => 'IA',
            ],
        ];
    }

    public function providerSources()
    {
        return [
            [ // Segment provider should take priority
                'segmentProvider' => $this->getProvider('PROVIDER_NAME', 'SP'),
                'tripProvider' => $this->getProvider('PROVIDER_NAME', 'TP'),
                'expectedIata' => 'SP',
            ],
            [ // Trip provider should be used
                'segmentProvider' => null,
                'tripProvider' => $this->getProvider('PROVIDER_NAME', 'TP'),
                'expectedIata' => 'TP',
            ],
            [ // No IATA
                'segmentProvider' => null,
                'tripProvider' => null,
                'expectedIata' => null,
            ],
        ];
    }

    /**
     * @dataProvider bookingLinkProvider
     * @param AirTrip[] $segments
     * @param array $expected
     */
    public function testBookingLink($segments, $expected)
    {
        $this->tripHelper->fillBookingLinks($segments, $this->user);

        $firstSegment = $segments[0];

        $this->assertEquals($expected['info'], $firstSegment->getBookingInfo());
        $this->assertStringContainsString($expected['url'], $firstSegment->getBookingUrl());
    }

    public function bookingLinkProvider()
    {
        $today = new \DateTime();
        $today->setTime(10, 0, 0);

        $nextWeek = clone $today;
        $nextWeek->modify('+1 week');

        $nextMonth = clone $today;
        $nextMonth->modify('+1 month');

        $lastMonth = clone $today;
        $lastMonth->modify('-1 month');

        return [
            // next segment same day
            [
                $this->createSegments('CONFNO1', [
                    ['JFK', 'LAX', $nextWeek],
                    ['LAX', 'JFK', (clone $nextWeek)->modify('+1 hour')],
                ]),
                [
                    'info' => "Check out these amazing hotel deals in City, Country for {$nextWeek->format('M j, Y')}, for 1 night",
                    'url' => $this->buildReferralLink([
                        'checkin_monthday' => $nextWeek->format('d'),
                        'checkin_year_month' => $nextWeek->format('Y-m'),
                        'checkout_monthday' => (clone $nextWeek)->modify('+1 day')->format('d'),
                        'checkout_year_month' => (clone $nextWeek)->modify('+1 day')->format('Y-m'),
                    ]),
                ],
            ],
            // next segment in 14 days
            [
                $this->createSegments('CONFNO1', [
                    ['JFK', 'LAX', $nextWeek],
                    ['LAX', 'JFK', (clone $nextWeek)->modify('+14 day')],
                ]),
                [
                    'info' => "Check out these amazing hotel deals in City, Country for {$nextWeek->format('M j, Y')}, for 14 nights",
                    'url' => $this->buildReferralLink([
                        'checkin_monthday' => $nextWeek->format('d'),
                        'checkin_year_month' => $nextWeek->format('Y-m'),
                        'checkout_monthday' => (clone $nextWeek)->modify('+14 day')->format('d'),
                        'checkout_year_month' => (clone $nextWeek)->modify('+14 day')->format('Y-m'),
                    ]),
                ],
            ],
            // next segment in a month
            [
                $this->createSegments('CONFNO1', [
                    ['JFK', 'LAX', $nextWeek],
                    ['LAX', 'JFK', (clone $nextWeek)->modify('+1 month')],
                ]),
                [
                    'info' => "Check out these amazing hotel deals in City, Country for {$nextWeek->format('M j, Y')}, for 1 night",
                    'url' => $this->buildReferralLink([
                        'checkin_monthday' => $nextWeek->format('d'),
                        'checkin_year_month' => $nextWeek->format('Y-m'),
                        'checkout_monthday' => (clone $nextWeek)->modify('+1 day')->format('d'),
                        'checkout_year_month' => (clone $nextWeek)->modify('+1 day')->format('Y-m'),
                    ]),
                ],
            ],
            // segment in the past
            [
                $this->createSegments('CONFNO1', [
                    ['JFK', 'LAX', $lastMonth],
                    ['LAX', 'JFK', (clone $lastMonth)->modify('+1 day')],
                ]),
                [
                    'info' => "Check out these amazing hotel deals in City, Country",
                    'url' => $this->buildReferralLink([
                        'checkin_monthday' => $today->format('d'),
                        'checkin_year_month' => $today->format('Y-m'),
                        'checkout_monthday' => (clone $today)->modify('+1 day')->format('d'),
                        'checkout_year_month' => (clone $today)->modify('+1 day')->format('Y-m'),
                    ]),
                ],
            ],
        ];
    }

    protected function createSegments(string $confNo, array $options): array
    {
        $items = [];

        foreach ($options as [$dep, $arr, $depDate]) {
            $items[] = $this->createSegment($confNo, $dep, $arr, $depDate);
        }

        return $items;
    }

    protected function createSegment(string $confNo, string $dep, string $arr, \DateTime $depDate): AirTrip
    {
        $arrDate = clone $depDate;
        $arrDate->modify('+1 hour');

        $trip = new Trip();
        $trip->setCategory(TRIP_CATEGORY_AIR);

        $geotag = (new Geotag())
            ->setCity('City')
            ->setCountry('Country');

        $source = (new Tripsegment())
            ->setDepcode($dep)
            ->setArrcode($arr)
            ->setDepartureDate($depDate)
            ->setArrivalDate($arrDate)
            ->setTripid($trip)
            ->setDepgeotagid($geotag)
            ->setArrgeotagid($geotag);

        setObjectProperty($source, 'tripsegmentid', 1);

        /** @var OperatedByResolver $resolver */
        $resolver = $this->makeEmpty(OperatedByResolver::class);
        $opts = (new QueryOptions())->setOperatedByResolver($resolver)->lock();
        $timelineSegment = $source->getTimelineItems(new Usr(), $opts)[0];
        $timelineSegment->setConfNo($confNo);

        return $timelineSegment;
    }

    protected function buildReferralLink($options)
    {
        $options = array_merge([
            'aid' => '1473858',
            'iata' => 'LAX',
            'iata_orr' => 3,
        ], $options);

        return 'https://awardwallet.com/blog/link/booking?' . http_build_query($options);
    }

    private function getProvider(string $name, ?string $iata = null)
    {
        $provider = new Provider();
        $provider->setKind(PROVIDER_KIND_AIRLINE);
        $provider->setShortname($name);
        $provider->setName($name);
        $provider->setIATACode($iata);

        return $provider;
    }
}
