<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\Service\MileValue\Constants;
use Psr\Log\LoggerInterface;

class FarePortalPriceSource implements PriceSourceInterface
{
    private const SOURCE_ID = 'fareportal';

    private const SEARCH_TYPES = [
        Constants::ROUTE_TYPE_MULTI_CITY => 'MULTICITY',
        Constants::ROUTE_TYPE_ROUND_TRIP => 'ROUNDTRIP',
        Constants::ROUTE_TYPE_ONE_WAY => 'ONEWAYTRIP',
    ];

    private LoggerInterface $logger;

    private \HttpDriverInterface $httpDriver;

    private string $fplUserName;

    private string $fplPassword;

    public function __construct(
        LoggerInterface $logger,
        \HttpDriverInterface $httpDriver,
        string $fplUserName,
        string $fplPassword
    ) {
        $this->logger = $logger;
        $this->httpDriver = $httpDriver;
        $this->fplUserName = $fplUserName;
        $this->fplPassword = $fplPassword;
    }

    public function search(array $routes, string $classOfService, int $passengers): array
    {
        $request = $this->prepareSearchRequest($routes, $classOfService, $passengers);
        $response = $this->sendRequest($request);

        if ($response === null || !array_key_exists('IsSearchCompleted', $response)) {
            return [];
        }

        // $response = $this->waitSearchComplete($response);

        return $this->convertResponse($response, $passengers);
    }

    /**
     * @param SearchRoute[] $routes
     * @param string $classOfService - one of Constants::CLASSES_OF_SERVICE
     */
    private function prepareSearchRequest(array $routes, string $classOfService, int $passengers): \HttpDriverRequest
    {
        $segments = array_map(function (SearchRoute $route) {
            return [
                "DepartureDate" => date("Y-m-d", $route->depDate),
                "DepartureTime" => date("Hi", $route->depDate),
                "Origin" => $route->depCode,
                "Destination" => $route->arrCode,
            ];
        }, $routes);

        $routeType = RouteTypeDetector::detect($routes);

        if (!in_array($classOfService, Constants::CLASSES_OF_SERVICE)) {
            throw new \Exception("Unknown class of service");
        }

        // http://fpwebbox.fareportal.com/gateway.asmx?op=SearchFlightAvailability39
        // http://fpwebbox-uk.fareportal.com/Help/Api/POST-api-Search-SearchFlightAvailability

        $requestBody = [
            "ResponseVersion" => "VERSION41",
            "FlightSearchRequest" => [
                "Adults" => $passengers,
                "Child" => "0",
                // @TODO: check, will basic economy or premium economy return something? may be it is the source of error for air taixes?
                "ClassOfService" => $classOfService,
                "InfantInLap" => "0",
                "InfantOnSeat" => "0",
                "Seniors" => "0",
                "TypeOfTrip" => self::SEARCH_TYPES[$routeType],
                "SegmentDetails" => $segments,
            ],
        ];

        return $this->prepareHttpRequest('POST', '/air/api/search/searchflightavailability', $requestBody);
    }

    private function sendRequest(\HttpDriverRequest $request): ?array
    {
        $try = 0;

        do {
            $try++;
            $response = $this->httpDriver->request($request);
            $json = json_decode($response->body, true);

            if ($json === null) {
                $this->logger->warning("failed to decode json: http code {$response->httpCode}, error code: {$response->errorCode} " . substr($response->body, 0, 2048));
                sleep($try ** 2);
            }
        } while ($json === null && $try < 3);

        if ($json === null) {
            $this->logger->warning("failed to query fareportallabs");

            return null;
        }

        return $json;
    }

    /**
     * @param array $response - json response from fare portal labs
     * @return Price[]
     */
    private function convertResponse(array $response, int $passengers): array
    {
        $details = $response['FlightResponse']['FpSearch_AirLowFaresRS']['SegmentReference']['RefDetails'] ?? [];

        if (empty($details)) {
            $this->logger->info("no prices found");

            return [];
        }

        $outbounds = $this->convertOptions(
            $response['FlightResponse']['FpSearch_AirLowFaresRS']['OriginDestinationOptions']['OutBoundOptions']['OutBoundOption'] ?? []
        );
        $inbounds = $this->convertOptions(
            $response['FlightResponse']['FpSearch_AirLowFaresRS']['OriginDestinationOptions']['InBoundOptions']['InBoundOption'] ?? []
        );

        $results = array_map(function (array $detail) use ($outbounds, $inbounds, $passengers): ?Price {
            $routes = $this->convertRoutes($detail, $outbounds, $inbounds);

            if ($routes === null) {
                return null;
            }

            return new Price(self::SOURCE_ID, $detail['PTC_FareBreakdown']['Adult']['TotalAdultFare'] * $passengers, $routes, null, $detail);
        }, $details);

        return array_filter($results, function (?Price $price): bool {
            return $price !== null;
        });
    }

    /**
     * @return array ['optionId' => ResultRoute[]]
     */
    private function convertOptions(array $options): array
    {
        $result = [];

        foreach ($options as $option) {
            $result[$option['Segmentid']] = array_map(function (array $segment): ?ResultRoute {
                return new ResultRoute(
                    $segment['DepartureAirport']['LocationCode'],
                    $segment['ArrivalAirport']['LocationCode'],
                    $this->convertDateTimeStr($segment["DepartureDateTime"]),
                    $this->convertDateTimeStr($segment["ArrivalDateTime"]),
                    $segment['OperatedByAirline']['CompanyText'],
                    $segment['FlightNumber'] . ' ' . $segment['FlightCabin']['CabinType'] . ' ' . $segment['FlightClass']['ClassType']
                );
            }, $option['FlightSegment']);
        }

        return $result;
    }

    private function convertDateTimeStr(string $localDateTime): int
    {
        if (!preg_match('#^(\d\d)([a-z]{3})(\d\d\d\d)T(\d\d):(\d\d) (AM|PM)$#ims', $localDateTime, $matches)) {
            throw new \Exception("Failed to parse time: $localDateTime");
        }

        return strtotime("{$matches[1]} {$matches[2]} {$matches[3]} {$matches[4]}:{$matches[5]}{$matches[6]}");
    }

    private function convertRoutes(array $detail, array $outbounds, array $inbounds): ?array
    {
        $routes = [];

        foreach ($detail['OutBoundOptionId'] as $optionId) {
            if (!isset($outbounds[$optionId])) {
                return null;
            }
            $routes = array_merge($routes, $outbounds[$optionId]);
        }

        foreach ($detail['InBoundOptionId'] as $index => $optionId) {
            if (!isset($inbounds[$optionId])) {
                return null;
            }
            $routes = array_merge($routes, $inbounds[$optionId]);
        }

        return $routes;
    }

    private function prepareHttpRequest(string $method, string $path, ?array $requestBody): \HttpDriverRequest
    {
        $headers = [
            "Authorization" => "basic " . base64_encode($this->fplUserName . ":" . $this->fplPassword),
        ];

        if ($method === 'POST') {
            $headers["Content-Type"] = "application/json";
        }

        return new \HttpDriverRequest(
            'https://api-dev.fareportallabs.com' . $path,
            $method,
            $method === 'POST' ? json_encode($requestBody) : null,
            $headers
        );
    }

    private function waitSearchComplete(array $response): array
    {
        $startTime = time();

        while (!$response['IsSearchCompleted'] && !isset($response['FlightResponse']['ErrorReport']) && (time() - $startTime) < 60) {
            // http://fpwebbox-uk.fareportal.com/Help/Api/GET-api-GatewaySearch-CheckSearchStatus-CntKey_
            $response = $this->sendRequest($this->prepareHttpRequest('POST', '/air/api/search/getairfaresforanchorsearch', [
                "ResponseVersion" => "VERSION45",
                'AnchorSearchDetail' => [
                    'CntKey' => $response['FlightResponse']['FpSearch_AirLowFaresRS']['CntKey'],
                ],
            ]));

            if ($response === null) {
                return [];
            }
            sleep(5);
        }

        if (array_key_exists('ErrorReport', $response)) {
            $this->logger->warning("error: {$response['ErrorReport']['ErrorDescription']}");

            return [];
        }

        if (!$response['IsSearchCompleted']) {
            $this->logger->warning("could not complete search");
        }

        return $response;
    }
}
