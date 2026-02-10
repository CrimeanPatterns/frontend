<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class KiwiPriceSource implements PriceSourceInterface
{
    private const API_HOST = 'https://tequila-api.kiwi.com';

    private string $multiCityApiKey;

    private string $kiwiSearchApiKey;

    private \HttpDriverInterface $httpDriver;

    private LoggerInterface $logger;

    public function __construct(string $kiwiMultiCityApiKey, string $kiwiSearchApiKey, \HttpDriverInterface $httpDriver, LoggerInterface $logger)
    {
        $this->multiCityApiKey = $kiwiMultiCityApiKey;
        $this->kiwiSearchApiKey = $kiwiSearchApiKey;
        $this->httpDriver = $httpDriver;
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->setMessagePrefix("kiwi: ");
    }

    public function search(array $routes, string $classOfService, int $passengers): array
    {
        if ($passengers > 9) {
            $this->logger->info("KiwiPriceSource: too many passengers: $passengers");

            return [];
        }

        $requestParams = [
            'requests' => $this->convertRequestRoutes($routes, $passengers),
        ];

        if (count($routes) === 1) {
            $response = $this->requestApi('GET', '/v2/search?locale=en&curr=USD&selected_cabins=' . $this->convertClassOfService($classOfService) . '&' . http_build_query($requestParams['requests'][0]), null, $this->kiwiSearchApiKey)['data'];
        } else {
            $response = $this->requestApi('POST', '/v2/flights_multi?locale=en&curr=USD&selected_cabins=' . $this->convertClassOfService($classOfService), $requestParams, $this->multiCityApiKey);

            if (count($response) === count($routes) && RouteTypeDetector::detect($routes) !== Constants::ROUTE_TYPE_ROUND_TRIP) {
                // https://tequila.kiwi.com/portal/docs/tequila_api/multicity_api
                // In the response root, the results field contains a list of responses for the posted queries.
                // Every single element contains the same response as it would if /search API call were used.

                // however, seems like for roundtrips kiwi returns responses on root level
                $response = it($response)->flatMap(function (array $rows) { return $rows; })->toArray();
            }
        }

        return $this->convertResponse($response);
    }

    /**
     * @internal
     */
    public function routeToResult(array $route): ResultRoute
    {
        return new ResultRoute(
            $route['flyFrom'],
            $route['flyTo'],
            $route['dTime'] ?? strtotime($route['local_departure']), // differs for one-way and roundtrips results
            $route['aTime'] ?? strtotime($route['local_arrival']),
            $route['airline'],
            $route['operating_flight_no'],
            null,
            $route['operating_carrier'] ?? null,
            $route['operating_carrier_flight_no'] ?? null,
            $route['fare_classes'] ?? null,
            $route['fare_basis'] ?? null
        );
    }

    private function requestApi(string $method, string $pathAndQuery, ?array $postData, string $apiKey): array
    {
        $headers = ['Accept' => 'application/json', 'apikey' => $apiKey];

        if ($postData !== null) {
            $headers['Content-Type'] = 'application/json';
        }
        $try = 0;

        do {
            $try++;
            $response = $this->httpDriver->request(new \HttpDriverRequest(
                self::API_HOST . $pathAndQuery,
                $method,
                $postData !== null ? json_encode($postData) : null,
                $headers
            ));
            $json = null;

            if ($response->httpCode >= 200 && $response->httpCode <= 299) {
                $json = json_decode($response->body, true);
            }

            if ($json === null) {
                $this->logger->warning("failed to decode json: http code {$response->httpCode}, error code: {$response->errorCode} " . substr($response->body, 0, 2048));
                sleep($try ** 2);
            }
        } while ($json === null && $try < 3);

        if ($json === null) {
            return [];
        }

        return $json;
    }

    /**
     * @param SearchRoute[] $routes
     */
    private function convertRequestRoutes(array $routes, int $passengers): array
    {
        return
            it($routes)
            ->map(function (SearchRoute $route) use ($passengers) {
                return [
                    'flyFrom' => $route->depCode,
                    'to' => $route->arrCode,
                    'dateFrom' => date("d/m/Y", $route->depDate),
                    'dateTo' => date("d/m/Y", $route->depDate),
                    'adults' => $passengers,
                ];
            })
            ->toArray()
        ;
    }

    private function convertResponse(array $prices): array
    {
        return
            it($prices)
            ->map(function (array $row) {
                return new Price(
                    'kiwi',
                    $row['price'],
                    it($row['route'])->flatMap(function (array $route) {
                        if (isset($route['route'])) {
                            // sub-segments, seen in roundtrips
                            return it($route['route'])->map([$this, "routeToResult"])->toArray();
                        }

                        return [$this->routeToResult($route)];
                    })->toArray(),
                    $row['deep_link'] ?? null,
                    $row
                );
            })
            ->toArray()
        ;
    }

    private function convertClassOfService(string $classOfService): string
    {
        if ($classOfService === Constants::CLASS_FIRST) {
            return 'F';
        }

        if ($classOfService === Constants::CLASS_BUSINESS) {
            return 'C';
        }

        if ($classOfService === Constants::CLASS_PREMIUM_ECONOMY) {
            return 'W';
        }

        return 'M';
    }
}
