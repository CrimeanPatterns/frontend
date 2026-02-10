<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\MileValue\Async;

use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Service\MileValue\Async\CalcTripMileValueExecutor;
use AwardWallet\MainBundle\Service\MileValue\Async\CalcTripMileValueTask;
use AwardWallet\MainBundle\Service\MileValue\Async\TripUpdatedSubscriber;
use AwardWallet\MainBundle\Service\MileValue\CabinClassMapper;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\CombinedPriceSource;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\Price;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\PriceSourceInterface;
use AwardWallet\MainBundle\Timeline\Diff\Properties;
use AwardWallet\MainBundle\Timeline\Diff\TripSource;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;
use Codeception\Stub\Expected;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-functional
 */
class TripUpdatedSubscriberCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testCalcMileValue(\TestSymfonyGuy $I): void
    {
        $user = new User();
        $providerId = $I->makeProvider(new Provider());
        //        $I->haveInDatabase("AirClassDictionary", [
        //            "ProviderID" => $providerId,
        //        ])
        $tripSegment = new TripSegment(
            "JFK",
            "New York",
            new \DateTime("+1 month"),
            "LAX",
            "Los Angeles",
            (new \DateTime("+1 month"))->modify("+10 hour"),
            null,
            [
                'DepGeoTagID' => $I->grabFromDatabase("GeoTag", "GeoTagID", ["Address" => "JFK"]),
                'ArrGeoTagID' => $I->grabFromDatabase("GeoTag", "GeoTagID", ["Address" => "LAX"]),
                'CabinClass' => 'Economy',
            ]
        );
        $tripId = $I->makeTrip(
            new Trip(
                bin2hex(random_bytes(3)),
                [
                    $tripSegment,
                ],
                $user,
                [
                    'SpentAwards' => 1000,
                    'Total' => 100,
                    'Parsed' => 1,
                    'ReservationDate' => date("Y-m-d H:i:s", strtotime("-1 day")),
                    'SpentAwardsProviderID' => $providerId,
                    'CabinClass' => 'Economy',
                ]
            ),
        );

        $process = $I->stubMakeEmpty(Process::class, [
            'execute' => Expected::exactly(2, function (CalcTripMileValueTask $task) use ($I, $tripId) {
                $I->assertEquals($tripId, $task->getTripId());

                return new Response();
            }),
        ]);
        $I->mockService(Process::class, $process);

        $I->mockService(CabinClassMapper::class, $I->stubMakeEmpty(CabinClassMapper::class, [
            "cabinClassToClassOfService" => Constants::CLASS_BASIC_ECONOMY,
        ]));

        $I->mockService(CombinedPriceSource::class, $I->stubMakeEmpty(PriceSourceInterface::class, [
            'search' => Expected::once([new Price('MOCK', 500, [], null)]),
        ]));

        /** @var TripUpdatedSubscriber $subscriber */
        $subscriber = $I->grabService(TripUpdatedSubscriber::class);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);

        $subscriber->onItineraryUpdate(new ItineraryUpdateEvent(
            $user->getId(),
            [
                new Properties(new TripSource($em), "IGNORED", new \DateTime(), [], $tripSegment->getId()),
            ],
            [],
            [],
            []
        ));

        /** @var CalcTripMileValueExecutor $executor */
        $executor = $I->grabService(CalcTripMileValueExecutor::class);
        $executor->execute(new CalcTripMileValueTask($tripId));
        $I->assertEquals(40, round($I->grabFromDatabase("MileValue", "MileValue", ["TripID" => $tripId])));

        $subscriber->onItineraryUpdate(new ItineraryUpdateEvent(
            $user->getId(),
            [
                new Properties(new TripSource($em), "IGNORED", new \DateTime(), [], $tripSegment->getId()),
            ],
            [],
            [],
            []
        ));

        $I->verifyMocks();
    }
}
