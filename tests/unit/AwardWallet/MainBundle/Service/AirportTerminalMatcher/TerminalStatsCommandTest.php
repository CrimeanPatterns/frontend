<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\AirportTerminalMatcher;

use AwardWallet\MainBundle\Service\AirportTerminalMatcher\Matcher;
use AwardWallet\MainBundle\Service\AirportTerminalMatcher\TerminalStatsCommand;
use AwardWallet\Tests\Modules\DbBuilder\FlightStats;
use AwardWallet\Tests\Modules\DbBuilder\Trip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\CommandTester;
use Clock\ClockInterface;
use Clock\ClockTest;
use Doctrine\DBAL\Connection;

/**
 * @group frontend-unit
 */
class TerminalStatsCommandTest extends CommandTester
{
    /**
     * @var TerminalStatsCommand
     */
    protected $command;

    private ?ClockInterface $clock;

    public function _before()
    {
        parent::_before();

        $this->initCommand(new TerminalStatsCommand(
            $this->container->get(Connection::class),
            $this->container->get(Connection::class),
            $this->container->get($this->loggerService),
            $this->clock = new ClockTest()
        ));
        $this->db->executeQuery("DELETE FROM FlightStats");
        $this->db->executeQuery("DELETE FROM AirportTerminal WHERE AirportCode IN ('ABC', 'QWE')");
    }

    public function _after()
    {
        $this->cleanCommand();

        parent::_after();
    }

    public function test()
    {
        $past = (clone $this->clock->current()->getAsDateTime())->modify('-1 HOUR');
        $this->dbBuilder->makeFlightStats(new FlightStats($flightStatsFields = [
            'DepCode' => 'ABC',
            'DepDate' => ($depDate = new \DateTime('+1 DAY'))->format('Y-m-d H:i:s'),
            'ArrCode' => 'QWE',
            'ArrDate' => ($arrDate = (clone $depDate)->modify('+3 HOUR'))->format('Y-m-d H:i:s'),
            'FlightNumber' => '01',
            'FlightNumber2' => '01',
            'DepTerminal' => null,
            'ArrTerminal' => '4',
            'BookedAirline' => 'WN',
            'OperatingAirline' => 'WN',
            'PrimaryMarketingAirline' => 'WN',
            'CreateDate' => $past->format('Y-m-d H:i:s'),
        ]));

        $this->runCommand();
        $this->logContains('done, processed flights: 1');
        $this->seeTerminal('ABC', Matcher::MAIN_TERMINAL);
        $this->seeTerminal('QWE', '4');
        $this->seeAliases('ABC', Matcher::MAIN_TERMINAL, 0);
        $this->seeAliases('QWE', '4', 0);
        $this->db->dontSeeInDatabase('FlightStats', [
            'DepCode' => 'ABC',
            'DepDate' => $depDate->format('Y-m-d H:i:s'),
            'CreateDate' => $past->format('Y-m-d H:i:s'),
        ]);

        $this->dbBuilder->makeTrip(new Trip(
            'XXX1',
            [
                new TripSegment(
                    'ABC',
                    'ABC',
                    $depDate,
                    'QWE',
                    'QWE',
                    $arrDate,
                    null,
                    [
                        'DepartureTerminal' => 'Main Terminal',
                        'ArrivalTerminal' => 'Terminal 2',
                        'FlightNumber' => '001',
                    ]
                ),
            ],
            $user = new User()
        ));
        $this->dbBuilder->makeTrip(new Trip(
            'XXX2',
            [
                new TripSegment(
                    'ABC',
                    'ABC',
                    $depDate,
                    'QWE',
                    'QWE',
                    $arrDate,
                    null,
                    [
                        'ArrivalTerminal' => '2 Terminal',
                        'FlightNumber' => '0001',
                    ]
                ),
            ],
            $user
        ));

        $this->db->haveInDatabase('FlightStats', $flightStatsFields);
        $this->runCommand();
        $this->logContains('done, processed flights: 1');
        $this->seeAliases('ABC', Matcher::MAIN_TERMINAL, 1);
        $this->seeAliases('QWE', '4', 2);
    }

    private function seeTerminal(string $airportCode, string $terminal)
    {
        $this->db->seeInDatabase('AirportTerminal', [
            'AirportCode' => $airportCode,
            'Name' => $terminal,
        ]);
    }

    private function seeTerminals(string $airportCode, int $count)
    {
        $this->assertEquals($count, $this->db->grabCountFromDatabase('AirportTerminal', ['AirportCode' => $airportCode]));
    }

    private function seeTerminalAlias(string $airportCode, string $terminal, string $alias)
    {
        $terminalId = $this->db->grabFromDatabase('AirportTerminal', 'AirportTerminalID', ['AirportCode' => $airportCode, 'Name' => $terminal]);
        $this->assertNotEmpty($terminalId);
        $this->db->seeInDatabase('AirportTerminalAlias', ['AirportTerminalID' => $terminalId, 'Alias' => $alias]);
    }

    private function seeAliases(string $airportCode, string $terminal, int $count)
    {
        $terminalId = $this->db->grabFromDatabase('AirportTerminal', 'AirportTerminalID', ['AirportCode' => $airportCode, 'Name' => $terminal]);
        $this->assertNotEmpty($terminalId);
        $this->assertEquals($count, $this->db->grabCountFromDatabase('AirportTerminalAlias', ['AirportTerminalID' => $terminalId]));
    }

    private function runCommand()
    {
        $this->clearLogs();
        $this->executeCommand();
    }
}
