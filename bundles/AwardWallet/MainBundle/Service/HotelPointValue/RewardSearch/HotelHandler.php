<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch;

use AwardWallet\MainBundle\Service\LocationFormatter;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\DBAL\Connection;

class HotelHandler
{
    public const HOTEL_PROVIDER_LIMIT = 5;

    private MileValueService $mileValueService;
    private LocationFormatter $locationFormatter;
    private Connection $connection;

    public function __construct(
        MileValueService $mileValueService,
        LocationFormatter $locationFormatter,
        Connection $connection
    ) {
        $this->mileValueService = $mileValueService;
        $this->locationFormatter = $locationFormatter;
        $this->connection = $connection;
    }

    /**
     * @return HotelItem[]
     * @throws \Exception
     */
    public function getHotels(int $providerId, int $hotelLimit, int $itMinCount = 1): array
    {
        $hotels = $this->connection->fetchAll('
            SELECT
                    h.HotelID, h.ProviderID, h.HotelBrandID, h.Name, h.Address, h.PointValue, h.CashPrice, h.PointPrice, h.GeoTagID, h.MatchCount, h.Website,
                    ' . $this->getSqlAvgPontValueCaseCondition() . ' AS avgAboveValue,
                    gt.CountryCode, gt.StateCode, gt.Country, gt.State, gt.City,
                    p.ShortName AS ProviderShortName
            FROM Hotel h
            JOIN GeoTag gt ON (h.GeoTagID = gt.GeoTagID)
            JOIN Provider p ON (p.ProviderID = h.ProviderID)
            WHERE
                    h.ProviderID = :providerId
                AND gt.City IS NOT NULL
                AND gt.CountryCode IS NOT NULL
                AND h.MatchCount >= ' . $itMinCount . '
            ORDER BY avgAboveValue DESC, h.PointValue DESC
            LIMIT :hotelLimit',
            ['providerId' => $providerId, 'hotelLimit' => $hotelLimit],
            ['providerId' => \PDO::PARAM_INT, 'hotelLimit' => \PDO::PARAM_INT]
        );

        return $this->formatHotels($hotels);
    }

    /**
     * @return HotelItem[]
     * @throws \Exception
     */
    public function formatHotels(array $hotels): array
    {
        $result = [];

        foreach ($hotels as &$hotel) {
            if (empty($hotel['City']) || empty($hotel['CountryCode'])) {
                continue;
            }
            // $avgPointValue = $this->mileValueService->getProviderValue($hotel['ProviderID'], 'AvgPointValue');

            $hotel['location'] = $this->locationFormatter->formatLocationName($hotel['City'], $hotel['CountryCode'],
                $hotel['Country'], $hotel['State']);
            // $hotel['avgAboveValue'] = round(($hotel['PointValue'] / $avgPointValue * 100) - 100);
            $hotel['ProviderShortName'] = html_entity_decode($hotel['ProviderShortName']);

            $result[] = new HotelItem(
                $hotel['HotelID'],
                $hotel['Name'],
                $hotel['ProviderShortName'],
                $hotel['PointValue'],
                $hotel['avgAboveValue'],
                $hotel['CashPrice'],
                $hotel['PointPrice'],
                $hotel['location'],
                $hotel['Website'],
                $hotel['MatchCount']
            );
        }

        usort($result, fn ($a, $b) => $b->getAboveAverage() <=> $a->getAboveAverage());

        return $result;
    }

    public function getSqlAvgPontValueCaseCondition(): string
    {
        $hotelPointValues = $this->mileValueService->fetchCombinedHotelValueData();
        $sqlAvgPointCaseCondition = 'CASE' . PHP_EOL;

        foreach ($hotelPointValues as $hotelPointValue) {
            $autoValues = $hotelPointValue->getAutoValues();

            if (!isset($autoValues['AvgPointValue'])) {
                // may be we should consider manual value here?
                // choice hotels for example has only manual
                continue;
            }

            $avgPointValue = $autoValues['AvgPointValue']['value'];
            $sqlAvgPointCaseCondition .= 'WHEN h.ProviderID = ' . $hotelPointValue->getProviderId() . ' THEN ' . $avgPointValue . PHP_EOL;
        }
        $sqlAvgPointCaseCondition .= 'END' . PHP_EOL;

        return 'ROUND((h.PointValue / (' . $sqlAvgPointCaseCondition . ') * 100) - 100 , 2)';
    }
}
