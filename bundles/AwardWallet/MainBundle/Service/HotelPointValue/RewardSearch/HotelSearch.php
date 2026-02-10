<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch;

use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use Doctrine\DBAL\Connection;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

class HotelSearch implements TranslationContainerInterface
{
    public const CACHE_KEY = 'HotelRewardSearch_v2';
    public const CACHE_LIFETIME = 1; // todo increase cache

    public const HOTEL_SEARCH_LIMIT = 30;

    // https://awardwallet.com/manager/list.php?Schema=Region&Kind=7
    public const AVAILABLE_CONTINENT = [
        30 => 'Africa',
        32 => 'Asia',
        10531 => 'Australia and the Pacific',
        22 => 'Europe',
        8 => 'North America',
        21 => 'South America',
    ];

    // https://awardwallet.com/manager/list.php?Schema=Region&Kind=1
    public const AVAILABLE_REGION = [
        10280 => 'Alaska & Canada',
        10269 => 'Australia &amp; New Zealand',
        10702 => 'Azores Islands (Portugal)',
        10696 => 'Balearic Islands (Spain)',
        10697 => 'Canary Islands (Spain)',
        10267 => 'Caribbean',
        10266 => 'Central & Southern Africa',
        10272 => 'Central America',
        10262 => 'Central Asia',
        10692 => 'Galapagos Islands (Ecuador)',
        10704 => 'Madeira Islands (Portugal)',
        10270 => 'Middle East',
        10273 => 'North Asia',
        10265 => 'Northern Africa',
        10274 => 'Northern South America',
        39 => 'Oceania',
        10271 => 'South Asia',
        10268 => 'Southern South America',
        // 10278 => 'USA (Continental 48)',
    ];

    private const DISABLED_HOTEL_ID = [9223, 13207];

    private HotelHandler $hotelHandler;
    private Connection $connection;
    private CacheManager $cacheManager;

    private string $hotelFragmentName = '';

    public function __construct(
        HotelHandler $hotelHandler,
        Connection $connection,
        CacheManager $cacheManager
    ) {
        $this->hotelHandler = $hotelHandler;
        $this->connection = $connection;
        $this->cacheManager = $cacheManager;
    }

    /**
     * @return HotelItem[]
     * @throws \Exception
     */
    public function getByPlace(PlaceParserResult $parserResult, string $fragmentName = ''): array
    {
        $this->hotelFragmentName = $fragmentName;
        $placeId = $parserResult->getPlaceId();

        $cacheRef = new CacheItemReference(self::CACHE_KEY . $placeId . $fragmentName, [],
            function () use ($parserResult) {
                return $this->searchByPlace($parserResult);
            });
        $cacheRef->setExpiration(self::CACHE_LIFETIME);

        return $this->cacheManager->load($cacheRef);
    }

    /**
     * @return HotelItem[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function searchByPlace(PlaceParserResult $parserResult): array
    {
        $countryCode = $parserResult->getCountryCode();
        $stateCode = $parserResult->getStateCode();
        $city = $parserResult->getCity();
        $address = $parserResult->getAddress();

        if (!empty($address)) {
            return $this->searchByCoordinates($parserResult->getLat(), $parserResult->getLng(), 50);
        }

        if (!empty($city)) {
            $hotels = $this->searchByCity($countryCode, $stateCode, $city);

            return empty($hotels) ? $this->searchByCity($countryCode, null, $city) : $hotels;
        }

        if (!empty($stateCode)) {
            $hotels = $this->searchByState($countryCode, $stateCode);

            return empty($hotels) ? $this->searchByCity($countryCode, null, $stateCode) : $hotels;
        }

        if (!empty($countryCode)) {
            return $this->searchByCountry($countryCode);
        }

        throw new \RuntimeException('Unknown SearchType');
    }

    /**
     * @return HotelItem[]
     * @throws \Exception
     */
    public function searchByContinentId(array $continentId): ?array
    {
        $cacheKeyExt = 'continents_' . sha1(implode('-', $continentId));
        $cacheRef = new CacheItemReference(self::CACHE_KEY . $cacheKeyExt, [], function () use ($continentId) {
            $subRegionsId = $this->connection->fetchFirstColumn(
                'SELECT SubRegionID FROM RegionContent WHERE RegionID IN (?) AND Exclude = 0',
                [$continentId],
                [$this->connection::PARAM_INT_ARRAY]
            );

            if (empty($subRegionsId)) {
                return null;
            }

            $countryCodes = $this->connection->fetchFirstColumn('
                    SELECT c.Code
                    FROM Region r
                    JOIN Country c ON (r.CountryID = c.CountryID)
                    WHERE
                        r.RegionID IN (?)',
                [$subRegionsId],
                [$this->connection::PARAM_INT_ARRAY]
            );

            if (empty($countryCodes)) {
                return null;
            }

            $hotels = $this->connection->fetchAll('
                    SELECT
                            h.HotelID, h.ProviderID, h.HotelBrandID, h.Name, h.Address, h.PointValue, h.CashPrice, h.PointPrice, h.GeoTagID, h.MatchCount, h.Website,
                            ' . $this->hotelHandler->getSqlAvgPontValueCaseCondition() . ' AS avgAboveValue,
                            gt.CountryCode, gt.StateCode, gt.Country, gt.State, gt.City,
                            p.ShortName AS ProviderShortName
                    FROM Hotel h
                    JOIN GeoTag gt ON (h.GeoTagID = gt.GeoTagID)
                    JOIN Provider p ON (p.ProviderID = h.ProviderID)
                    WHERE
                            gt.CountryCode IN (:countryCode)
                            ' . $this->getExtraCondition() . '
                    ORDER BY avgAboveValue DESC, h.PointValue DESC
                    LIMIT :hotelLimit',
                ['countryCode' => $countryCodes, 'hotelLimit' => self::HOTEL_SEARCH_LIMIT],
                ['countryCode' => $this->connection::PARAM_STR_ARRAY, 'hotelLimit' => \PDO::PARAM_INT]
            );

            return $this->hotelHandler->formatHotels($hotels);
        });
        $cacheRef->setExpiration(self::CACHE_LIFETIME);

        return $this->cacheManager->load($cacheRef);
    }

    /**
     * @return HotelItem[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function searchByRegionId(int $regionId): ?array
    {
        $subRegionsId = $this->connection->fetchFirstColumn(
            'SELECT SubRegionID FROM RegionContent WHERE RegionID = ? AND Exclude = 0',
            [$regionId], [\PDO::PARAM_INT]
        );

        if (empty($subRegionsId)) {
            return null;
        }

        return $this->searchByContinentId($subRegionsId);
    }

    /*
    private function getHotelsQuery(): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from('Hotel', 'h')
            ->join('GeoTagID', 'GeoTag', 'WITH')
            ->orderBy('h.PointValue', 'DESC')
            ->setMaxResults(self::HOTEL_SEARCH_LIMIT);

        return $qb;
    }
    */

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('best-hotel-reward-search'))->setDesc('Best Value Hotel Reward Search'),
            (new Message('award-hotel-research-tool'))->setDesc('Award Hotel Research Tool'),
            (new Message('redemption-value'))->setDesc('Redemption Value'),
            (new Message('percent-above-avg'))->setDesc('% Above Average'),
            (new Message('avg-cash-price-night'))->setDesc('Avg Cash Price per Night'),
            (new Message('avg-point-price-night'))->setDesc('Avg Point Price per Night'),
            (new Message('check-availability'))->setDesc('Check Availability'),
            (new Message('per-point'))->setDesc('per point'),
            (new Message('we-calculate-points-evaluating-bookings-points'))->setDesc('We calculate the value of points by evaluating thousands of bookings that our users make with points. You can see the complete list of point and mile values %link_on%here%link_off%.'),
            (new Message('enter-city-state-country-search'))->setDesc('Enter city, state our country to search'),
            (new Message('search-result-near'))->setDesc('Search result near %query%'),
        ];
    }

    private function getExtraCondition(): string
    {
        $sql = ' AND h.HotelID NOT IN (' . implode(',', self::DISABLED_HOTEL_ID) . ')';

        if (empty($this->hotelFragmentName)) {
            return $sql;
        }

        return $sql . ' AND h.Name LIKE ' . $this->connection->quote('%' . $this->hotelFragmentName . '%');
    }

    /**
     * @return HotelItem[]
     * @throws \Exception
     */
    private function searchByCountry(string $countryCode): array
    {
        $hotels = $this->connection->fetchAll('
            SELECT
                    h.HotelID, h.ProviderID, h.HotelBrandID, h.Name, h.Address, h.PointValue, h.CashPrice, h.PointPrice, h.GeoTagID, h.MatchCount, h.Website,
                    ' . $this->hotelHandler->getSqlAvgPontValueCaseCondition() . ' AS avgAboveValue,
                    gt.CountryCode, gt.StateCode, gt.Country, gt.State, gt.City,
                    p.ShortName AS ProviderShortName
            FROM Hotel h
            JOIN GeoTag gt ON (h.GeoTagID = gt.GeoTagID)
            JOIN Provider p ON (p.ProviderID = h.ProviderID)
            WHERE
                    gt.CountryCode LIKE :countryCode
                    ' . $this->getExtraCondition() . '
            ORDER BY avgAboveValue DESC, h.PointValue DESC
            LIMIT :hotelLimit',
            ['countryCode' => $countryCode, 'hotelLimit' => self::HOTEL_SEARCH_LIMIT],
            ['countryCode' => \PDO::PARAM_STR, 'hotelLimit' => \PDO::PARAM_INT]
        );

        return $this->hotelHandler->formatHotels($hotels);
    }

    /**
     * @return HotelItem[]
     * @throws \Exception
     */
    private function searchByState(string $countryCode, string $stateCode): array
    {
        $hotels = $this->connection->fetchAll('
            SELECT
                    h.HotelID, h.ProviderID, h.HotelBrandID, h.Name, h.Address, h.PointValue, h.CashPrice, h.PointPrice, h.GeoTagID, h.MatchCount, h.Website,
                    ' . $this->hotelHandler->getSqlAvgPontValueCaseCondition() . ' AS avgAboveValue,
                    gt.CountryCode, gt.StateCode, gt.Country, gt.State, gt.City,
                    p.ShortName AS ProviderShortName
            FROM Hotel h
            JOIN GeoTag gt ON (h.GeoTagID = gt.GeoTagID)
            JOIN Provider p ON (p.ProviderID = h.ProviderID)
            WHERE
                    gt.StateCode LIKE :stateCode
                AND gt.CountryCode LIKE :countryCode
                ' . $this->getExtraCondition() . '
            ORDER BY avgAboveValue DESC, h.PointValue DESC
            LIMIT :hotelLimit',
            ['stateCode' => $stateCode, 'countryCode' => $countryCode, 'hotelLimit' => self::HOTEL_SEARCH_LIMIT],
            ['stateCode' => \PDO::PARAM_STR, 'countryCode' => \PDO::PARAM_STR, 'hotelLimit' => \PDO::PARAM_INT]
        );

        return $this->hotelHandler->formatHotels($hotels);
    }

    /**
     * @return HotelItem[]
     * @throws \Exception
     */
    private function searchByCity(?string $countryCode, ?string $stateCode, string $city): array
    {
        $where = [
            'gt.City LIKE :city',
        ];
        $params = ['city' => $city, 'hotelLimit' => self::HOTEL_SEARCH_LIMIT];
        $types = ['city' => \PDO::PARAM_STR, 'hotelLimit' => \PDO::PARAM_INT];

        if (!empty($countryCode)) {
            $where[] = 'gt.CountryCode LIKE :countryCode';
            $params['countryCode'] = $countryCode;
            $types['countryCode'] = \PDO::PARAM_STR;
        }

        if (!empty($stateCode) && $stateCode !== $city) {
            $where[] = 'gt.StateCode LIKE :stateCode';
            $params['stateCode'] = $stateCode;
            $types['stateCode'] = \PDO::PARAM_STR;
        }

        $hotels = $this->connection->fetchAllAssociative('
            SELECT
                    h.HotelID, h.ProviderID, h.HotelBrandID, h.Name, h.Address, h.PointValue, h.CashPrice, h.PointPrice, h.GeoTagID, h.MatchCount, h.Website,
                    ' . $this->hotelHandler->getSqlAvgPontValueCaseCondition() . ' AS avgAboveValue,
                    gt.CountryCode, gt.StateCode, gt.Country, gt.State, gt.City,
                    p.ShortName AS ProviderShortName
            FROM Hotel h
            JOIN GeoTag gt ON (h.GeoTagID = gt.GeoTagID)
            JOIN Provider p ON (p.ProviderID = h.ProviderID)
            WHERE
                    (' . implode(' AND ', $where) . ')
                    ' . $this->getExtraCondition() . '     
            ORDER BY avgAboveValue DESC, h.PointValue DESC
            LIMIT :hotelLimit',
            $params,
            $types
        );

        return $this->hotelHandler->formatHotels($hotels);
    }

    /**
     * @return HotelItem[]
     * @throws \Doctrine\DBAL\Exception
     */
    private function searchByCoordinates(float $lat, float $lng, int $squareSize): array
    {
        [$conditions, $paramsList] = Geo::getSquareGeofenceSQLCondition(
            $lat, $lng,
            'gt.Lat', 'gt.Lng', true,
            round($squareSize / 2)
        );
        $values = $types = [];

        foreach ($paramsList as [$name, $value, $type]) {
            $values[$name] = $value;
            $types[$name] = $type;
        }

        $values['hotelLimit'] = self::HOTEL_SEARCH_LIMIT;
        $types['hotelLimit'] = \PDO::PARAM_INT;

        $stmt = $this->connection->executeQuery("
            SELECT 
                    h.HotelID, h.ProviderID, h.HotelBrandID, h.Name, h.Address, h.PointValue, h.CashPrice, h.PointPrice, h.GeoTagID, h.MatchCount, h.Website,
                    " . $this->hotelHandler->getSqlAvgPontValueCaseCondition() . " AS avgAboveValue,
                    gt.CountryCode, gt.StateCode, gt.Country, gt.State, gt.City,
                    p.ShortName AS ProviderShortName  
            FROM Hotel h
            JOIN GeoTag gt ON (gt.GeoTagID = h.GeoTagID)
            JOIN Provider p ON (p.ProviderID = h.ProviderID)
            WHERE
                    {$conditions}
                    " . $this->getExtraCondition() . "
            ORDER BY avgAboveValue DESC
            LIMIT :hotelLimit",
            $values,
            $types
        );

        $hotels = $stmt->fetchAll();

        return $this->hotelHandler->formatHotels($hotels);
    }
}
