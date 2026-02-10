<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Event\PushNotification\ItineraryListener;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\FlightInfo\FlightInfo;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Timeline\Item\ItineraryInterface;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;
use Prophecy\Argument;

/**
 * @group frontend-unit
 */
class DiffTest extends BaseUserTest
{
    /**
     * @var \AwardWallet\MainBundle\Timeline\Diff\Tracker
     */
    protected $tracker;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var OperatedByResolver
     */
    protected $operatedByResolver;

    public function _before()
    {
        parent::_before();
        $this->tracker = $this->container->get('aw.diff.tracker');
        $this->manager = $this->container->get(Manager::class);
        $this->operatedByResolver = $this->container->get(OperatedByResolver::class);
    }

    public function testTracker()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "future.trip.random.seats", "random.dates");
        $this->aw->checkAccount($accountId);

        $properties = $this->tracker->getProperties($accountId);
        $this->assertNotEmpty($properties);
        $seats = explode(",", array_shift($properties)->values['Seats']);
        $this->assertGreaterThan($seats[0], $seats[1]);

        $segment = $this->getSegment($accountId);
        $this->assertEmpty($segment->getChangeDate());
        $this->checkTimelineChanged($segment, false);

        $this->aw->checkAccount($accountId);
        $segment = $this->getSegment($accountId);
        $this->assertNotEmpty($segment->getChangeDate());
        $this->checkTimelineChanged($segment, true);

        $timeline = $this->manager->query(QueryOptions::createDesktop()->setUser($this->user)->setOperatedByResolver($this->operatedByResolver)->setWithDetails(true));
        $this->assertCount(2, $timeline);
        $this->assertTrue($timeline[1]["changed"]);
    }

    public function testSegmentWithoutCodes()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "Timeline.RandomTimesNoCodes", "asc");
        $this->aw->checkAccount($accountId);
        $segments = $this->getSegments($accountId);
        $this->assertCount(2, $segments);
        $this->checkTimelineChanged($segments[0], false);
        $this->checkTimelineChanged($segments[1], false);

        $this->db->executeQuery("UPDATE Account set Pass = 'desc' WHERE AccountID = $accountId");
        $this->aw->checkAccount($accountId);
        $segments = $this->getSegments($accountId);
        $this->checkTimelineChanged($segments[0], true);
        $this->checkTimelineChanged($segments[1], false);
    }

    public function testFlightNumber()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "Timeline.RandomFlightNumber");
        $this->aw->checkAccount($accountId);
        $this->aw->checkAccount($accountId);
        $segments = $this->getSegments($accountId);
        $this->assertCount(1, $segments);
        $this->checkTimelineChanged($segments[0], true);

        $timeline = $this->manager->query(QueryOptions::createDesktop()->setUser($this->user)->setWithDetails(true));
        $this->assertCount(2, $timeline);
        $this->assertArrayNotHasKey("changed", $timeline[1]);
    }

    public function testItineraryUpdateEvent()
    {
        $this->aw->createFamilyMember($this->user->getUserid(), 'John', 'Smith');
        $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD);
        $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);

        $timestamp = (new \DateTime('+1 month 12:00'))->getTimestamp();

        $providerId = $this->aw->createAwProvider(
            $code = 'testitinerary' . StringHandler::getRandomCode(7),
            $code,
            [],
            [
                'ParseItineraries' => function () use ($user, $timestamp) {
                    return [
                        [
                            "RecordLocator" => "ABCDEF",
                            "Passengers" => [
                                $user->getFullName(),
                                "John Smith",
                            ],
                            "TripSegments" => [
                                [
                                    "Status" => "Confirmed",
                                    "FlightNumber" => "SQ 175",
                                    "DepName" => "HAN",
                                    "DepCode" => "HAN",
                                    "DepDate" => $timestamp,
                                    "ArrName" => "SIN",
                                    "ArrCode" => "SIN",
                                    "ArrDate" => $timestamp + 16800,
                                    "Cabin" => "Economy ",
                                    "BookingClass" => "N",
                                    "Duration" => "3hrs 40mins",
                                    "Aircraft" => "772",
                                    "Seats" => rand(1, 255) . "K, " . rand(1, 255) . "H",
                                ],
                            ],
                        ],
                        [
                            "RecordLocator" => "HIJKLM",
                            "Passengers" => [
                                $user->getFullName(),
                                "Join Smith",
                            ],
                            "TripSegments" => [
                                [
                                    "Status" => "Confirmed",
                                    "FlightNumber" => "SQ 974",
                                    "DepName" => "SIN",
                                    "DepCode" => "SIN",
                                    "DepDate" => $timestamp + 174900,
                                    "ArrName" => "BKK",
                                    "ArrCode" => "BKK",
                                    "ArrDate" => $timestamp + 180000,
                                    "Cabin" => "Economy ",
                                    "BookingClass" => "X",
                                    "Duration" => "2hrs 25mins",
                                    "Aircraft" => "772",
                                    "Seats" => rand(1, 255) . "K, " . rand(1, 255) . "H",
                                ],
                            ],
                        ],
                    ];
                },
            ]
        );

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), $providerId, "login");
        $listenerProphecy = $this->prophesize(ItineraryListener::class);
        $listenerProphecy
            ->onItineraryUpdate(
                Argument::that(function (ItineraryUpdateEvent $event) {
                    return
                        $event->getAdded()
                        && !$event->getChanged()
                        && !$event->getRemoved();
                }),
                Argument::cetera()
            )
            ->shouldBeCalledTimes(1);

        $listenerProphecy
            ->onItineraryUpdate(
                Argument::that(function (ItineraryUpdateEvent $event) {
                    return
                        $event->getChanged()
                        && !$event->getAdded()
                        && !$event->getRemoved();
                }),
                Argument::cetera()
            )
            ->shouldBeCalledTimes(1);

        $this->container->set('aw.push_notification.itinerary.listener', $listenerProphecy->reveal());

        $this->aw->checkAccount($accountId, true);
        $this->aw->checkAccount($accountId, true);
    }

    public function _after()
    {
        $this->tracker = null;
        $this->manager = null;

        parent::_after(); // TODO: Change the autogenerated stub
    }

    /**
     * @return Tripsegment
     */
    private function getSegment($accountId)
    {
        $rows = $this->getSegments($accountId);
        $this->assertCount(1, $rows);

        return $rows[0];
    }

    private function getSegments($accountId)
    {
        $this->em->clear();
        $this->container->get(FlightInfo::class)->clearCache();
        $q = $this->em->createQuery('
            SELECT
                s
            FROM
                AwardWallet\MainBundle\Entity\Tripsegment s
                JOIN s.tripid t
            WHERE
                t.account = :account
            ORDER BY
                s.depdate'
        );

        return $q->execute(['account' => $accountId]);
    }

    private function checkTimelineChanged(Tripsegment $segment, $changed)
    {
        $queryOptions = new QueryOptions();
        $queryOptions->setEntityManager($this->em);
        $queryOptions->setOperatedByResolver($this->operatedByResolver);
        $queryOptions->lock();
        $items = $segment->getTimelineItems($this->user, $queryOptions);
        $this->assertCount(1, $items);
        /** @var ItineraryInterface $item */
        $item = $items[0];
        $this->assertEquals($changed, $item->isChanged());
    }
}
