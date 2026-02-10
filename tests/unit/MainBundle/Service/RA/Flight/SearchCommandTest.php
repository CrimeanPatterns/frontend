<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery as EntityRAFlightSearchQuery;
use AwardWallet\MainBundle\Service\RA\Flight\Api;
use AwardWallet\MainBundle\Service\RA\Flight\DTO\ApiSearchResult;
use AwardWallet\MainBundle\Service\RA\Flight\LoggerFactory;
use AwardWallet\MainBundle\Service\RA\Flight\SearchCommand;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchQuery;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\CommandTester;
use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;

/**
 * @group frontend-unit
 */
class SearchCommandTest extends CommandTester
{
    /**
     * @var SearchCommand
     */
    protected $command;

    public function _before()
    {
        parent::_before();

        $this->db->executeQuery("DELETE FROM RAFlightSearchQuery WHERE Parsers = 'test'");
    }

    public function testDaily()
    {
        $id = $this->dbBuilder->makeRAFlightSearchQuery(
            new RAFlightSearchQuery(
                ['MOW'],
                ['LED'],
                $from = date_create((date('Y') + 1) . '-01-01'),
                (clone $from)->modify('+1 day'),
                $user = new User(),
                null,
                [],
                [
                    'FlightClass' => EntityRAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
                    'SearchInterval' => EntityRAFlightSearchQuery::SEARCH_INTERVAL_DAILY,
                    'Parsers' => 'test',
                ]
            )
        );
        $this->runCommand(Expected::once(fn () => new ApiSearchResult([])));
        $this->logContains('processed 1 queries');

        $this->db->updateInDatabase('RAFlightSearchQuery', ['LastSearchDate' => date('Y-m-d H:i:s')], ['RAFlightSearchQueryID' => $id]);
        $this->runCommand(Expected::never(fn () => new ApiSearchResult([])));
        $this->logContains('processed 0 queries');

        $this->db->updateInDatabase('RAFlightSearchQuery', ['LastSearchDate' => date('Y-m-d H:i:s', strtotime('-1 day'))], ['RAFlightSearchQueryID' => $id]);
        $this->runCommand(Expected::once(fn () => new ApiSearchResult([])));
        $this->logContains('processed 1 queries');

        $this->dbBuilder->makeRAFlightSearchQuery(
            new RAFlightSearchQuery(
                ['MOW'],
                ['LED'],
                $from = date_create((date('Y') + 1) . '-01-02'),
                (clone $from)->modify('+1 day'),
                $user,
                null,
                [],
                [
                    'FlightClass' => EntityRAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
                    'SearchInterval' => EntityRAFlightSearchQuery::SEARCH_INTERVAL_DAILY,
                    'Parsers' => 'test',
                ]
            )
        );
        $this->runCommand(Expected::exactly(2, fn () => new ApiSearchResult([])));
        $this->logContains('processed 2 queries');

        $this->dbBuilder->makeRAFlightSearchQuery(
            new RAFlightSearchQuery(
                ['MOW'],
                ['LED'],
                $from = date_create((date('Y') + 1) . '-01-05'),
                (clone $from)->modify('+1 day'),
                $user,
                null,
                [],
                [
                    'FlightClass' => EntityRAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
                    'SearchInterval' => EntityRAFlightSearchQuery::SEARCH_INTERVAL_DAILY,
                    'Parsers' => 'test',
                ]
            )
        );
        $this->runCommand(Expected::exactly(3, fn () => new ApiSearchResult([])));
        $this->logContains('processed 3 queries');
    }

    public function testWeekly()
    {
        $id = $this->dbBuilder->makeRAFlightSearchQuery(
            new RAFlightSearchQuery(
                ['MOW'],
                ['LED'],
                $from = date_create((date('Y') + 1) . '-01-01'),
                (clone $from)->modify('+1 day'),
                $user = new User(),
                null,
                [],
                [
                    'FlightClass' => EntityRAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
                    'SearchInterval' => EntityRAFlightSearchQuery::SEARCH_INTERVAL_WEEKLY,
                    'Parsers' => 'test',
                ]
            )
        );
        $this->runCommand(Expected::once(fn () => new ApiSearchResult([])));
        $this->logContains('processed 1 queries');

        $this->db->updateInDatabase('RAFlightSearchQuery', ['LastSearchDate' => date('Y-m-d H:i:s')], ['RAFlightSearchQueryID' => $id]);
        $this->runCommand(Expected::never(fn () => new ApiSearchResult([])));
        $this->logContains('processed 0 queries');

        $this->db->updateInDatabase('RAFlightSearchQuery', ['LastSearchDate' => date('Y-m-d H:i:s', strtotime('-1 week'))], ['RAFlightSearchQueryID' => $id]);
        $this->runCommand(Expected::once(fn () => new ApiSearchResult([])));
        $this->logContains('processed 1 queries');

        $this->dbBuilder->makeRAFlightSearchQuery(
            new RAFlightSearchQuery(
                ['MOW'],
                ['LED'],
                $from = date_create((date('Y') + 1) . '-01-02'),
                (clone $from)->modify('+1 day'),
                $user,
                null,
                [],
                [
                    'FlightClass' => EntityRAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
                    'SearchInterval' => EntityRAFlightSearchQuery::SEARCH_INTERVAL_WEEKLY,
                    'Parsers' => 'test',
                ]
            )
        );
        $this->runCommand(Expected::exactly(2, fn () => new ApiSearchResult([])));
        $this->logContains('processed 2 queries');

        $this->dbBuilder->makeRAFlightSearchQuery(
            new RAFlightSearchQuery(
                ['MOW'],
                ['LED'],
                $from = date_create((date('Y') + 1) . '-01-05'),
                (clone $from)->modify('+1 day'),
                $user,
                null,
                [],
                [
                    'FlightClass' => EntityRAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
                    'SearchInterval' => EntityRAFlightSearchQuery::SEARCH_INTERVAL_WEEKLY,
                    'Parsers' => 'test',
                ]
            )
        );
        $this->runCommand(Expected::exactly(3, fn () => new ApiSearchResult([])));
        $this->logContains('processed 3 queries');
    }

    public function testPastQuery()
    {
        $id = $this->dbBuilder->makeRAFlightSearchQuery(
            new RAFlightSearchQuery(
                ['MOW'],
                ['LED'],
                $from = date_create('-2 day'),
                (clone $from)->modify('+1 day'),
                $user = new User(),
                null,
                [],
                [
                    'FlightClass' => EntityRAFlightSearchQuery::FLIGHT_CLASS_ECONOMY,
                    'SearchInterval' => EntityRAFlightSearchQuery::SEARCH_INTERVAL_WEEKLY,
                    'Parsers' => 'test',
                ]
            )
        );
        $this->runCommand(Expected::never());
        $this->logContains('processed 0 queries');

        $this->db->updateInDatabase('RAFlightSearchQuery', ['DepDateTo' => date('Y-m-d H:i:s')], ['RAFlightSearchQueryID' => $id]);
        $this->runCommand(Expected::once(fn () => new ApiSearchResult([])));
        $this->logContains('processed 1 queries');
    }

    private function runCommand(StubMarshaler $expected)
    {
        $this->cleanCommand();
        $this->command = new SearchCommand(
            $this->makeEmpty(Api::class, [
                'search' => $expected,
            ]),
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
