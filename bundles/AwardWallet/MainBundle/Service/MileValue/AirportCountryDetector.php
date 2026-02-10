<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;

class AirportCountryDetector
{
    private $cache = [];

    private Statement $cityQuery;

    private Statement $airportQuery;

    public function __construct(Connection $connection)
    {
        $this->cityQuery = $connection->prepare("select CountryCode from AirCode where CityCode = ?");
        $this->airportQuery = $connection->prepare("select CountryCode from AirCode where AirCode = ?");
    }

    /**
     * @return string - iata country code
     */
    public function findAirportCountry(string $cityOrAirportCode): string
    {
        $result = $this->cache[$cityOrAirportCode] ?? null;

        if ($result !== null) {
            return $result;
        }

        foreach ([$this->cityQuery, $this->airportQuery] as $query) {
            $query->execute([$cityOrAirportCode]);
            $result = $query->fetchColumn();

            if ($result !== false) {
                break;
            }
        }

        if ($result === false) {
            throw new \Exception("country not found by code: {$cityOrAirportCode}");
        }

        $this->cache[$cityOrAirportCode] = $result;

        return $result;
    }
}
