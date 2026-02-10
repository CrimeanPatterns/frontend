<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\RA;

use AwardWallet\Common\Memcached\MemcachedMock;
use AwardWallet\MainBundle\Service\RA\AwardPriceService;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Codeception\Module\JsonNormalizer;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Service\RA\AwardPriceService
 * @group frontend-unit
 */
class AwardPriceServiceTest extends BaseContainerTest
{
    private const BASE_JSON_PATH = 'Manager/AwardPriceService/';
    private const REGION_COUNTRY_USA = 10263;
    private const REGION_USA_CONTINENTAL_48 = 10278;
    private ?string $raflightTableName;
    private ?JsonNormalizer $jsonNormalizer;

    public function _before()
    {
        parent::_before();

        $this->jsonNormalizer = $this->getModule('JsonNormalizer');
        $this->raflightTableName = 'RAFlightAwardPriceServiceTest' . \bin2hex(\random_bytes(10));
        $this->container
            ->get('database_connection')
            ->executeQuery("create table {$this->raflightTableName} like RAFlight");
    }

    public function _after()
    {
        $this->raflightTableName = null;
        $this->jsonNormalizer = null;

        parent::_after();
    }

    /**
     * @dataProvider csvRowsDataProvider
     */
    public function testCsvRows(array $dbRows, array $params, string $expectedFilePath, array $sharedContext): void
    {
        foreach ($dbRows as $dbRow) {
            $this->db->haveInDatabase($this->raflightTableName, $dbRow);
        }

        $aps = $this->getAwardPriceService();
        $awardPriceData = $aps->getAwardPriceData($params);
        $this->jsonNormalizer->expectJsonTemplate(
            $expectedFilePath,
            \json_encode($awardPriceData),
            $sharedContext
        );
    }

    public function csvRowsDataProvider()
    {
        $requestId = fn () => \bin2hex(\random_bytes(12));
        $defaultParams = [
            'providerCode' => 'delta',
            'cabinType' => 'economy',
            'flightType' => '1',
            'origType' => '1',
            'destType' => '2',

            'origRegion' => self::REGION_USA_CONTINENTAL_48,
            // 'origCountry' => '1',
            // 'origIncChild' => '1',

            // 'destRegion' => self::REGION_USA_CONTINENTAL_48,
            'destCountry' => self::REGION_COUNTRY_USA,
            // 'destIncChild' => '1',
        ];

        return [
            'empty' => [
                [],
                $defaultParams,
                self::getEmptyFile(),
                [],
            ],
            'one provider one row' => [
                [
                    [
                        'RequestID' => $requestId(),
                        'SearchDate' => '2024-04-01 16:18:38',
                        'Provider' => 'delta',
                        'Airlines' => 'DL',
                        'StandardSegmentCOS' => 'economy',
                        'FareClasses' => 'NE',
                        'AwardType' => '',
                        'FlightType' => 1,
                        'Route' => 'MIA-JFK',
                        'FromAirport' => 'MIA',
                        'FromRegion' => 'USA (Continental 48)',
                        'FromCountry' => 'United States',
                        'ToAirport' => 'JFK',
                        'ToRegion' => 'USA (Continental 48)',
                        'ToCountry' => 'United States',
                        'MileCost' => 7000,
                        'Taxes' => 6.0,
                        'Currency' => 'USD',
                        'DaysBeforeDeparture' => 58,
                        'DepartureDate' => '2024-05-30 07:01:00',
                        'ArrivalDate' => '2024-05-30 10:00:00',
                        'TravelTime' => 179,
                        'Stopovers' => 0,
                        'Layovers' => 0,
                        'TotalDistance' => 1088.96,
                        'LayoverOne' => '',
                        'LayoverOneDistance' => 0,
                        'StopoverOne' => '',
                        'StopoverOneDistance' => 0,
                        'LayoverTwo' => '',
                        'LayoverTwoDistance' => 0,
                        'StopoverTwo' => '',
                        'StopoverTwoDistance' => 0,
                        'IsMixedCabin' => 0,
                        'IsFastest' => 1,
                        'StandardItineraryCOS' => 'economy',
                        'BrandedItineraryCOS' => 'Basic Economy',
                        'IsCheapest' => 1,
                        'Passengers' => 2,
                        'SeatsLeft' => null,
                        'SeatsLeftOnRoute' => 9,
                        'ODDistance' => 1088.96,
                        'BrandedSegmentCOS' => 'Basic Economy',
                        'CostPerHour' => 2346,
                    ],
                ],
                $defaultParams,
                self::fromRelativePath('one_provider_one_row.json'),
                [],
            ],
            'one provider multiple rows' => [
                $oneProviderRows = [
                    [
                        "RequestID" => $requestId(),
                        "SearchDate" => "2024-04-01 16:22:28",
                        "Provider" => "delta",
                        "Airlines" => "DL,DL",
                        "StandardSegmentCOS" => "economy,economy",
                        "FareClasses" => "NM,NM",
                        "AwardType" => "",
                        "FlightType" => 1,
                        "Route" => "IAH-ATL,lo:46m,ATL-COS",
                        "FromAirport" => "IAH",
                        "FromRegion" => "USA (Continental 48)",
                        "FromCountry" => "United States",
                        "ToAirport" => "COS",
                        "ToRegion" => "USA (Continental 48)",
                        "ToCountry" => "United States",
                        "MileCost" => 39000,
                        "Taxes" => 6.00,
                        "Currency" => "USD",
                        "DaysBeforeDeparture" => 45,
                        "DepartureDate" => "2024-05-17 15:46:00",
                        "ArrivalDate" => "2024-05-17 20:37:00",
                        "TravelTime" => 351,
                        "Stopovers" => 0,
                        "Layovers" => 1,
                        "TotalDistance" => 1864.47,
                        "LayoverOne" => "ATL",
                        "LayoverOneDistance" => 0,
                        "StopoverOne" => "",
                        "StopoverOneDistance" => 0,
                        "LayoverTwo" => "",
                        "LayoverTwoDistance" => 0,
                        "StopoverTwo" => "",
                        "StopoverTwoDistance" => 0,
                        "IsMixedCabin" => 0,
                        "IsFastest" => 1,
                        "StandardItineraryCOS" => "economy",
                        "BrandedItineraryCOS" => "Main",
                        "IsCheapest" => 1,
                        "Passengers" => 1,
                        "SeatsLeft" => null,
                        "SeatsLeftOnRoute" => 9,
                        "ODDistance" => 807.434,
                        "BrandedSegmentCOS" => "Main,Main",
                        "CostPerHour" => 7672,
                    ],
                    [
                        "RequestID" => $requestId(),
                        "SearchDate" => "2024-04-01 16:22:18",
                        "Provider" => "delta",
                        "Airlines" => "DL,DL,DL",
                        "StandardSegmentCOS" => "economy,economy,economy",
                        "FareClasses" => "NE,NE,NE",
                        "AwardType" => "",
                        "FlightType" => 1,
                        "Route" => "ORD-MSP,lo:2h14m,MSP-LAX,lo:2h15m,LAX-HNL",
                        "FromAirport" => "ORD",
                        "FromRegion" => "USA (Continental 48)",
                        "FromCountry" => "United States",
                        "ToAirport" => "HNL",
                        "ToRegion" => "Hawaii",
                        "ToCountry" => "United States",
                        "MileCost" => 35000,
                        "Taxes" => 6.00,
                        "Currency" => "USD",
                        "DaysBeforeDeparture" => 172,
                        "DepartureDate" => "2024-09-21 09:30:00",
                        "ArrivalDate" => "2024-09-21 20:24:00",
                        "TravelTime" => 954,
                        "Stopovers" => 0,
                        "Layovers" => 2,
                        "TotalDistance" => 4409.59,
                        "LayoverOne" => "MSP",
                        "LayoverOneDistance" => 0,
                        "StopoverOne" => "",
                        "StopoverOneDistance" => 0,
                        "LayoverTwo" => "LAX",
                        "LayoverTwoDistance" => 0,
                        "StopoverTwo" => "",
                        "StopoverTwoDistance" => 0,
                        "IsMixedCabin" => 0,
                        "IsFastest" => 0,
                        "StandardItineraryCOS" => "economy",
                        "BrandedItineraryCOS" => "Basic Economy",
                        "IsCheapest" => 1,
                        "Passengers" => 1,
                        "SeatsLeft" => null,
                        "SeatsLeftOnRoute" => 9,
                        "ODDistance" => 4227.52,
                        "BrandedSegmentCOS" => "Basic Economy,Basic Economy,Basic Economy",
                        "CostPerHour" => 3066,
                    ],
                    [
                        "RequestID" => $requestId(),
                        "SearchDate" => "2024-04-01 16:22:31",
                        "Provider" => "delta",
                        "Airlines" => "DL,DL",
                        "StandardSegmentCOS" => "economy,economy",
                        "FareClasses" => "NE,NE",
                        "AwardType" => "",
                        "FlightType" => 1,
                        "Route" => "DFW-SLC,lo:2h45m,SLC-DEN",
                        "FromAirport" => "DFW",
                        "FromRegion" => "USA (Continental 48)",
                        "FromCountry" => "United States",
                        "ToAirport" => "DEN",
                        "ToRegion" => "USA (Continental 48)",
                        "ToCountry" => "United States",
                        "MileCost" => 9000,
                        "Taxes" => 6.00,
                        "Currency" => "USD",
                        "DaysBeforeDeparture" => 128,
                        "DepartureDate" => "2024-08-08 05:30:00",
                        "ArrivalDate" => "2024-08-08 11:41:00",
                        "TravelTime" => 431,
                        "Stopovers" => 0,
                        "Layovers" => 1,
                        "TotalDistance" => 1374.91,
                        "LayoverOne" => "SLC",
                        "LayoverOneDistance" => 0,
                        "StopoverOne" => "",
                        "StopoverOneDistance" => 0,
                        "LayoverTwo" => "",
                        "LayoverTwoDistance" => 0,
                        "StopoverTwo" => "",
                        "StopoverTwoDistance" => 0,
                        "IsMixedCabin" => 0,
                        "IsFastest" => 0,
                        "StandardItineraryCOS" => "economy",
                        "BrandedItineraryCOS" => "Basic Economy",
                        "IsCheapest" => 1,
                        "Passengers" => 2,
                        "SeatsLeft" => null,
                        "SeatsLeftOnRoute" => 9,
                        "ODDistance" => 639.421,
                        "BrandedSegmentCOS" => "Basic Economy,Basic Economy",
                        "CostPerHour" => 2030,
                    ],
                    [
                        "RequestID" => $requestId(),
                        "SearchDate" => "2024-04-01 16:22:31",
                        "Provider" => "delta",
                        "Airlines" => "DL,DL",
                        "StandardSegmentCOS" => "economy,economy",
                        "FareClasses" => "NE,NE",
                        "AwardType" => "",
                        "FlightType" => 1,
                        "Route" => "DAL-ATL,lo:2h,ATL-DEN",
                        "FromAirport" => "DAL",
                        "FromRegion" => "USA (Continental 48)",
                        "FromCountry" => "United States",
                        "ToAirport" => "DEN",
                        "ToRegion" => "USA (Continental 48)",
                        "ToCountry" => "United States",
                        "MileCost" => 19000,
                        "Taxes" => 6.00,
                        "Currency" => "USD",
                        "DaysBeforeDeparture" => 128,
                        "DepartureDate" => "2024-08-08 14:50:00",
                        "ArrivalDate" => "2024-08-08 21:10:00",
                        "TravelTime" => 440,
                        "Stopovers" => 0,
                        "Layovers" => 1,
                        "TotalDistance" => 1910.39,
                        "LayoverOne" => "ATL",
                        "LayoverOneDistance" => 0,
                        "StopoverOne" => "",
                        "StopoverOneDistance" => 0,
                        "LayoverTwo" => "",
                        "LayoverTwoDistance" => 0,
                        "StopoverTwo" => "",
                        "StopoverTwoDistance" => 0,
                        "IsMixedCabin" => 0,
                        "IsFastest" => 0,
                        "StandardItineraryCOS" => "economy",
                        "BrandedItineraryCOS" => "Basic Economy",
                        "IsCheapest" => 1,
                        "Passengers" => 2,
                        "SeatsLeft" => null,
                        "SeatsLeftOnRoute" => 9,
                        "ODDistance" => 649.124,
                        "BrandedSegmentCOS" => "Basic Economy,Basic Economy",
                        "CostPerHour" => 3563,
                    ],
                    [
                        "RequestID" => $requestId(),
                        "SearchDate" => "2024-04-01 16:22:29",
                        "Provider" => "delta",
                        "Airlines" => "DL,DL",
                        "StandardSegmentCOS" => "economy,economy",
                        "FareClasses" => "NM,NM",
                        "AwardType" => "",
                        "FlightType" => 1,
                        "Route" => "COS-ATL,lo:2h52m,ATL-IAH",
                        "FromAirport" => "COS",
                        "FromRegion" => "USA (Continental 48)",
                        "FromCountry" => "United States",
                        "ToAirport" => "IAH",
                        "ToRegion" => "USA (Continental 48)",
                        "ToCountry" => "United States",
                        "MileCost" => 39000,
                        "Taxes" => 6.00,
                        "Currency" => "USD",
                        "DaysBeforeDeparture" => 47,
                        "DepartureDate" => "2024-05-19 06:00:00",
                        "ArrivalDate" => "2024-05-19 14:51:00",
                        "TravelTime" => 471,
                        "Stopovers" => 0,
                        "Layovers" => 1,
                        "TotalDistance" => 1864.47,
                        "LayoverOne" => "ATL",
                        "LayoverOneDistance" => 0,
                        "StopoverOne" => "",
                        "StopoverOneDistance" => 0,
                        "LayoverTwo" => "",
                        "LayoverTwoDistance" => 0,
                        "StopoverTwo" => "",
                        "StopoverTwoDistance" => 0,
                        "IsMixedCabin" => 0,
                        "IsFastest" => 1,
                        "StandardItineraryCOS" => "economy",
                        "BrandedItineraryCOS" => "Main",
                        "IsCheapest" => 1,
                        "Passengers" => 1,
                        "SeatsLeft" => null,
                        "SeatsLeftOnRoute" => 6,
                        "ODDistance" => 807.434,
                        "BrandedSegmentCOS" => "Main,Main",
                        "CostPerHour" => 7826,
                    ],
                ],
                $defaultParams,
                self::fromRelativePath('one_provider_multiple_rows.json'),
                [],
            ],
            'one provider multiple rows, include dest children' => [
                $oneProviderRows,
                \array_merge(
                    $defaultParams,
                    ['destIncChild' => '1']
                ),
                self::fromRelativePath('one_provider_multiple_rows_include_dest_children.json'),
                [],
            ],
        ];
    }

    private static function fromRelativePath(string $path): string
    {
        return codecept_data_dir(self::BASE_JSON_PATH . $path);
    }

    private static function getEmptyFile(): string
    {
        return self::fromRelativePath('empty.json');
    }

    private function getAwardPriceService()
    {
        return new AwardPriceService(
            $this->container->get('database_connection'),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new MemcachedMock(),
            $this->raflightTableName
        );
    }
}
