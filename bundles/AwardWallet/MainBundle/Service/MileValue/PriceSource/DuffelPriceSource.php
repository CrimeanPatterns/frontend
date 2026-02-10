<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\Common\CurrencyConverter\CurrencyConverter;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use AwardWallet\Strings\Strings;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DuffelPriceSource implements PriceSourceInterface
{
    private const API_HOST = 'https://api.duffel.com';

    private LoggerInterface $logger;

    private \HttpDriverInterface $httpDriver;

    private CurrencyConverter $currencyConverter;

    private string $apiKey;

    public function __construct(
        LoggerInterface $logger,
        \HttpDriverInterface $httpDriver,
        CurrencyConverter $currencyConverter,
        string $duffelApiKey
    ) {
        $this->logger = $logger;
        $this->httpDriver = $httpDriver;
        $this->currencyConverter = $currencyConverter;
        $this->apiKey = $duffelApiKey;
    }

    public function search(array $routes, string $classOfService, int $passengers): array
    {
        $requestParams = [
            'data' => [
                'passengers' => array_fill(0, $passengers, ['type' => 'adult']),
                'slices' => $this->convertRequestRoutes($routes),
                'cabin_class' => $this->convertClass($classOfService),
            ],
        ];

        $response = $this->requestApi('POST', '/air/offer_requests?return_offers=true', $requestParams);

        return $this->convertResponse($response);
    }

    private function requestApi(string $method, string $pathAndQuery, ?array $postData = []): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Duffel-Version' => 'beta',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        if ($postData !== null) {
            $headers['Content-Type'] = 'application/json';
        }
        $response = $this->httpDriver->request(new \HttpDriverRequest(
            self::API_HOST . $pathAndQuery,
            $method,
            $postData !== null ? json_encode($postData) : null,
            $headers
        ));

        if ($response->httpCode < 200 || $response->httpCode > 299) {
            throw new \Exception("duffel responded with {$response->httpCode}: " . Strings::cutInMiddle($response->body, 512));
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
                    'departure_date' => date("Y-m-d", $route->depDate),
                ];
            })
            ->toArray()
        ;
    }

    private function convertClass(string $classOfService): string
    {
        if ($classOfService === Constants::CLASS_FIRST) {
            return 'first';
        }

        if ($classOfService === Constants::CLASS_BUSINESS) {
            return 'business';
        }

        if ($classOfService === Constants::CLASS_PREMIUM_ECONOMY) {
            return 'premium_economy';
        }

        return 'economy';
    }

    private function convertResponse(array $response): array
    {
        return
            it($response['data']['offers'])
            ->map(function (array $row) {
                return new Price(
                    'duffel',
                    $this->currencyConverter->convertToUsd($row['total_amount'], $row['total_currency']),
                    it($row['slices'])
                        ->flatMap(function (array $slice) {
                            return it($slice['segments'])
                                ->map(function (array $segment) {
                                    return new ResultRoute(
                                        $segment['origin']['iata_code'],
                                        $segment['destination']['iata_code'],
                                        strtotime($segment['departing_at']),
                                        strtotime($segment['arriving_at']),
                                        $segment['marketing_carrier']['iata_code'],
                                        $segment['marketing_carrier_flight_number']);
                                })
                            ;
                        })
                        ->toArray(),
                    null,
                    $row
                );
            })
            ->toArray()
        ;
    }
}
