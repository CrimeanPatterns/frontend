<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\DBAL\Connection;

class AirlineNameResolver
{
    public const PREFIX = 'airline_fuzzy_name';

    private Connection $connection;

    private CacheManager $cache;

    public function __construct(Connection $connection, CacheManager $cache)
    {
        $this->connection = $connection;
        $this->cache = $cache;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function resolve($airlineName)
    {
        $airlineName = preg_replace('/\(.*\)/', '', $airlineName); // custom segments
        $airlineName = strtolower(trim(preg_replace('/\s+/', ' ', $airlineName)));

        if (empty($airlineName)) {
            return null;
        }

        if (strlen($airlineName) < 2) {
            return null;
        }

        return $this->cache->load(new CacheItemReference(
            self::getKey($airlineName),
            self::getTags(),
            function () use ($airlineName) {
                if (strlen($airlineName) == 2) {
                    return $this->getAirlineByIATA($airlineName);
                }

                if (strlen($airlineName) == 3) {
                    $airline = $this->getAirlineByICAO($airlineName);

                    if (empty($airline)) {
                        $airline = $this->getAirlineByName($airlineName);
                    }

                    return $airline;
                }

                return $this->getAirlineByName($airlineName);
            }
        ));
    }

    /**
     * @return string|null
     */
    public function resolveToIATACode($airlineName)
    {
        $airline = $this->resolve($airlineName);

        if (!empty($airline)) {
            return $airline['Code'];
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function resolveToICAOCode($airlineName)
    {
        $airline = $this->resolve($airlineName);

        if (!empty($airline)) {
            return $airline['ICAO'];
        }

        return null;
    }

    private function getKey($airlineName)
    {
        return implode('_', [
            self::PREFIX,
            preg_replace('/[^a-z0-9]/ims', '_', $airlineName),
        ]);
    }

    private static function getTags()
    {
        return Tags::addTagPrefix([Tags::TAG_AIRLINE]);
    }

    /**
     * @return array|null
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getAirlineByName($airlineName)
    {
        $connection = $this->connection;

        $sql = 'SELECT a.* FROM Airline a LEFT JOIN AirlineAlias aa on a.AirlineID = aa.AirlineID WHERE aa.Alias = ? OR a.Name = ? ORDER BY aa.AirlineAliasID DESC';
        $stmt = $connection->executeQuery($sql, [$airlineName, $airlineName], [\PDO::PARAM_STR, \PDO::PARAM_STR]);

        $airline = $stmt->fetch();
        $stmt->closeCursor();

        if (!empty($airline)) {
            return $airline;
        }

        return null;
    }

    /**
     * @return array|null
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getAirlineByIATA($airlineName)
    {
        $connection = $this->connection;

        $sql = 'SELECT a.* FROM Airline a WHERE Code = ?';
        $stmt = $connection->executeQuery($sql, [$airlineName], [\PDO::PARAM_STR]);

        $airline = $stmt->fetch();
        $stmt->closeCursor();

        if (!empty($airline)) {
            return $airline;
        }

        return null;
    }

    /**
     * @return array|null
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getAirlineByICAO($airlineName)
    {
        $connection = $this->connection;

        $sql = 'SELECT a.* FROM Airline a WHERE ICAO = ?';
        $stmt = $connection->executeQuery($sql, [$airlineName], [\PDO::PARAM_STR]);

        $airline = $stmt->fetch();
        $stmt->closeCursor();

        if (!empty($airline)) {
            return $airline;
        }

        return null;
    }
}
