<?php

namespace AwardWallet\Tests\MainBundle\Service\FlightStats;

use AwardWallet\Common\FlightStats\Airline as FSAirline;
use AwardWallet\Common\FlightStats\Communicator;
use AwardWallet\MainBundle\Entity\Airline as EntityAirline;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Service\FlightStats\AirlinesUpdater;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AirlinesUpdaterTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityAirline[]
     */
    private $entityAirlines = [];

    /**
     * @var FSAirline[]
     */
    private $fsAirlines = [];

    /**
     * @var AirlineRepository
     */
    private $airlineRepository;

    /**
     * @var Communicator
     */
    private $communicator;

    public function _before()
    {
        $this->logger = $this->makeEmpty(LoggerInterface::class);
        $this->entityAirlines = [
            'sync only' => $this->createAirline('FS1', 'sync only', 'IA1', 'IC1', true),
            'update' => $this->createAirline('FS2', 'update', 'IA2', 'IC2', true),
            'obsolete' => $this->createAirline('FS3', 'obsolete', 'IA3', 'IC3', true),
            'override' => $this->createAirline('LZ', 'Belle Air', 'LZ', 'LBY', true),
        ];
        $this->fsAirlines = [
            'sync only' => new FSAirline('FS1', 'IA1', 'IC1', 'sync only', null, true),
            'updated' => new FSAirline('FS2', 'IAU', 'ICU', 'updated', null, false),
            'new' => new FSAirline('FS4', 'IA4', 'IC4', 'new', null, true),
            'override' => new FSAirline('LZ', 'LZ', 'LBY', 'Belle Air', null, true),
        ];
        /** @var AirlineRepository $airlineRepository */
        $this->airlineRepository = $this->makeEmpty(AirlineRepository::class, [
            'findAll' => [
                $this->entityAirlines['sync only'],
                $this->entityAirlines['update'],
                $this->entityAirlines['obsolete'],
                $this->entityAirlines['override'],
            ],
        ]);
        /** @var Communicator $communicator */
        $this->communicator = $this->makeEmpty(Communicator::class, [
            'getAllAirlines' => [
                $this->fsAirlines['sync only'],
                $this->fsAirlines['updated'],
                $this->fsAirlines['new'],
                $this->fsAirlines['override'],
            ],
        ]);
    }

    public function testSync()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->makeEmpty(EntityManagerInterface::class, [
            'persist' => function (EntityAirline $airline) {
                $this->assertFields($airline, 'FS4', 'new', 'IA4', 'IC4', true, new \DateTime());
            },
            'remove' => function (EntityAirline $airline) {
                $this->assertEquals($this->entityAirlines['obsolete'], $airline);
            },
        ], $this);
        $airlineUpdater = new AirlinesUpdater($this->logger, $this->communicator, $this->airlineRepository, $entityManager);

        $airlineUpdater->sync();
        $this->assertFields($this->entityAirlines['sync only'], 'FS1', 'sync only', 'IA1', 'IC1', true, null);
        $this->assertFields($this->entityAirlines['update'], 'FS2', 'updated', 'IAU', 'ICU', false, new \DateTime());
        $this->assertFields($this->entityAirlines['override'], 'LZ', 'Belle Air', 'LZ', 'LBY', false, new \DateTime());
    }

    public function testDryRun()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->makeEmpty(EntityManagerInterface::class, [
            'persist' => Stub::never(),
            'remove' => Stub::never(),
        ], $this);
        $airlineUpdater = new AirlinesUpdater($this->logger, $this->communicator, $this->airlineRepository, $entityManager);

        $airlineUpdater->sync(AirlinesUpdater::DRY_RUN);
        $this->assertFields($this->entityAirlines['sync only'], 'FS1', 'sync only', 'IA1', 'IC1', true, null);
        $this->assertFields($this->entityAirlines['update'], 'FS2', 'update', 'IA2', 'IC2', true, null);
        $this->assertFields($this->entityAirlines['override'], 'LZ', 'Belle Air', 'LZ', 'LBY', true, null);
    }

    private function assertFields(EntityAirline $airline, string $fsCode, string $name, string $iata, string $icao, bool $active, ?\DateTime $lastUpdate)
    {
        $this->assertSame($fsCode, $airline->getFsCode());
        $this->assertSame($name, $airline->getName());
        $this->assertSame($iata, $airline->getCode());
        $this->assertSame($icao, $airline->getIcao());
        $this->assertSame($active, $airline->isActive());
        $this->assertEqualsWithDelta($lastUpdate, $airline->getLastupdatedate(), 1, null);
    }

    private function createAirline(string $fsCode, string $name, string $iata, string $icao, bool $active)
    {
        $airline = new EntityAirline();
        $airline->setFsCode($fsCode);
        $airline->setCode($iata);
        $airline->setName($name);
        $airline->setIcao($icao);
        $airline->setActive($active);

        return $airline;
    }
}
