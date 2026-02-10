<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Useragent as UseragentEntity;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\FlightNotification\FlightAlertConsumer;
use AwardWallet\MainBundle\Service\FlightNotification\FlightAlertTask;
use AwardWallet\MainBundle\Service\FlightNotification\LegSegmentDetector;
use AwardWallet\MainBundle\Service\FlightNotification\OffsetHandler;
use AwardWallet\MainBundle\Service\FlightNotification\OffsetStatus;
use AwardWallet\MainBundle\Service\FlightNotification\QueueLocker;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\TaskScheduler\Producer;
use AwardWallet\Tests\Modules\DbBuilder\Airline;
use AwardWallet\Tests\Modules\DbBuilder\GeoTag;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment as DBTripSegment;
use AwardWallet\Tests\Modules\DbBuilder\UserAgent;
use AwardWallet\Tests\Unit\BaseUserTest;
use Clock\ClockInterface;
use Clock\ClockTest;
use Codeception\Stub\Expected;
use Psr\Log\Test\TestLogger;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class FlightAlertConsumerTest extends BaseUserTest
{
    private ?Tripsegment $ts;

    private ?TestLogger $logger;

    private ?ClockInterface $clock;

    public function _before()
    {
        parent::_before();

        $this->logger = new TestLogger();
        $depDate = new \DateTime('+24 hours');
        $arrDate = (clone $depDate)->modify('+3 hours');
        $this->dbBuilder->makeTrip(new Trip(
            'TEST001',
            [
                ($ts = new DBTripSegment(
                    'AAA',
                    'AAA',
                    $depDate,
                    'BBB',
                    'BBB',
                    $arrDate
                ))
                    ->setDepGeoTag(new GeoTag(null, ['CountryCode' => 'US']))
                    ->setArrGeoTag(new GeoTag(null, ['CountryCode' => 'US']))
                    ->setAirline(new Airline('XX', 'Test Airline')),
            ],
            null,
            ['UserID' => $this->user->getId(), 'Category' => TRIP_CATEGORY_AIR]
        ));
        $this->ts = $this->em->getRepository(Tripsegment::class)->find($ts->getId());
        $this->clock = new ClockTest();
    }

    public function _after()
    {
        parent::_after();

        $this->logger = null;
        $this->ts = null;
        $this->clock = null;
    }

    public function testTripSegmentNotFound()
    {
        $this->getFlightAlertConsumer()->consume(new FlightAlertTask(0));
        $this->assertLogContains('[ts:0] not found in the db');
    }

    public function testNothingToSend()
    {
        $this->execute();
        $this->assertLogContains(sprintf('[ts:%d] nothing to send', $this->ts->getId()));
    }

    public function testMovingDepDateForward()
    {
        $this->execute([$this->getOffsetStatus(24, 3600, [OffsetHandler::CATEGORY_MAIL], OffsetHandler::KIND_CHECKIN, 100)]);
        $this->assertLogContains(sprintf('[ts:%d] early notification', $this->ts->getId()));
    }

    public function testMovingDepDateBack()
    {
        $this->execute(
            [$this->getOffsetStatus(24, 3600, [OffsetHandler::CATEGORY_MAIL], OffsetHandler::KIND_CHECKIN, -100)],
            [],
            1
        );
        $this->assertLogContains('sent 1 emails, push: 0');
    }

    public function testSendEmail()
    {
        $this->execute([$this->getOffsetStatus()], [
            'removeFromQueue' => Expected::once(),
        ], 1);
        $this->assertLogContains(sprintf(
            '[ts:%d][U][%s] process target',
            $this->ts->getId(),
            $this->user->getEmail()
        ));
        $this->assertLogContains('sent 1 emails, push: 0');
    }

    public function testEmptyDepGeotag()
    {
        $this->ts->setDepgeotagid();
        $this->em->flush();
        $this->execute([$this->getOffsetStatus()]);
        $this->assertLogContains('empty dep geotag');
    }

    public function testEmptyArrGeotag()
    {
        $this->ts->setArrgeotagid();
        $this->em->flush();
        $this->execute([$this->getOffsetStatus()]);
        $this->assertLogContains('empty arr geotag');
    }

    public function testEmptyDepCountryCode()
    {
        $this->ts->getDepgeotagid()->setCountryCode('');
        $this->em->flush();
        $this->execute([$this->getOffsetStatus()]);
        $this->assertLogContains('empty dep country');
    }

    public function testEmptyArrCountryCode()
    {
        $this->ts->getArrgeotagid()->setCountryCode('');
        $this->em->flush();
        $this->execute([$this->getOffsetStatus()]);
        $this->assertLogContains('empty arr country');
    }

    public function testNotLegSegment()
    {
        $this->execute([$this->getOffsetStatus()], [], 0, [], false);
        $this->assertLogContains(sprintf('[ts:%d] is not leg segment', $this->ts->getId()));
    }

    public function testNotificationDate()
    {
        $this->ts->setCheckinnotificationdate(new \DateTime());
        $this->em->flush();
        $this->execute([$this->getOffsetStatus(24, 3600, [OffsetHandler::CATEGORY_MAIL, OffsetHandler::CATEGORY_PUSH])]);
        $this->assertLogContains(sprintf(
            '[ts:%d][U][%s] process target',
            $this->ts->getId(), $this->user->getEmail()
        ));
        $this->assertLogContains('has already been sent, executor, skip');
        $this->assertLogContains('sent 0 emails, push: 0');
    }

    public function testEmailUserNotificationSettings()
    {
        $this->user->setCheckinreminder(false);
        $this->em->flush();
        $this->execute([$this->getOffsetStatus()]);
        $this->assertLogContains(sprintf(
            '[ts:%d][U][%s] user has disabled setting',
            $this->ts->getId(), $this->user->getEmail()
        ));
        $this->assertLogContains('sent 0 emails, push: 0');
    }

    public function testHiddenTrip()
    {
        $this->ts->hideByUser();
        $this->em->flush();
        $this->execute([$this->getOffsetStatus()]);
        $this->assertLogContains(sprintf('[ts:%d] hidden trip or segment', $this->ts->getId()));
    }

    public function testCategoryTrip()
    {
        $this->ts->getTripid()->setCategory(TRIP_CATEGORY_BUS);
        $this->em->flush();
        $this->execute([$this->getOffsetStatus()]);
        $this->assertLogContains(sprintf('[ts:%d] notification for flights only', $this->ts->getId()));
    }

    public function testFamilyMemberAndCopy()
    {
        $fm = $this->makeFamilyMember();
        $this->execute([$this->getOffsetStatus()], [], 2);
        $this->assertLogContains(sprintf(
            '[ts:%d][U][%s] process target',
            $this->ts->getId(), $this->user->getEmail()
        ));
        $this->assertLogContains(sprintf(
            '[ts:%d][UA][%s] process target',
            $this->ts->getId(), $fm->getEmail()
        ));
        $this->assertLogContains(sprintf('[ts:%d] sent 2 emails, push: 0', $this->ts->getId()));
    }

    public function testFamilyMemberEmptyEmail()
    {
        $fm = $this->makeFamilyMember(['Email' => '']);
        $this->execute([$this->getOffsetStatus()], [], 1);
        $this->assertLogContains(sprintf(
            '[ts:%d][U][%s] process target',
            $this->ts->getId(), $this->user->getEmail()
        ));
        $this->assertLogNotContains(sprintf(
            '[ts:%d][UA][%s] process target',
            $this->ts->getId(), $fm->getEmail()
        ));
        $this->assertLogContains(sprintf('[ts:%d] sent 1 emails, push: 0', $this->ts->getId()));
    }

    public function testFamilyMemberEmailSettings()
    {
        $fm = $this->makeFamilyMember(['SendEmails' => 0]);
        $this->execute([$this->getOffsetStatus()], [], 1);
        $this->assertLogContains(sprintf(
            '[ts:%d][U][%s] process target',
            $this->ts->getId(), $this->user->getEmail()
        ));
        $this->assertLogNotContains(sprintf(
            '[ts:%d][UA][%s] process target',
            $this->ts->getId(), $fm->getEmail()
        ));
        $this->assertLogContains(sprintf('[ts:%d] sent 1 emails, push: 0', $this->ts->getId()));
    }

    public function testFamilyMemberEqualEmails()
    {
        $fm = $this->makeFamilyMember(['Email' => $this->user->getEmail()]);
        $this->execute([$this->getOffsetStatus()], [], 1);
        $this->assertLogContains(sprintf(
            '[ts:%d][U][%s] process target',
            $this->ts->getId(), $this->user->getEmail()
        ));
        $this->assertLogNotContains(sprintf(
            '[ts:%d][UA][%s] process target',
            $this->ts->getId(), $fm->getEmail()
        ));
        $this->assertLogContains(sprintf('[ts:%d] sent 1 emails, push: 0', $this->ts->getId()));
    }

    public function testEmailAndPush()
    {
        $this->execute(
            [$this->getOffsetStatus(24, 3600, [OffsetHandler::CATEGORY_PUSH, OffsetHandler::CATEGORY_MAIL])],
            [
                'removeFromQueue' => Expected::once(),
            ],
            1,
            [
                'loadDevices' => [new MobileDevice()],
                'send' => true,
            ]
        );
        $this->assertLogContains(sprintf(
            '[ts:%d][U][%s] process target',
            $this->ts->getId(), $this->user->getEmail()
        ));
        $this->assertLogContains('sent 1 emails, push: 1');
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

    private function makeFamilyMember(array $fields = []): UseragentEntity
    {
        $id = $this->dbBuilder->makeUserAgent(new UserAgent(null, null, array_merge([
            'AgentID' => $this->user->getId(),
            'FirstName' => 'John',
            'LastName' => 'Smith',
            'Email' => 'john' . StringHandler::getRandomCode(5) . '@mail.com',
            'IsApproved' => 1,
        ], $fields)));
        $this->db->updateInDatabase('Trip', ['UserAgentID' => $id], [
            'TripID' => $this->ts->getTripid()->getId(),
        ]);

        return $this->em->getRepository(UseragentEntity::class)->find($id);
    }

    private function getOffsetStatus(
        float $offset = 24,
        int $deadline = 3600,
        array $categories = [OffsetHandler::CATEGORY_MAIL],
        string $kind = OffsetHandler::KIND_CHECKIN,
        int $delay = 0
    ): OffsetStatus {
        return new OffsetStatus(
            636,
            $kind,
            $categories,
            $offset,
            ceil($offset * 3600),
            $delay,
            $deadline,
            time()
        );
    }

    private function execute(
        array $statuses = [],
        array $queueLocker = [],
        int $emailSent = 0,
        array $pushSender = [],
        bool $isLegSegment = true
    ) {
        $this->getFlightAlertConsumer(
            $statuses, $queueLocker, $emailSent, $pushSender, $isLegSegment
        )->consume(new FlightAlertTask($this->ts->getId()));
    }

    private function getFlightAlertConsumer(
        array $statuses = [],
        array $queueLocker = [],
        int $emailSent = 0,
        array $pushSender = [],
        bool $isLegSegment = true
    ): FlightAlertConsumer {
        return new FlightAlertConsumer(
            $this->make(OffsetHandler::class, [
                'getOffsetsStatusesBySegment' => $statuses,
            ]),
            $this->makeEmpty(QueueLocker::class, $queueLocker),
            $this->logger,
            $this->em,
            $this->make(Sender::class, $pushSender),
            $this->make(Producer::class, [
                'publish' => $emailSent > 0 ? Expected::exactly($emailSent) : Expected::never(),
            ]),
            $this->make(LegSegmentDetector::class, [
                'isLegSegment' => $isLegSegment,
            ]),
            $this->em->getRepository(Tripsegment::class),
            $this->clock
        );
    }
}
