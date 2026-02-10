<?php

namespace AwardWallet\MainBundle\Service\FlightStats;

use AwardWallet\Common\FlightStats\Airline as FSAirline;
use AwardWallet\Common\FlightStats\Communicator;
use AwardWallet\MainBundle\Entity\Airline as EntityAirline;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AirlinesUpdater
{
    public const DRY_RUN = true;

    /**
     * Override FlightStats data
     * [<airline name> => [<override field> => <override value>...]...].
     *
     * @var array
     */
    public const OVERRIDE = [
        // Air-Taxi Europe
        'TWG' => [
            'active' => false,
        ],
        // Belle Air
        'LZ' => [
            'active' => false,
        ],
        // Belle Air Europe
        'L9' => [
            'active' => false,
        ],
        // Aerosvit Airlines
        'VV' => [
            'active' => false,
        ],
        // Air Bagan - Ceased operations August 2018
        'W9*' => [
            'active' => false,
        ],
        // Cairo Aviation - Ceased operations	2018
        'OE' => [
            'active' => false,
        ],
        // Asia Overnight Express
        'OE*' => [
            'active' => false,
        ],
        // Air Liaison - missing iata
        'LIZ' => [
            'iata' => 'DU',
        ],
    ];

    /**
     * @var FSAirline[]
     */
    protected $fsAirlines = [];

    /**
     * @var EntityAirline[]
     */
    protected $entityAirlines = [];

    /**
     * @var FSAirline[]
     */
    protected $fsCodeToFSAirline = [];

    /**
     * @var EntityAirline[]
     */
    protected $fsCodeToEntityAirline = [];

    private LoggerInterface $logger;

    private Communicator $communicator;

    private AirlineRepository $airlineRepository;

    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        Communicator $communicator,
        AirlineRepository $airlineRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->communicator = $communicator;
        $this->airlineRepository = $airlineRepository;
        $this->entityManager = $entityManager;
    }

    public function sync(bool $dryRun = false)
    {
        $this->fsAirlines = $this->communicator->getAllAirlines();

        if (null === $this->fsAirlines) {
            $this->logger->warning('FlightStats API communicator returned null: aborting airlines update');

            return;
        }
        $this->override($this->fsAirlines);
        $this->fsAirlines = $this->add($this->fsAirlines);

        $this->entityAirlines = $this->airlineRepository->findAll();
        $this->buildIndexes();

        $codesFromFS = array_map(function (FSAirline $airline) {
            return $airline->getFs();
        }, $this->fsAirlines);
        $entityFSCodes = array_map(function (EntityAirline $airline) {
            return $airline->getFsCode();
        }, $this->entityAirlines);
        $newCodes = array_diff($codesFromFS, $entityFSCodes);
        $obsoleteCodes = array_diff($entityFSCodes, $codesFromFS);
        $syncCodes = array_intersect($codesFromFS, $entityFSCodes);
        $updateCodes = array_filter($syncCodes, function (string $fsCode) {
            $fsAirline = $this->fsCodeToFSAirline[$fsCode];
            $entityAirline = $this->fsCodeToEntityAirline[$fsCode];

            return $fsAirline->getName() !== $entityAirline->getName()
                || $fsAirline->getIcao() !== $entityAirline->getIcao()
                || $fsAirline->getIata() !== $entityAirline->getCode()
                || $fsAirline->isActive() !== $entityAirline->isActive();
        });

        if (!empty($newCodes)) {
            $count = count($newCodes);
            $this->logger->notice("Received $count new airlines from FlightStats. FS Codes are: " . implode(', ', $newCodes));
        }

        if (!empty($obsoleteCodes)) {
            $count = count($obsoleteCodes);
            $this->logger->warning("Found $count obsolete airlines. FS Codes are: " . implode(', ', $obsoleteCodes));
        }
        $count = count($syncCodes);
        $this->logger->notice("Syncing $count airlines");

        if (!empty($updateCodes)) {
            $count = count($updateCodes);
            $this->logger->notice("Updating $count airlines. FS Codes are: " . implode(', ', $updateCodes));
        }

        if (!$dryRun) {
            $this->addNewAirlines($newCodes);
            $this->removeObsoleteAirlines($obsoleteCodes);
            $this->updateAirlines($updateCodes);
            $this->entityManager->flush();
        }
    }

    private function buildIndexes()
    {
        foreach ($this->fsAirlines as $fsAirline) {
            $this->fsCodeToFSAirline[$fsAirline->getFs()] = $fsAirline;
        }

        foreach ($this->entityAirlines as $entityAirline) {
            $this->fsCodeToEntityAirline[$entityAirline->getFsCode()] = $entityAirline;
        }
    }

    private function addNewAirlines(array $fsCodes)
    {
        foreach ($fsCodes as $fsCode) {
            $fsAirline = $this->fsCodeToFSAirline[$fsCode];
            $this->createNewAirline($fsAirline);
        }
    }

    private function removeObsoleteAirlines(array $fsCodes)
    {
        foreach ($fsCodes as $fsCode) {
            $entityAirline = $this->fsCodeToEntityAirline[$fsCode];
            $this->entityManager->remove($entityAirline);
        }
    }

    private function updateAirlines(array $fsCodes)
    {
        foreach ($fsCodes as $fsCode) {
            $entityAirline = $this->fsCodeToEntityAirline[$fsCode];
            $fsAirline = $this->fsCodeToFSAirline[$fsCode];
            $this->updateAirline($entityAirline, $fsAirline);
        }
    }

    private function updateAirline(EntityAirline $entityAirline, FSAirline $fsAirline)
    {
        $entityAirline->setFsCode($fsAirline->getFs());
        $entityAirline->setName($fsAirline->getName());
        $entityAirline->setCode($fsAirline->getIata());
        $entityAirline->setIcao($fsAirline->getIcao());
        $entityAirline->setActive($fsAirline->isActive());
        $entityAirline->setLastupdatedate(new \DateTime());
    }

    private function createNewAirline(FSAirline $fsAirline)
    {
        $entityAirline = new EntityAirline();
        $this->updateAirline($entityAirline, $fsAirline);
        $this->entityManager->persist($entityAirline);

        return $entityAirline;
    }

    /**
     * @param FSAirline[] $fsAirlines
     */
    private function override(array $fsAirlines)
    {
        foreach ($fsAirlines as $fsAirline) {
            if (isset(self::OVERRIDE[$fsAirline->getFs()])) {
                $this->overrideAirline($fsAirline, self::OVERRIDE[$fsAirline->getFs()]);
            }
        }
    }

    private function overrideAirline(FSAirline $fsAirline, array $overrideValues)
    {
        // invoke setter names explicitly for easier maintenance
        foreach ($overrideValues as $overrideField => $overrideValue) {
            switch ($overrideField) {
                case 'name':
                    $fsAirline->setName($overrideValue);

                    break;

                case 'iata':
                    $fsAirline->setIata($overrideValue);

                    break;

                case 'icao':
                    $fsAirline->setIcao($overrideValue);

                    break;

                case 'active':
                    $fsAirline->setActive($overrideValue);

                    break;

                default:
                    throw new \LogicException("Unknown field $overrideField", 400);
            }
        }
    }

    /**
     * @param FSAirline[] $fsAirlines
     */
    // hardCode - fs not have record
    private function add(array $fsAirlines)
    {
        $hasHaActive = false;

        foreach ($fsAirlines as $fsAirline) {
            // check H1 active=1
            if ($fsAirline->getFs() == 'H1' && $fsAirline->isActive()) {
                $hasHaActive = true;

                break;
            }
        }

        if ($hasHaActive) {
            $this->logger->notice('FlightStats API communicator returned info about H1 with active = 1. So can delete hardcode');

            return $fsAirlines;
        }

        $newLine = null;
        $fsCodes = [];

        foreach ($fsAirlines as $fsAirline) {
            $fsCodes[$fsAirline->getFs()] = $fsAirline;
        }

        if (!isset($fsCodes['H1*'])) {
            $newFSCode = 'H1*';
        } elseif (!isset($fsCodes['H1Z'])) {
            $newFSCode = 'H1Z';
        } elseif (!isset($fsCodes['H1Y'])) {
            $newFSCode = 'H1Y';
        } else {
            $this->logger->notice('FlightStats API communicator no returned info about H1 with active = 1. And has lines with FSCode in list (H1*, H1Z, H1Y). So correct hardcode');

            return $fsAirlines;
        }
        $newLine = new FSAirline($newFSCode, 'H1', null, 'Hahn Air Systems', null, true);
        $fsAirlines[] = $newLine;

        return $fsAirlines;
    }
}
