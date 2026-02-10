<?php

namespace AwardWallet\MainBundle\Service\RA;

use AwardWallet\MainBundle\Controller\AwardPriceController;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class AwardPriceService
{
    private const AWP_AIRPORTS_BY_ID_PREFIX = 'awp_airports_by_id_';

    private Connection $connection;

    private LoggerInterface $logger;

    private \Memcached $memcached;
    private string $baseTable;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        \Memcached $memcached,
        $baseTable = 'RAFlight'
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->memcached = $memcached;
        $this->baseTable = $baseTable;
    }

    public function getAwardPriceCsv(array $params, string $fileName): ?string
    {
        // for debug
        $this->logger->info('getAwardPriceData', $params);
        $rows = $this->getAwardPriceData($params);

        if (empty($rows)) {
            return null;
        }

        $fileName = '/tmp/' . $fileName;
        // for debug
        $this->logger->info('export to csv');

        if (file_exists($fileName)) {
            @unlink($fileName);
        }
        $csv = fopen($fileName, 'x');
        fputcsv($csv, array_keys($rows[0]));

        try {
            foreach ($rows as $row) {
                fputcsv($csv, $row);
            }
        } finally {
            fclose($csv);
        }

        return $fileName;
    }

    public function getAwardPriceSubData($params, $extendedData): array
    {
        // format dates
        if (isset($params['searchDate1'])) {
            $params['searchDate1'] = date('Y-m-d 00:00:00', strtotime($params['searchDate1']));
        }

        if (isset($params['searchDate2'])) {
            $params['searchDate2'] = date('Y-m-d 23:59:59', strtotime($params['searchDate2']));
        }

        if (isset($params['travelDate1'])) {
            $params['travelDate1'] = date('Y-m-d 00:00:00', strtotime($params['travelDate1']));
        }

        if (isset($params['travelDate2'])) {
            $params['travelDate2'] = date('Y-m-d 23:59:59', strtotime($params['travelDate2']));
        }
        $ignoreProvider = isset($params['ignoreProvider']);
        $ignoreCabinType = isset($params['ignoreCabinType']);
        $ignoreFlightType = isset($params['ignoreFlightType']);

        $builder = $this->connection->createQueryBuilder();
        $select =
            "    
            Provider,
            StandardItineraryCOS,
            FlightType,
            MileCost,
            count(*) AS CNT, 
            Min(DaysBeforeDeparture) AS MINd, 
            Avg(DaysBeforeDeparture) AS AVGd, 
            Max(DaysBeforeDeparture) as MAXd 
            ";

        $builder
            ->select($select)
            ->from($this->baseTable)
            ->where("IsMixedCabin = 0 AND IsCheapest = 1");

        $listNoNeedFormatParams = [];

        if (!$ignoreProvider) {
            $builder->andwhere('Provider = :providerCode');
            $listNoNeedFormatParams += ['providerCode' => true];
        }

        if (!$ignoreCabinType) {
            $builder->andWhere('StandardItineraryCOS = :cabinType');
            $listNoNeedFormatParams += ['cabinType' => true];
        }

        if (!$ignoreFlightType) {
            $builder->andWhere('FlightType = :flightType');
            $listNoNeedFormatParams += ['flightType' => true];
        }
        $listNoNeedFormatParams += [
            'daysBeforeDepMin' => true,
            'daysBeforeDepMax' => true,
            'searchDate1' => true,
            'searchDate2' => true,
            'travelDate1' => true,
            'travelDate2' => true,
        ];
        $queryParams = array_intersect_key($params, $listNoNeedFormatParams);

        // Airports
        foreach (['orig' => 'FromAirport', 'dest' => 'ToAirport'] as $type => $field) {
            $listAirports = $this->getAirportsByRegion($params[$type . 'Id']);

            if (empty($listAirports)) {
                $builder->andWhere('FALSE');
            } else {
                $builder->andWhere($builder->expr()->in($field, ':' . $field));
                $builder->setParameter(":" . $field, $listAirports, Connection::PARAM_STR_ARRAY);
            }
        }

        // SearchDate
        if (isset($params['searchDate1'], $params['searchDate2'])) {
            $builder->andWhere("SearchDate BETWEEN :searchDate1 AND :searchDate2");
        } elseif (isset($params['searchDate1'])) {
            $builder->andWhere("SearchDate >= :searchDate1");
        } elseif (isset($params['searchDate2'])) {
            $builder->andWhere("SearchDate <= :searchDate2");
        }

        // DepartureDate
        if (isset($params['travelDate1'], $params['travelDate2'])) {
            $builder->andWhere("DepartureDate BETWEEN :travelDate1 AND :travelDate2");
        } elseif (isset($params['travelDate1'])) {
            $builder->andWhere("DepartureDate >= :travelDate1");
        } elseif (isset($params['travelDate2'])) {
            $builder->andWhere("DepartureDate <= :travelDate2");
        }

        // DaysBeforeDeparture
        if (isset($params['daysBeforeDepMin'], $params['daysBeforeDepMax'])) {
            $builder->andWhere("DaysBeforeDeparture BETWEEN :daysBeforeDepMin AND :daysBeforeDepMax");
        } elseif (isset($params['daysBeforeDepMin'])) {
            $builder->andWhere("DaysBeforeDeparture >= :daysBeforeDepMin");
        } elseif (isset($params['daysBeforeDepMax'])) {
            $builder->andWhere("DaysBeforeDeparture <= :daysBeforeDepMax");
        }

        $builder
            ->groupBy('Provider')
            ->addGroupBy('FlightType')
            ->addGroupBy('StandardItineraryCOS')
            ->addGroupBy('MileCost')
            ->orderBy('Provider')
            ->addOrderBy('FlightType')
            ->addOrderBy('StandardItineraryCOS')
            ->addOrderBy('MileCost');

        foreach ($queryParams as $key => $value) {
            $builder->setParameter($key, $value);
        }

        $data = $builder->execute()->fetchAllAssociative();

        if (empty($data)) {
            return [];
        }
        $result = [];

        $newData = [];

        foreach ($data as $row) {
            $newData[sprintf('%s-%s-%s', $row['Provider'], $row['FlightType'], $row['StandardItineraryCOS'])][] = $row;
        }

        foreach ($newData as $key => $data) {
            $list = explode('-', $key);
            $provider = $list[0];
            $flightType = $list[1];
            $cabinType = $list[2];
            $result = array_merge(
                $result,
                $this->getPreResult($data, $provider, $flightType, $cabinType, $extendedData)
            );
        }

        return $result;
    }

    public function getAirportsByRegion(int $regionId): array
    {
        $result = $this->memcached->get(self::AWP_AIRPORTS_BY_ID_PREFIX . $regionId);

        if (is_array($result)) {
            return $result;
        }

        $result = [];

        $countryRegions = $stateRegions = $airportRegions = $newIncludeIDs = [];

        switch ($this->getKindOfRegion($regionId)) {
            case REGION_KIND_COUNTRY:
                $countryRegions[] = $regionId;

                break;

            case REGION_KIND_STATE:
                $stateRegions[] = $regionId;

                break;

            case REGION_KIND_AIRPORT:
                $airportRegions[] = $regionId;

                break;
        }

        $includeIDs = $this->getIncludeRegions($regionId);
        $counter = 0;

        while (!empty($includeIDs) && $counter < 4) {
            foreach ($includeIDs as $includeID) {
                switch ($this->getKindOfRegion($includeID)) {
                    case REGION_KIND_COUNTRY:
                        $countryRegions[] = $includeID;

                        break;

                    case REGION_KIND_STATE:
                        $stateRegions[] = $includeID;

                        break;

                    case REGION_KIND_AIRPORT:
                        $airportRegions[] = $includeID;

                        break;
                }

                if (empty($children = $this->getIncludeRegions($includeID))) {
                    continue;
                }
                $newIncludeIDs += $children;
            }
            $counter++;
            $includeIDs = array_unique($newIncludeIDs);
        }

        if ($counter === 4) {
            $this->logger->warning('Too deep nesting of regions. Check starting from ' . $regionId);
        }
        $countryRegions = array_unique($countryRegions);
        $stateRegions = array_unique($stateRegions);
        $airportRegions = array_unique($airportRegions);

        // airports included in the countries included in the region
        $countries = $this->connection->executeQuery(/** @lang MySQL */ "SELECT CountryID FROM Region WHERE RegionID IN (?)",
            [$countryRegions], [Connection::PARAM_INT_ARRAY])->fetchFirstColumn();
        $sql = $this->getQueryCodeByCountry();

        foreach ($countries as $country) {
            $addAirports = $this->connection->executeQuery($sql, [$country, $country])->fetchFirstColumn();
            $result = array_merge($result, $addAirports);
        }

        // airports included in the states included in the region
        $states = $this->connection->executeQuery(/** @lang MySQL */ "SELECT StateID FROM Region WHERE RegionID IN (?)",
            [$stateRegions], [Connection::PARAM_INT_ARRAY])->fetchFirstColumn();
        $sql = $this->getQueryCodeByState();

        foreach ($states as $state) {
            $addAirports = $this->connection->executeQuery($sql, [$state, $state])->fetchFirstColumn();
            $result = array_merge($result, $addAirports);
        }

        // airports included in the region

        $addAirports = $this->connection->executeQuery(/** @lang MySQL */ "SELECT AirCode FROM Region WHERE RegionID IN (?)",
            [$airportRegions], [Connection::PARAM_INT_ARRAY])->fetchFirstColumn();
        $result = array_merge($result, $addAirports);

        $result = array_unique($result);
        $this->memcached->set(self::AWP_AIRPORTS_BY_ID_PREFIX . $regionId, $result, 60 * 60 * 2);

        return $result;
    }

    public function getQueryCodeByCountry(): string
    {
        return /** @lang MySQL */ "
            SELECT DISTINCT ac.AirCode AS Code FROM AirCode ac
	            INNER JOIN Country c ON (ac.CountryCode = c.Code)
	            WHERE  c.CountryID = ?
        UNION
            SELECT DISTINCT ac.StationCode AS Code FROM StationCode ac
	            INNER JOIN Country c ON (ac.CountryCode = c.Code)
	            WHERE  c.CountryID = ?
	            ";
    }

    public function getQueryCodeByState(): string
    {
        return /** @lang MySQL */ "
            SELECT DISTINCT ac.AirCode AS Code FROM AirCode ac
	            INNER JOIN State s ON (ac.State = s.Code AND ac.StateName=s.Name)
	            WHERE  s.StateID = ?
        UNION
            SELECT DISTINCT ac.StationCode AS Code FROM StationCode ac
	            INNER JOIN State s ON (ac.State = s.Code AND ac.StateName=s.Name)
	            WHERE  s.StateID = ?
	            ";
    }

    public function getIncludeRegions($regionId): array
    {
        return $this->connection->executeQuery(/** @lang MySQL */ 'SELECT SubRegionID FROM RegionContent WHERE RegionID = ? AND Exclude = 0',
            [$regionId])->fetchFirstColumn();
    }

    public function getRegionName($ID): ?string
    {
        $row = $this->connection->executeQuery(/** @lang MySQL */ "
            SELECT
                COALESCE(r.Name, rco.Name, rs.Name, CONCAT(r.AirCode, ', ', ac.AirName)) as Name
		    FROM
			    Region r
                LEFT JOIN Country rco ON rco.CountryID = r.CountryID
                LEFT JOIN State rs ON rs.StateID = r.StateID
                LEFT JOIN AirCode ac ON r.AirCode = ac.AirCode
		    WHERE RegionID = ?
		;",
            [$ID])->fetchOne();

        if (false === $row) {
            return null;
        }

        return $row;
    }

    public function getAwardPriceData(array $params): array
    {
        $result = [];
        $regions = [];

        $header = [];

        foreach (['orig', 'dest'] as $type) {
            $key = $type . 'IncChild';
            $includeChildren = isset($params[$key]);

            $index = $params[$type . 'Type'];

            if (!array_key_exists($index, AwardPriceController::AWARD_PRICE_REGIONS)) {
                break;
            }
            $paramName = $type . str_replace(' ', '', AwardPriceController::AWARD_PRICE_REGIONS[$index]);

            if (!isset($params[$paramName])) {
                break;
            }

            if ($includeChildren) {
                $regions[$type] = $this->getIncludeRegions($params[$paramName]);

                if (empty($regions[$type])) {
                    $regions[$type] = [$params[$paramName]];
                }
            } else {
                $regions[$type] = [$params[$paramName]];
            }
            $header[$type] = $params[$paramName];
        }

        if (count($header) !== 2) {
            // for debug
            $this->logger->info('maybe error with header', $params);

            return [];
        }
        $from = $this->getRegionName($header['orig']);
        $to = $this->getRegionName($header['dest']);

        if (count($regions['orig']) === 1) {
            $fromChild = null;
        }

        if (count($regions['dest']) === 1) {
            $toChild = null;
        }

        foreach ($regions['orig'] as $origRegion) {
            if (count($regions['orig']) !== 1) {
                $fromChild = $this->getRegionName($origRegion);
            }

            foreach ($regions['dest'] as $destRegion) {
                if (count($regions['dest']) !== 1) {
                    $toChild = $this->getRegionName($destRegion);
                }
                $params['origId'] = $origRegion;
                $params['destId'] = $destRegion;
                // for debug
                $this->logger->info('getAwardPriceSubData',
                    ['From' => $from, 'FromChild' => $fromChild, 'To' => $to, 'ToChild' => $toChild]);
                $rows = $this->getAwardPriceSubData($params,
                    ['From' => $from, 'FromChild' => $fromChild, 'To' => $to, 'ToChild' => $toChild]);

                if (!empty($rows)) {
                    // add separate (empty row)
                    $keys = array_keys($rows[0]);
                    $values = array_fill(0, count($keys), null);
                    $rows[] = array_combine($keys, $values);
                    $result = array_merge($result, $rows);
                }
            }
        }

        return $result;
    }

    private function getPreResult(array $data, $provider, $flightType, $cabinType, $extendedData): array
    {
        $total = array_sum(array_map(function ($s) {
            return $s['CNT'];
        }, $data));
        $percentile = 0.0;
        $result = [];

        foreach ($data as $row) {
            $percent = $row['CNT'] / $total * 100;
            $percentile += $percent;
            $result[] = [
                'Provider' => $provider,
                'FlightType' => RAFlightSchema::FLIGHT_TYPES[$flightType] ?? $flightType,
                'CabinType' => $cabinType,
                'MileCost' => $row['MileCost'],
                'CountID' => $row['CNT'],
                'MINdaysBeforDeparture' => $row['MINd'],
                'AVGdaysBeforDeparture' => $row['AVGd'],
                'MAXdaysBeforDeparture' => $row['MAXd'],
                'CountIDpercent' => $percent,
                'Percentile' => $percentile,
            ] + $extendedData;
        }

        return $result;
    }

    private function getKindOfRegion($ID)
    {
        $row = $this->connection->executeQuery(/** @lang MySQL */ "
            SELECT Kind FROM Region WHERE RegionID = ?
		;",
            [$ID])->fetchOne();

        if (false === $row) {
            return null;
        }

        return $row;
    }
}
