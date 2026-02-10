<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Alliance;
use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\Clusterizer;
use AwardWallet\MainBundle\Service\Lounge\Logger;
use AwardWallet\MainBundle\Service\Lounge\LogProcessor;
use AwardWallet\MainBundle\Service\Lounge\ParseCommand;
use AwardWallet\MainBundle\Service\Lounge\Parser\ParserInterface;
use AwardWallet\MainBundle\Service\Lounge\PriorityManager;
use AwardWallet\MainBundle\Service\Lounge\Scheduler;
use AwardWallet\Tests\Unit\CommandTester;
use Clock\ClockTest;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

abstract class AbstractParseCommandTest extends CommandTester
{
    protected function seeLoungesParsingStarts()
    {
        $this->logContains('lounges parsing starts');
    }

    protected function seeLoungesParsingEnds()
    {
        $this->logContains('lounges parsing ends');
    }

    protected function seeOptimalMode(int $number)
    {
        $this->logContains(sprintf('optimal mode, search lounges for %s top airports', $number));
    }

    protected function seeOptimalModeButNotTimeForScan()
    {
        $this->logContains('optimal mode, but not time for scan');
    }

    protected function seeSearchFromParsers(array $parsers)
    {
        $this->logContains(sprintf('search lounges from: %s', \count($parsers) > 0 ? implode(', ', $parsers) : 'no parsers'));
    }

    protected function seeSearchFromDB()
    {
        $this->logContains('search lounges from db');
    }

    protected function seeRetrievingData(string $parser)
    {
        $this->logContains(sprintf('[%s] retrieving data', $parser));
    }

    protected function seeSavingSources(string $parser)
    {
        $this->logContains(sprintf('[%s] saving sources', $parser));
    }

    protected function seeLoungeWasNotFoundAndTryingFindSimilar(string $parser, LoungeSource $lounge)
    {
        $this->logContains(sprintf(
            '[%s][%s][%s] lounge was not found, trying to find similar',
            $parser,
            $lounge->getAirportCode(),
            $this->formatLoungeSource($lounge)
        ));
    }

    protected function seeSimilarLoungeWasFound(string $parser, LoungeSource $lounge, LoungeSource $similar)
    {
        $this->logContains(sprintf(
            '[%s][%s][%s] similar lounge <%s> was found',
            $parser,
            $lounge->getAirportCode(),
            $this->formatLoungeSource($lounge),
            $similar
        ));
    }

    protected function seeLoungeWasFound(string $parser, LoungeSource $lounge)
    {
        $this->logContains(sprintf(
            '[%s][%s][%s] lounge was found',
            $parser,
            $lounge->getAirportCode(),
            $this->formatLoungeSource($lounge)
        ));
    }

    protected function seeCreateNewLounge(string $parser, LoungeSource $lounge)
    {
        $this->logContains(sprintf(
            '[%s][%s][%s] create new lounge',
            $parser,
            $lounge->getAirportCode(),
            $this->formatLoungeSource($lounge)
        ));
    }

    protected function seeSavedLoungesInAirport(string $parser, string $airport, int $lounges, int $new)
    {
        $this->logContains(sprintf(
            '[%s][%s] found %d lounges, new: %d',
            $parser,
            $airport,
            $lounges,
            $new
        ));
    }

    protected function seeLooksParserIsBrokenInAirport(string $parser, string $airport)
    {
        $this->logContains(sprintf(
            '[%s][%s] Looks like the parser is broken',
            $parser,
            $airport
        ));
    }

    protected function seeDeletedLoungesInAirport(string $parser, string $airport, int $deleted)
    {
        $this->logContains(sprintf(
            '[%s][%s] mark as deleted lounges: %d',
            $parser,
            $airport,
            $deleted
        ));
    }

    protected function seeNoAffectedAirportsFound(string $parser)
    {
        $this->logContains(sprintf(
            '[%s] no affected airports found, the parser is broken',
            $parser
        ));
    }

    protected function dontSeeNoAffectedAirportsFound(string $parser)
    {
        $this->logNotContains(sprintf(
            '[%s] no affected airports found, the parser is broken',
            $parser
        ));
    }

    protected function seeDeletedLounges(string $parser, int $deleted)
    {
        $this->logContains(sprintf(
            '[%s] mark as deleted lounges: %d',
            $parser,
            $deleted
        ));
    }

    protected function seeSkipFrozenParser(string $parser)
    {
        $this->logContains(sprintf('[%s] skip frozen parser', $parser));
    }

    protected function seeStartMerging()
    {
        $this->logContains('start merging');
    }

    protected function seeEndMerging(int $processedAirports)
    {
        $this->logContains(sprintf('end merging, processed %d airports', $processedAirports));
    }

    protected function seeClusters(string $airport, int $number)
    {
        $this->logContains(sprintf(
            '[%s] merge airport, found %d clusters',
            $airport,
            $number
        ));
    }

    protected function seeCreateMergedLounge(string $airport)
    {
        $this->logContains(sprintf(
            '[%s] create lounge',
            $airport
        ));
    }

    protected function seeDeleteLoungeWithoutSources(Lounge $lounge)
    {
        $this->logContains(sprintf(
            '[%s] no sources, delete',
            $this->formatLounge($lounge)
        ));
    }

    protected function seeFreezedProperty(Lounge $lounge, string $propName)
    {
        $this->logContains(sprintf(
            '[%s] freeze action, prop %s',
            $this->formatLounge($lounge),
            $propName
        ));
    }

    protected function seeProbablyLoungeIsAvailable(Lounge $lounge, string $parser)
    {
        $this->logContains(sprintf(
            '[%s] probably lounge is available, but most priority parser "%s" is broken',
            $this->formatLounge($lounge),
            $parser
        ));
    }

    protected function seeMergedLounge(Lounge $lounge)
    {
        $this->logContains(sprintf(
            '[%s] merged lounge',
            $this->formatLounge($lounge),
        ));
    }

    protected function seeLoungesInDb(int $number)
    {
        $count = $this->em->getConnection()->executeQuery('SELECT COUNT(*) FROM Lounge WHERE AirportCode LIKE "$__"')->fetchFirstColumn();
        $this->assertEquals($number, $count[0]);
    }

    protected function seeChangedPropertyInDb(string $propName, int $id, array $criteria = [])
    {
        $this->db->seeInDatabase('LoungeSourceChange', array_merge(
            $criteria,
            [
                'LoungeSourceID' => $id,
                'Property' => $propName,
            ]
        ));
    }

    protected function seeSkipParsing(string $parser)
    {
        $this->logContains(sprintf('[%s] skip parsing, already parsed', $parser));
    }

    protected function seeParserException(string $parser, string $message)
    {
        $this->logContains(sprintf('[%s] Parser error: %s', $parser, $message));
    }

    protected function clearDb()
    {
        $this->db->executeQuery("DELETE FROM Lounge WHERE AirportCode LIKE '\$__'");
        $this->db->executeQuery("DELETE FROM LoungeSource WHERE AirportCode LIKE '\$__'");
    }

    protected function source(string $airportCode, string $name, ?string $parserId = null): LoungeSource
    {
        return (new LoungeSource())
            ->setAirportCode($airportCode)
            ->setName($name)
            ->setSourceCode('test')
            ->setSourceId($parserId ?? StringHandler::getRandomCode(5))
            ->setPageBody(sprintf('page body for %s', $airportCode));
    }

    /**
     * @param LoungeSource[] $lounges
     */
    protected function createParser(array $lounges = [], bool $throwException = false, bool $enabled = true, bool $isParsingFrozen = false): TestParser
    {
        $parserCode = sprintf('parser-%s', StringHandler::getRandomCode(5));

        return new class($parserCode, $lounges, $throwException, $enabled, $isParsingFrozen) extends TestParser {};
    }

    /**
     * @param ParserInterface[] $parsers
     * @param string[]|null $airports
     */
    protected function runCommand(
        array $parsers,
        ?int $popularityTop = null,
        ?array $airports = null,
        bool $db = false,
        bool $isItsTimeForScan = true,
        ?PriorityManager $priorityManager = null,
        ?array $memcached = null
    ) {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get($this->loggerService);
        /** @var Clusterizer $clusterizer */
        $clusterizer = $this->container->get(Clusterizer::class);
        /** @var Mailer $mailer */
        $mailer = $this->container->get(Mailer::class);
        $parserCodes = it($parsers)->map(fn (ParserInterface $parser) => $parser->getCode())->toArray();

        if (!$priorityManager) {
            $priorityManager = $this->makeEmpty(PriorityManager::class, [
                'getParsersByPriority' => $parserCodes,
                'getPatches' => [],
            ]);
        }

        $this->initCommand(
            new ParseCommand(
                $this->construct(Scheduler::class, [new ClockTest()], ['isItsTimeForScan' => $isItsTimeForScan]),
                new Logger($logger, $this->container->get(LogProcessor::class)),
                $this->em,
                $clusterizer,
                $mailer,
                $this->makeEmpty(\Memcached::class, $memcached ?? []),
                $parsers,
                $priorityManager
            )
        );

        $params = ['--test' => true];

        if ($popularityTop) {
            $params['--popularity-top'] = $popularityTop;
        }

        if ($airports) {
            $params['--airport'] = $airports;
        }

        if ($db) {
            $params['--db'] = $db;
        }

        $this->clearLogs();
        $this->executeCommand($params);
    }

    /**
     * @param ParserInterface[] $parsersByPriority
     */
    protected function createPriorityManager(array $parsersByPriority, array $patches): PriorityManager
    {
        $map = function (array $priority) {
            return it($priority)
                ->map(fn (array $parsers) => array_map(fn (ParserInterface $parser) => $parser->getCode(), $parsers))
                ->toArrayWithKeys();
        };

        return $this->makeEmpty(PriorityManager::class, [
            'getParsersByPriority' => array_map(fn (ParserInterface $parser) => $parser->getCode(), $parsersByPriority),
            'getPatches' => $map($patches),
        ]);
    }

    protected function findLounge(array $criteria): ?Lounge
    {
        $lounge = $this->em->getRepository(Lounge::class)->findOneBy($criteria);
        $this->assertNotNull($lounge, sprintf('Lounge not found by criteria: %s', json_encode($criteria)));

        return $lounge;
    }

    protected function findLoungeSource(array $criteria): ?LoungeSource
    {
        $loungeSource = $this->em->getRepository(LoungeSource::class)->findOneBy($criteria);
        $this->assertNotNull($loungeSource, sprintf('LoungeSource not found by criteria: %s', json_encode($criteria)));

        return $loungeSource;
    }

    protected function findAirline(array $criteria): ?Airline
    {
        $airline = $this->em->getRepository(Airline::class)->findOneBy($criteria);
        $this->assertNotNull($airline, sprintf('Airline not found by criteria: %s', json_encode($criteria)));

        return $airline;
    }

    protected function findAlliance(array $criteria): ?Alliance
    {
        $alliance = $this->em->getRepository(Alliance::class)->findOneBy($criteria);
        $this->assertNotNull($alliance, sprintf('Alliance not found by criteria: %s', json_encode($criteria)));

        return $alliance;
    }

    private function formatLounge(Lounge $lounge): string
    {
        return sprintf(
            'lounge id: %s, iata: %s, terminal: %s',
            $lounge->getId() ? sprintf('#%d', $lounge->getId()) : '<null>',
            $lounge->getAirportCode(),
            !empty($lounge->getTerminal()) ? $lounge->getTerminal() : '<null>'
        );
    }

    private function formatLoungeSource(LoungeSource $loungeSource): string
    {
        return sprintf(
            'lounge-source id: %s, iata: %s, terminal: %s',
            sprintf('%s#%s', $loungeSource->getSourceCode(), $loungeSource->getSourceId()),
            $loungeSource->getAirportCode(),
            !empty($loungeSource->getTerminal()) ? $loungeSource->getTerminal() : '<null>'
        );
    }
}
