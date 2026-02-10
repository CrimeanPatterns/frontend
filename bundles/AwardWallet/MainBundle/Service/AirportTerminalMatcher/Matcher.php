<?php

namespace AwardWallet\MainBundle\Service\AirportTerminalMatcher;

use AwardWallet\MainBundle\Service\LogProcessor;
use Doctrine\DBAL\Connection;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class Matcher
{
    public const MAIN_TERMINAL = 'Main';

    private Connection $connection;

    private LoggerInterface $logger;

    private \Memcached $memcached;

    private array $loadedAliases = [];

    public function __construct(Connection $connection, LoggerInterface $logger, \Memcached $memcached)
    {
        $this->connection = $connection;
        $logProcessor = new LogProcessor('terminal_matcher');
        $this->logger = new Logger('terminal_matcher', [new PsrHandler($logger)], [$logProcessor]);
        $this->memcached = $memcached;
    }

    /**
     * match airport terminal.
     */
    public function match(string $airportCode, string $rawTerminal): ?string
    {
        $matched = [];

        foreach ($this->loadAliases($airportCode) as $terminalName => $aliases) {
            $aliases = array_merge(
                [
                    $this->prepareAlias($terminalName),
                ],
                array_map(fn ($alias) => $this->prepareAlias($alias), $aliases)
            );

            $regex = sprintf("/^(%s)$/ims", implode('|', $aliases));

            if (preg_match($regex, $rawTerminal)) {
                $matched[] = $terminalName;
            }
        }

        $matchedCount = count($matched);

        if ($matchedCount > 1) {
            $cacheKey = "terminal_matcher_log_" . $airportCode;
            $result = $this->memcached->get($cacheKey);

            if ($result === false) {
                $this->logger->info(sprintf(
                    'airport terminal matching: multiple matches, "%s", rawTerminal: "%s", matched: "%s"',
                    $airportCode, $rawTerminal, implode('", "', $matched)
                ));
                $this->memcached->set($cacheKey, 1, 60 * 60 * 3);
            }

            return $rawTerminal;
        } elseif ($matchedCount === 0) {
            return $rawTerminal;
        }

        return $matched[0];
    }

    private function prepareAlias(string $alias): string
    {
        $alias = preg_quote($alias);
        $alias = str_replace(" ", '\s*', $alias);

        return str_replace("/", '\\/', $alias);
    }

    private function loadAliases(string $airportCode): array
    {
        if (isset($this->loadedAliases[$airportCode])) {
            return $this->loadedAliases[$airportCode];
        }

        return $this->loadedAliases[$airportCode] = stmtAssoc(
            $this->connection->executeQuery("
                SELECT
                    t.Name AS TerminalName,
                    ta.Alias
                FROM
                    AirportTerminal t
                    LEFT JOIN AirportTerminalAlias ta ON ta.AirportTerminalID = t.AirportTerminalID
                WHERE
                    t.AirportCode = ?
                ORDER BY t.Name ASC
            ", [$airportCode])
        )
            ->groupAdjacentBy(fn (array $a, array $b) => $a['TerminalName'] <=> $b['TerminalName'])
            ->reindexByPropertyPath('[0][TerminalName]')
            ->mapIndexed(function (array $aliases) {
                if (count($aliases) === 1 && is_null($aliases[0]['Alias'])) {
                    return [];
                }

                return array_map(function ($alias) {
                    return $alias['Alias'];
                }, $aliases);
            })
            ->toArrayWithKeys();
    }
}
