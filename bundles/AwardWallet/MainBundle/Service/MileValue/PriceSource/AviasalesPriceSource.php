<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\Service\MileValue\Constants;
use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AviasalesPriceSource implements PriceSourceInterface
{
    private const API_HOST = 'api.travelpayouts.com';

    private string $apiToken;

    private string $marker;

    private string $host;

    private \HttpDriverInterface $httpDriver;

    private LoggerInterface $logger;

    public function __construct(
        string $aviaSalesApiToken,
        string $aviaSalesMarker,
        string $host,
        \HttpDriverInterface $httpDriver,
        LoggerInterface $logger
    ) {
        $this->apiToken = $aviaSalesApiToken;
        $this->marker = $aviaSalesMarker;
        $this->host = $host;
        $this->httpDriver = $httpDriver;
        $this->logger = $logger;
    }

    public function search(array $routes, string $classOfService, int $passengers): array
    {
        $requestParams = [
            'marker' => $this->marker,
            'host' => $this->host,
            'user_ip' => '127.0.0.1',
            'locale' => 'en-us',
            'trip_class' => in_array($classOfService, Constants::LUXE_CLASSES_OF_SERVICE) ? 'C' : 'Y',
            'passengers' => [
                'adults' => $passengers,
                'children' => 0,
                'infants' => 0,
            ],
            'segments' => $this->convertRequestRoutes($routes),
            'currency' => 'USD',
        ];

        $requestParams['signature'] = $this->createSignature($requestParams);

        $response = $this->requestApi('POST', '/v1/flight_search', $requestParams);

        return $this->convertResults($this->waitSearchResponse($response['search_id']));
    }

    private function requestApi(string $method, string $pathAndQuery, ?array $postData = []): array
    {
        $response = $this->httpDriver->request(new \HttpDriverRequest(
            'https://' . self::API_HOST . $pathAndQuery,
            $method,
            $postData !== null ? json_encode($postData) : null,
            $postData !== null ? ['Content-type' => 'application/json'] : []
        ));

        if ($response->httpCode < 200 || $response->httpCode > 299) {
            throw new \Exception("AviaSales responded with {$response->httpCode}: " . Strings::cutInMiddle($response->body, 512));
        }

        return json_decode($response->body, true);
    }

    /**
     * @param SearchRoute[] $routes
     */
    private function convertRequestRoutes(array $routes): array
    {
        return
            it($routes)
            ->map(function (SearchRoute $route) {
                return [
                    'origin' => $route->depCode,
                    'destination' => $route->arrCode,
                    'date' => date("Y-m-d", $route->depDate),
                ];
            })
            ->toArray()
        ;
    }

    private function createSignature(array $requestParams): string
    {
        $this->sortParams($requestParams);
        $contentToSign = $this->apiToken . ':' . implode(':', $this->getValues($requestParams));

        return md5($contentToSign);
    }

    private function sortParams(array &$requestParams): void
    {
        ksort($requestParams);

        foreach ($requestParams as $key => &$value) {
            if (is_array($value)) {
                $this->sortParams($value);
            }
        }
        unset($value);
    }

    private function getValues(array $requestParams): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return implode(':', $this->getValues($value));
            }

            return $value;
        }, $requestParams);
    }

    private function waitSearchResponse(string $searchId): array
    {
        $result = [];
        $startTime = microtime(true);

        do {
            sleep(5);
            $response = $this->requestApi('GET', '/v1/flight_search_results?uuid=' . urlencode($searchId));

            // Repeat the request until you get an associative array with one element search_id
            if (count($response) === 1 && isset($response[0]['search_id'])) {
                break;
            }
            // As a result of the search, you will get the JSON array where each element is a response from a definite agency.
            $result = array_merge($result, $response);
            $this->logger->info("aviasales results loaded: " . count($result));
        } while ((microtime(true) - $startTime) < 30);

        if (count($result) === 0) {
            throw new \Exception("could not wait for aviasales response");
        }

        return $result;
    }

    private function convertResults(array $response): array
    {
        return
            it($response)
            ->flatMap(function (array $agency) {
                foreach ($agency['proposals'] ?? [] as $proposal) {
                    $raw = array_merge(array_diff_key($agency, ["proposals" => false, "airports" => false, "airlines" => false, "meta" => false, "gates_info" => false, "filters_boundary" => false]), ["proposal" => $proposal]);
                    $flightKeys = [];

                    foreach ($proposal['segment'][0]['flight'] as $flight) {
                        $flightKeys[$flight['trip_class'] . $flight['marketing_carrier'] . $flight['number']] = false;
                    }
                    $raw['flight_info'] = array_intersect_key($raw['flight_info'], $flightKeys);

                    yield new Price(
                        'aviasales',
                        reset($proposal['terms'])['price'],
                        it($proposal['segment'][0]['flight'])->map(function (array $route) {
                            return new ResultRoute(
                                $route['departure'],
                                $route['arrival'],
                                strtotime($route['departure_date'] . ' ' . $route['departure_time']),
                                strtotime($route['arrival_date'] . ' ' . $route['arrival_time']),
                                $route['marketing_carrier'],
                                $route['number']
                            );
                        })->toArray(),
                        null,
                        $raw
                    );
                }
            })
            ->toArray()
        ;
    }
}
