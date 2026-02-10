<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Command\ClearDiffCommand;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\CustomDb;
use Monolog\Logger;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class ClearDiffCommandTest extends BaseUserTest
{
    public const CLASS_MAP = [
        Tripsegment::class => 'S',
        Rental::class => 'L',
        Reservation::class => 'R',
        Restaurant::class => 'E',
    ];
    /**
     * @var ClearDiffCommand
     */
    private $command;

    public function _before()
    {
        parent::_before();

        $this->command = $this->container->get(ClearDiffCommand::class);
        $this->command->setApplication($this->getApp());
    }

    public function testClearDiffCommand()
    {
        $entities = [];

        // Trip
        $trip = (new Trip())
            ->setUser($this->user)
            ->setCreateDate(new \DateTime());

        $this->em->persist($trip);

        $changeDate = new \DateTime();

        $entities[] = $tripSegment = (new Tripsegment())
            ->setTripid($trip)
            ->setDepname('test1')
            ->setDepdate(new \DateTime())
            ->setArrname('test2')
            ->setArrdate(new \DateTime())
            ->setChangeDate($changeDate);
        $tripSegment->setMarketingAirlineConfirmationNumber('TESTCN');

        // Reservation
        $entities[] = $reservation = (new Reservation())
            ->setHotelname('test1')
            ->setCheckindate(new \DateTime())
            ->setCheckoutdate(new \DateTime())
            ->setUser($this->user)
            ->setCreateDate(new \DateTime())
            ->setChangeDate($changeDate);
        $reservation->setConfirmationNumber('TESTCN');

        // Restaurant
        $entities[] = $event = (new Restaurant())
            ->setUser($this->user)
            ->setName('test')
            ->setStartdate(new \DateTime())
            ->setCreateDate(new \DateTime())
            ->setChangeDate($changeDate);
        $event->setConfirmationNumber('TESTCN');

        // Rental
        $entities[] = $rental = (new Rental())
            ->setPickuplocation('test1')
            ->setPickupdatetime(new \DateTime())
            ->setDropofflocation('test2')
            ->setDropoffdatetime(new \DateTime())
            ->setUser($this->user)
            ->setCreateDate(new \DateTime())
            ->setChangeDate($changeDate);
        $rental->setConfirmationNumber('TESTCN');

        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }

        $this->em->flush();

        $changesShouldBeDeleted = [];
        $changesShouldNotBeDeleted = [];

        $changeDateShouldNotBeDeleted = clone $changeDate;
        $changeDateShouldNotBeDeleted->add(new \DateInterval('P1D'));
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        foreach ($entities as $entity) {
            $sourceId = self::CLASS_MAP[get_class($entity)] . '.' . $this->getId($entity);

            $changesShouldBeDeleted[$sourceId] = (int) $db->haveInDatabase('DiffChange', [
                'SourceID' => $sourceId,
                'Property' => 'shouldBeDeleted',
                'OldVal' => 'testcleardiffchange',
                'NewVal' => '2',
                'ChangeDate' => $changeDate->format('Y-m-d H:i:s'),
                'ExpirationDate' => (new \DateTime('-1 day'))->format('Y-m-d H:i:s'),
            ]);

            $changesShouldNotBeDeleted[$sourceId] = (int) $db->haveInDatabase('DiffChange', [
                'SourceID' => $sourceId,
                'Property' => 'shouldNotBeDeleted',
                'OldVal' => 'testcleardiffchange',
                'NewVal' => '2',
                'ChangeDate' => $changeDateShouldNotBeDeleted->format('Y-m-d H:i:s'),
                'ExpirationDate' => (new \DateTime('+1 day'))->format('Y-m-d H:i:s'),
            ]);
        }

        $logger = $this->prophesize(Logger::class)
            ->info(Argument::containingString(sprintf("updated %d rows", count($entities))))
            ->getObjectProphecy()
            ->info(Argument::containingString(sprintf("deleted %d rows", count($entities))))
            ->getObjectProphecy()
            ->reveal();
        $this->mockService(LoggerInterface::class, $logger);
        $this->command->run($this->prophesize(InputInterface::class)->reveal(), $this->prophesize(OutputInterface::class)->reveal());

        $foundInDb = [];

        foreach ($changesShouldNotBeDeleted as $sourceId => $changeId) {
            $foundInDb[$sourceId] = (int) $db->grabFromDatabase('DiffChange', 'DiffChangeID', ['DiffChangeID' => $changeId]);
        }

        assertEquals($changesShouldNotBeDeleted, $foundInDb, 'changes were not removed from db');

        $foundInDb = [];

        foreach ($changesShouldBeDeleted as $sourceId => $changeId) {
            if ($foundId = (int) $db->grabFromDatabase('DiffChange', 'DiffChangeID', ['DiffChangeID' => $changeId])) {
                $foundInDb[$sourceId] = $foundId;
            }
        }

        assertEquals([], $foundInDb, 'changes were removed from db');
    }

    public function getId($entity)
    {
        return $entity instanceof Itinerary ? $entity->getId() : $entity->getTripsegmentId();
    }
}
