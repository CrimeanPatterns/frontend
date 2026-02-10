<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Globals\Geo;
use Doctrine\ORM\EntityRepository;

class StationcodeRepository extends EntityRepository
{
    /**
     * @var array
     */
    private $lookupStationStationCache = [];
    private $extractStationStationCache = [];

    public function lookupStationStation($stationCode)
    {
        if (array_key_exists($stationCode, $this->lookupStationStationCache)) {
            return $this->lookupStationStationCache[$stationCode];
        }
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT *, TimeZoneLocation AS TimeZoneName
            FROM StationCode
            WHERE StationCode = ?
        ";
        $station = $connection->executeQuery($sql, [$stationCode], [\PDO::PARAM_STR])->fetch(\PDO::FETCH_ASSOC);

        if (empty($station)) {
            return $this->lookupStationStationCache[$stationCode] = null;
        }
        $stationName = $this->buildStationStationName($station);
        $station['Name'] = $stationName;

        return $this->lookupStationStationCache[$stationCode] = $station;
    }

    public function buildStationStationName($stationData)
    {
        $airportData['Name'] = $stationData['CityCode'] . " (" . ucwords(strtolower($stationData['StationName'])) . ")";

        if ($airportData['CountryCode'] != 'US') {
            $airportData['Name'] .= ", " . ucwords(strtolower($stationData['CountryCode']));
        }
    }

    /**
     * extracts possible station code from name.
     *
     * @return mixed - row from StationCode table or null
     */
    public function extractStationStation($name, $lat, $lng)
    {
        if (array_key_exists($compositeKey = $name . $lat . $lng, $this->extractStationStationCache)) {
            return $this->extractStationStationCache[$compositeKey];
        }

        if (preg_match("/\b([A-Za-z]{3})\b/", $name, $matches)) {
            $station = $this->lookupStationStation($name);

            if ($station) {
                $distance = Distance($lat, $lng, $station['Lat'], $station['Lng']);

                if ($distance <= 30) {
                    return $this->extractStationStationCache[$compositeKey] = $station;
                }
            }
        }

        return $this->extractStationStationCache[$compositeKey] = null;
    }

    /**
     * Find nearest station that belongs to square with center in geotag lat, lng.
     *
     * @param float $square miles
     * @return string|null
     */
    public function getNearestStationStationCode(Geotag $geotag, $square = 4.0)
    {
        [$conditions, $paramsList] = Geo::getSquareGeofenceSQLCondition(
            $geotag->getLat(),
            $geotag->getLng(),
            'a.Lat',
            'a.Lng',
            true,
            $square
        );

        $values = [];
        $types = [];

        foreach ($paramsList as [$name, $value, $type]) {
            $values[$name] = $value;
            $types[$name] = $type;
        }

        $stmt = $this->getEntityManager()->getConnection()->executeQuery(
            "
            SELECT 
                StationCode 
            FROM StationCode a
            WHERE
                {$conditions}
            LIMIT 1;",
            $values,
            $types
        );

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['StationCode'];
        }

        return null;
    }

    /**
     * @return array
     */
    public function findStationcodeByQuery(string $query, int $limit = 10)
    {
        $foundByCodes = $this->createQueryBuilder('stationcode')
            ->orWhere('stationcode.stationcode = :query')
            ->orWhere('stationcode.icaoCode = :query')
            ->setParameter(':query', $query)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        $foundByLike = $this->createQueryBuilder('stationcode')
            ->orWhere('stationcode.stationcode LIKE :query')
            ->orWhere('stationcode.icaoCode LIKE :query')
            ->orWhere('stationcode.stationname LIKE :query')
            ->orWhere('stationcode.citycode LIKE :query')
            ->setParameter(':query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        $mergedStationStations = $foundByCodes;

        foreach ($foundByLike as $station) {
            if (!in_array($station, $mergedStationStations)) {
                $mergedStationStations[] = $station;
            }
        }
        array_slice($mergedStationStations, 0, $limit);

        return $mergedStationStations;
    }

    public function findAll()
    {
        return $this->findBy([], ['stationcode' => 'ASC']);
    }
}
