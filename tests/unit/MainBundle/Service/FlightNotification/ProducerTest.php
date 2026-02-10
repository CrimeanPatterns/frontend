<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\FlightNotification\NotificationDate;
use AwardWallet\MainBundle\Service\FlightNotification\OffsetHandler;
use AwardWallet\MainBundle\Service\FlightNotification\OffsetStatus;
use AwardWallet\MainBundle\Service\FlightNotification\Producer;
use AwardWallet\MainBundle\Service\FlightNotification\QueueLocker;
use AwardWallet\MainBundle\Service\TaskScheduler\Producer as BaseProducer;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment as DBTripSegment;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Stub\Expected;
use Psr\Log\Test\TestLogger;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class ProducerTest extends BaseUserTest
{
    private ?Tripsegment $ts;

    private ?TestLogger $logger;

    public function _before()
    {
        parent::_before();

        $this->logger = new TestLogger();
        $depDate = new \DateTime();
        $arrDate = (clone $depDate)->modify('+3 hours');
        $this->dbBuilder->makeTrip(new Trip(
            'TEST001',
            [
                $ts = new DBTripSegment(
                    'AAA',
                    'AAA',
                    $depDate,
                    'BBB',
                    'BBB',
                    $arrDate
                ),
            ],
            null,
            ['UserID' => $this->user->getId()]
        ));
        $this->ts = $this->em->getRepository(Tripsegment::class)->find($ts->getId());
    }

    public function _after()
    {
        parent::_after();

        $this->logger = null;
        $this->ts = null;
    }

    public function testNothingPublish()
    {
        $producer = $this->getProducer([], [], Expected::never());
        $producer->publish($this->ts, new \DateTime());
        $this->assertLogContains('nothing to publish');
    }

    public function testFilterCallback()
    {
        $producer = $this->getProducer([$this->getOffsetStatus()], [], Expected::never());
        $producer->publish($this->ts, new \DateTime(), fn (OffsetStatus $status) => false);
        $this->assertLogContains('filtered, skip');
    }

    public function testQueueLocked()
    {
        $producer = $this->getProducer([$this->getOffsetStatus()], [
            'isAcquired' => true,
        ], Expected::never());
        $producer->publish($this->ts, new \DateTime());
        $this->assertLogContains('is already in the queue, skip');
    }

    public function testValidateNotificationDate()
    {
        $status = $this->getOffsetStatus();
        NotificationDate::setDate($this->ts, $status->getKind(), new \DateTime());
        $this->em->flush();
        $producer = $this->getProducer([$status], ['isAcquired' => false], Expected::never());
        $producer->publish($this->ts, new \DateTime());
        $this->assertLogContains('has already been sent, producer, skip');
    }

    public function testPublish()
    {
        $status = $this->getOffsetStatus();
        $producer = $this->getProducer([$status], [
            'isAcquired' => false,
            'acquire' => Expected::once(false),
            'release' => Expected::never(),
        ], Expected::once());
        $producer->publish($this->ts, new \DateTime());
        $this->assertLogContains('publish message');
    }

    private function getOffsetStatus(): OffsetStatus
    {
        return new OffsetStatus(
            636,
            OffsetHandler::KIND_CHECKIN,
            [OffsetHandler::CATEGORY_PUSH],
            24,
            24,
            0,
            0,
            time()
        );
    }

    private function assertLogContains(string $str)
    {
        $this->assertStringContainsString($str, $this->getLogs());
    }

    private function assertLogNotContains(string $str)
    {
        $this->assertStringNotContainsString($str, $this->getLogs());
    }

    private function getLogs(): string
    {
        return it($this->logger->records)
            ->column('message')
            ->joinToString("\n");
    }

    private function getProducer(array $statuses, array $queueLocker = [], $publish = null): Producer
    {
        return new Producer(
            $this->make(OffsetHandler::class, [
                'getOffsetsStatusesBySegment' => $statuses,
            ]),
            $this->makeEmpty(QueueLocker::class, $queueLocker),
            $this->logger,
            $this->make(BaseProducer::class, [
                'publish' => $publish,
            ])
        );
    }
}
