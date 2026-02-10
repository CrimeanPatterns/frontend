<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Service\RA\Flight\CleanCommand;
use AwardWallet\MainBundle\Service\RA\Flight\LoggerFactory;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchQuery;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\CommandTester;

/**
 * @group frontend-unit
 */
class CleanCommandTest extends CommandTester
{
    /**
     * @var CleanCommand
     */
    protected $command;

    public function _before()
    {
        parent::_before();

        $this->db->executeQuery("DELETE FROM RAFlightSearchQuery WHERE Parsers = 'test'");
    }

    public function testNoQueries()
    {
        $this->runCommand();
        $this->logContains('deleted 0 old flight search queries');
    }

    public function testDeleteQueries()
    {
        $this->dbBuilder->makeRAFlightSearchQuery(new RAFlightSearchQuery(
            ['JFK'],
            ['LAX'],
            date_create('+7 days'),
            date_create('+14 days'),
            new User(),
            null,
            [],
            [
                'Parsers' => 'test',
                'DeleteDate' => date('Y-m-d H:i:s', strtotime('-35 days')),
            ]
        ));
        $this->runCommand();
        $this->logContains('deleted 1 old flight search queries');
    }

    private function runCommand()
    {
        $this->cleanCommand();
        $this->command = new CleanCommand(
            $this->em->getConnection(),
            $this->container->get(LoggerFactory::class)
        );
        $this->initCommand($this->command);
        $this->clearLogs();
        $this->executeCommand([
            '--test' => true,
        ]);
    }
}
