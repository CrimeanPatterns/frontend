<?php

namespace AwardWallet\MainBundle\Service\FlightSearch;

use AwardWallet\MainBundle\Entity\Repositories\RegionRepository;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\FlightSearch\Place\PlaceFactory;
use AwardWallet\MainBundle\Service\FlightSearch\Place\PlaceItem;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PlaceQuery
{
    public const TYPE_AIRPORT = 1;
    public const TYPE_CITY = 2;
    public const TYPE_STATE = 3;
    public const TYPE_COUNTRY = 4;
    public const TYPE_REGION = 5;

    public const EXCLUDE_REGION_ID = [10278];

    public const CACHE_KEY = 'SearchPlace_v0';
    public const CACHE_LIFETIME = 1;
    private const LIMIT_RESULT = 10;

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private CacheManager $cacheManager;
    private PlaceFactory $placeFactory;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CacheManager $cacheManager,
        PlaceFactory $placeFactory
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->cacheManager = $cacheManager;
        $this->placeFactory = $placeFactory;
    }

    public function byAll(string $query): array
    {
        if (empty($query) || strlen($query) < 2) {
            return [];
        }

        $founds = array_merge(
            $this->byAirport($query),
            $this->byCity($query),
            $this->byState($query),
            $this->byCountry($query),
            $this->byRegion($query)
        );

        $result = [];

        foreach ($founds as $found) {
            $result[] = [
                'id' => $found->getId(),
                'code' => $found->getCode(),
                'name' => html_entity_decode($found->getName()),
                'info' => html_entity_decode($found->getInfo()),
                'type' => $found->getType(),
                'value' => html_entity_decode($found->getValue()),
            ];
        }

        foreach ($result as $pos => $item) {
            if (strtolower($query) === strtolower($item['code'])) {
                $movItem = $item;
                unset($result[$pos]);
                array_unshift($result, $movItem);
            }
        }

        foreach ($result as $pos => $item) {
            if (strtolower($query) === strtolower($item['code']) && self::TYPE_AIRPORT === $item['type']) {
                $movItem = $item;
                unset($result[$pos]);
                array_unshift($result, $movItem);
            }
        }

        return array_values($result);
    }

    /**
     * @return PlaceItem[]
     */
    public function byAirport(string $query, int $limit = self::LIMIT_RESULT): array
    {
        $aircodes = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    AirCodeID, AirCode, CityCode, CityName, CountryCode, CountryName, State, StateName, AirName
            FROM AirCode
            WHERE
                    AirCode = :query
                OR  IcaoCode = :query
            LIMIT ' . $limit,
            ['query' => $query],
            ['query' => \PDO::PARAM_STR]
        );

        $result = [];

        foreach ($aircodes as $airport) {
            $result[] = $this->placeFactory->getPlaceItem(self::TYPE_AIRPORT, $airport);
        }

        return $result;
    }

    /**
     * @return PlaceItem[]
     */
    public function byCity(string $query, ?string $countryCode = '', int $limit = self::LIMIT_RESULT): array
    {
        $aircodes = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    -- DISTINCT CONCAT(CountryCode, "-", State, "-", CityName) AS _place,
                    MIN(AirCodeID) AS AirCodeID, MAX(CityCode) AS CityCode,
                    CountryCode, CountryName, State, StateName, CityName
            FROM AirCode
            WHERE (
                       CityCode LIKE :code
                    OR CityCode LIKE :query 
                    OR CityName LIKE :query
                )
                ' . (empty($countryCode) ? '' : 'AND CountryCode LIKE ' . $this->entityManager->getConnection()->quote($countryCode)) . '
            GROUP BY -- _place,
                    CountryCode, CountryName, State, StateName, CityCode, CityName
            ORDER BY
                    CASE
                        WHEN CityCode = :code THEN 0
                        WHEN CityName = :code THEN 1
                        WHEN CityName LIKE :queryStart THEN 2
                        ELSE 3
                    END
                ASC
            LIMIT ' . $limit,
            [
                'code' => $query,
                'query' => '%' . $query . '%',
                'queryStart' => $query . '%',
            ],
            [
                'code' => \PDO::PARAM_STR,
                'query' => \PDO::PARAM_STR,
                'queryStart' => \PDO::PARAM_STR,
            ]
        );

        $result = [];

        foreach ($aircodes as $aircode) {
            $result[] = $this->placeFactory->getPlaceItem(self::TYPE_CITY, $aircode);
        }

        return $result;
    }

    /**
     * @return PlaceItem[]
     */
    public function byState(string $query, int $limit = self::LIMIT_RESULT): array
    {
        $states = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    s.StateID, s.Code, s.Name AS StateName,
                    c.Code AS CountryCode, c.Name AS CountryName
            FROM State s
            JOIN Country c ON s.CountryID = c.CountryID
            WHERE
                    s.AreaID IN (1, 40)
                AND (
                       s.Code LIKE :code
                    OR s.Name LIKE :query
                )
            ORDER BY CASE WHEN s.Code LIKE :code THEN 0 ELSE 1 END ASC
            LIMIT ' . $limit,
            [
                'code' => $query,
                'query' => '%' . $query . '%',
            ],
            [
                'code' => \PDO::PARAM_STR,
                'query' => \PDO::PARAM_STR,
            ]
        );

        $result = [];

        foreach ($states as $state) {
            $result[] = $this->placeFactory->getPlaceItem(self::TYPE_STATE, $state);
        }

        return $result;
    }

    /**
     * @return PlaceItem[]
     */
    public function byCountry(string $query, int $limit = self::LIMIT_RESULT): array
    {
        $countrys = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    c.CountryID, c.Name, c.Code
            FROM Country c
            WHERE
                   c.Code LIKE :code
                OR c.Code LIKE :query
                OR (c.Name LIKE :query AND c.Code IS NOT NULL)
            ORDER BY CASE WHEN c.Code LIKE :code THEN 0 ELSE 1 END ASC
            LIMIT ' . $limit,
            [
                'code' => $query,
                'query' => '%' . $query . '%',
            ],
            [
                'code' => \PDO::PARAM_STR,
                'query' => \PDO::PARAM_STR,
            ]
        );

        $result = [];

        foreach ($countrys as $country) {
            $result[] = $this->placeFactory->getPlaceItem(self::TYPE_COUNTRY, $country);
        }

        return $result;
    }

    /**
     * @return PlaceItem[]
     */
    public function byRegion(string $query, int $limit = self::LIMIT_RESULT): array
    {
        $regions = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    r.RegionID, r.Name
            FROM Region r
            WHERE
                    r.Kind IN (' . RegionRepository::REGION_KIND_REGION . ', ' . RegionRepository::REGION_KIND_CONTINENT . ')  
                AND r.Name LIKE :query
                AND r.RegionID NOT IN (:excludeIds)
            LIMIT ' . $limit,
            [
                'query' => '%' . $query . '%',
                'excludeIds' => self::EXCLUDE_REGION_ID,
            ],
            [
                'query' => \PDO::PARAM_STR,
                'excludeIds' => Connection::PARAM_INT_ARRAY,
            ]
        );

        $result = [];

        foreach ($regions as $region) {
            $result[] = $this->placeFactory->getPlaceItem(self::TYPE_REGION, $region);
        }

        return $result;
    }

    /**
     * @return PlaceItem[]
     */
    public function getRegionByCountryId(int $countryId, int $limit = self::LIMIT_RESULT): array
    {
        $regions = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT r.RegionID, r.Name
            FROM RegionContent rc
            JOIN Region r ON rc.RegionID = r.RegionID
            WHERE
                    rc.SubRegionID IN (
                        SELECT RegionID FROM Region WHERE CountryID = ? AND Kind = ?
                    )
                AND r.Kind IN (' . RegionRepository::REGION_KIND_REGION . ', ' . RegionRepository::REGION_KIND_CONTINENT . ')
            LIMIT ' . $limit,
            [$countryId, RegionRepository::REGION_KIND_COUNTRY],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        $result = [];

        foreach ($regions as $region) {
            $result[] = $this->placeFactory->getPlaceItem(self::TYPE_REGION, $region);
        }

        return $result;
    }

    public function byGoogleAddress(string $query): array
    {
    }
}
