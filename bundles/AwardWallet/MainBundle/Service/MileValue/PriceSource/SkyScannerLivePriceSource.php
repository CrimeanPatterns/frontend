<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\Service\MileValue\Constants;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SkyScannerLivePriceSource implements PriceSourceInterface
{
    public const CABIN_CLASS_ECONOMY = 'CABIN_CLASS_ECONOMY';
    public const CABIN_CLASS_PREMIUM_ECONOMY = 'CABIN_CLASS_PREMIUM_ECONOMY';
    public const CABIN_CLASS_BUSINESS = 'CABIN_CLASS_BUSINESS';
    public const CABIN_CLASS_FIRST = 'CABIN_CLASS_FIRST';

    public const RESULT_STATUS_COMPLETE = 'RESULT_STATUS_COMPLETE';
    public const RESULT_STATUS_INCOMPLETE = 'RESULT_STATUS_INCOMPLETE';
    public const PLACE_TYPE_AIRPORT = 'PLACE_TYPE_AIRPORT';
    public const AGENT_TYPE_AIRLINE = 'AGENT_TYPE_AIRLINE';
    public const PRICE_UNIT_UNSPECIFIED = 'PRICE_UNIT_UNSPECIFIED';
    public const PRICE_UNIT_WHOLE = 'PRICE_UNIT_WHOLE';
    public const PRICE_UNIT_CENTI = 'PRICE_UNIT_CENTI';
    public const PRICE_UNIT_MILLI = 'PRICE_UNIT_MILLI';
    public const PRICE_UNIT_MICRO = 'PRICE_UNIT_MICRO';

    private const SOURCE_ID = 'skyscanner';

    private LoggerInterface $logger;

    private \HttpDriverInterface $httpDriver;

    private string $skyscannerApiKey;

    public function __construct(
        LoggerInterface $logger,
        \HttpDriverInterface $httpDriver,
        string $skyscannerApiKey
    ) {
        $this->logger = $logger;
        $this->httpDriver = $httpDriver;
        $this->skyscannerApiKey = $skyscannerApiKey;
    }

    public function search(array $routes, string $classOfService, int $passengers): array
    {
        $cabinClass = $this->convertCabinClass($classOfService);

        if (is_null($cabinClass)) {
            throw new \InvalidArgumentException(sprintf('Unknown class of service: %s', $classOfService));
        }

        $segments = array_map(function (SearchRoute $route) use ($cabinClass, $passengers) {
            return [
                'query' => [
                    'currency' => 'USD',
                    'market' => 'US',
                    'locale' => 'en-US',
                    'adults' => $passengers,
                    'cabinClass' => $cabinClass,
                    'queryLegs' => [
                        [
                            'originPlaceId' => [
                                'iata' => $route->depCode,
                            ],
                            'destinationPlaceId' => [
                                'iata' => $route->arrCode,
                            ],
                            'date' => [
                                'year' => (int) date('Y', $route->depDate),
                                'month' => (int) date('m', $route->depDate),
                                'day' => (int) date('d', $route->depDate),
                            ],
                        ],
                    ],
                ],
                'route' => $route,
            ];
        }, $routes);

        // unions round trip segments
        if (RouteTypeDetector::detect($routes) === Constants::ROUTE_TYPE_ROUND_TRIP && count($segments) === 2) {
            $firstSegment = $segments[0];
            $firstSegment['query']['queryLegs'][] = $segments[1]['query']['queryLegs'][0];
            $segments = [$firstSegment];
        }

        $prices = [];
        $bestSegmentCount = 10;

        if (count($segments) > 3) {
            $bestSegmentCount = 5;
        }

        if (count($segments) > 6) {
            $bestSegmentCount = 3;
        }

        if (count($segments) > 8) {
            $bestSegmentCount = 2;
        }

        if (count($segments) > 10) {
            $bestSegmentCount = 1;
        }

        foreach ($segments as $segment) {
            $sessionToken = $this->createSessionToken($segment['query']);

            if (is_null($sessionToken)) {
                return [];
            }

            $searchResult = $this->searchPoll($sessionToken);

            if (is_null($searchResult)) {
                return [];
            }

            $segmentPrices = $this->getPrices($searchResult, $passengers);

            if (empty($segmentPrices)) {
                $this->logger->info('no prices found for one or more segment');
                $prices = [];

                break;
            } elseif (count($segments) == 1) {
                $prices = $segmentPrices;
            } else {
                $prices = $this->unionPriceSegments($prices, $segmentPrices, $bestSegmentCount);
            }
        }

        if ($classOfService === Constants::CLASS_BUSINESS) {
            $prices = array_merge($prices, $this->search($routes, Constants::CLASS_FIRST, $passengers));
        }

        return $prices;
    }

    private function createSessionToken(array $query): ?string
    {
        $request = new \HttpDriverRequest(
            'https://partners.api.skyscanner.net/apiservices/v3/flights/live/search/create',
            'POST',
            \json_encode(['query' => $query]),
            [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->skyscannerApiKey,
            ]
        );

        $response = $this->httpDriver->request($request);

        if ($response->httpCode !== 200) {
            $this->logger->error(sprintf('failed to access skyscanner api, response code: %s', $response->httpCode));

            return null;
        }

        $json = json_decode($response->body, true);

        if (is_null($json)) {
            $this->logger->error(sprintf('failed to access skyscanner api, failed to decode json: http code %s, error code: %s, %s', $response->httpCode, $response->errorCode, substr($response->body, 0, 2048)));

            return null;
        }

        if (empty($json['sessionToken'])) {
            $this->logger->error(sprintf('failed to access skyscanner api, unknown json: %s', substr($response->body, 0, 2048)));

            return null;
        }

        return $json['sessionToken'];
    }

    private function searchPoll(string $sessionToken): ?array
    {
        $request = new \HttpDriverRequest(
            'https://partners.api.skyscanner.net/apiservices/v3/flights/live/search/poll/' . $sessionToken,
            'POST',
            [],
            [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->skyscannerApiKey,
            ]
        );
        $sleep = false;

        for ($try = 0; $try < 10; $try++) {
            $json = null;

            if ($sleep) {
                $sleep = false;
                sleep($try ** 3);
            }
            $response = $this->httpDriver->request($request);

            if ($response->httpCode !== 200) {
                $this->logger->warning(sprintf('failed to access skyscanner api, response code: %s', $response->httpCode));

                continue;
            }

            $json = json_decode($response->body, true);

            if (is_null($json)) {
                $this->logger->warning(sprintf('failed to access skyscanner api, failed to decode json: http code %s, error code: %s, %s', $response->httpCode, $response->errorCode, substr($response->body, 0, 2048)));
                $sleep = true;

                continue;
            }

            $status = $json['status'] ?? null;

            if ($status === self::RESULT_STATUS_INCOMPLETE) {
                $this->logger->info('skyscanner api, RESULT_STATUS_INCOMPLETE, retry');
                $sleep = true;

                continue;
            }

            break;
        }

        if (!isset($json)) {
            $this->logger->warning('failed to query skyscanner');

            return null;
        }

        if (!empty($json['status']) && $json['status'] === self::RESULT_STATUS_COMPLETE) {
            return $json['content']['results'] ?? [];
        }

        return null;
    }

    private function convertCabinClass(string $class): ?string
    {
        switch ($class) {
            case Constants::CLASS_BASIC_ECONOMY:
            case Constants::CLASS_ECONOMY:
                return self::CABIN_CLASS_ECONOMY;

            case Constants::CLASS_ECONOMY_PLUS:
            case Constants::CLASS_PREMIUM_ECONOMY:
                return self::CABIN_CLASS_PREMIUM_ECONOMY;

            case Constants::CLASS_BUSINESS:
                return self::CABIN_CLASS_BUSINESS;

            case Constants::CLASS_FIRST:
                return self::CABIN_CLASS_FIRST;
        }

        return null;
    }

    private function getPrices(array $data, int $adults): array
    {
        $airlines = \array_map(fn (array $carrier) => $carrier['iata'] ?? null, $data['carriers'] ?? []);
        $airports = \array_filter(\array_map(function (array $place) {
            if ($place['type'] === self::PLACE_TYPE_AIRPORT && !empty($place['iata'])) {
                return $place['iata'];
            }

            return null;
        }, $data['places'] ?? []));
        $segmentIds = \array_map(fn (array $leg) => $leg['segmentIds'] ?? [], $data['legs'] ?? []);
        $segments = $data['segments'] ?? [];
        $excludedAgents = [];

        foreach ($data['agents'] ?? [] as $agentId => $agent) {
            if ($agent['type'] === self::AGENT_TYPE_AIRLINE && in_array($agent['name'], ['KLM', 'Air France'])) {
                $excludedAgents[] = $agentId;
            }
        }

        $prices = [];

        foreach ($data['itineraries'] ?? [] as $itinerary) {
            $legIds = $itinerary['legIds'] ?? [];
            $route = [];
            $rawRoutes = [];

            foreach ($legIds as $legId) {
                foreach ($segmentIds[$legId] ?? [] as $segmentId) {
                    $segment = $segments[$segmentId] ?? [];

                    $departureDateTime = $this->createDateTime(
                        $segment['departureDateTime']['year'] ?? null,
                        $segment['departureDateTime']['month'] ?? null,
                        $segment['departureDateTime']['day'] ?? null,
                        $segment['departureDateTime']['hour'] ?? null,
                        $segment['departureDateTime']['minute'] ?? null,
                        $segment['departureDateTime']['second'] ?? null,
                    );
                    $arrivalDateTime = $this->createDateTime(
                        $segment['arrivalDateTime']['year'] ?? null,
                        $segment['arrivalDateTime']['month'] ?? null,
                        $segment['arrivalDateTime']['day'] ?? null,
                        $segment['arrivalDateTime']['hour'] ?? null,
                        $segment['arrivalDateTime']['minute'] ?? null,
                        $segment['arrivalDateTime']['second'] ?? null,
                    );
                    $origin = $airports[$segment['originPlaceId'] ?? null] ?? null;
                    $destination = $airports[$segment['destinationPlaceId'] ?? null] ?? null;
                    $marketingCarrier = $airlines[$segment['marketingCarrierId'] ?? null] ?? null;
                    $operatingCarrier = $airlines[$segment['operatingCarrierId'] ?? null] ?? null;

                    if (
                        is_null($departureDateTime)
                        || is_null($arrivalDateTime)
                        || empty($origin)
                        || empty($destination)
                        || empty($marketingCarrier)
                    ) {
                        continue 3;
                    }

                    $rawRoute = array_merge($segment, [
                        'departure' => $departureDateTime,
                        'arrival' => $arrivalDateTime,
                        'marketingCarrier' => $marketingCarrier,
                        'operatingCarrier' => $operatingCarrier,
                        'origin' => $origin,
                        'destination' => $destination,
                    ]);
                    $rawRoutes[] = $rawRoute;
                    $route[] = new ResultRoute(
                        $origin,
                        $destination,
                        $departureDateTime,
                        $arrivalDateTime,
                        $marketingCarrier,
                        $segment['marketingFlightNumber'],
                        null,
                        $operatingCarrier
                    );
                }
            }

            if (!empty($itinerary['pricingOptions'][0]['price']['amount'] ?? null)) {
                $priceOptions = $this->filterPriceOptions($itinerary['pricingOptions'], $excludedAgents);
                $prices[] = new Price(
                    self::SOURCE_ID,
                    round($this->getPrice($priceOptions[0]['price']['amount'], $priceOptions[0]['price']['unit']), 2),
                    $route,
                    it($priceOptions[0]['items'] ?? [])
                        ->map(fn (array $item) => $item['deepLink'] ?? null)
                        ->first(),
                    array_merge($priceOptions[0], ['Routes' => $rawRoutes]));
            }
        }

        return $prices;
    }

    private function createDateTime(
        ?int $year,
        ?int $month,
        ?int $day,
        ?int $hour,
        ?int $minute,
        ?int $second
    ): ?int {
        if (
            !is_null($year)
            && !is_null($month)
            && !is_null($day)
            && !is_null($hour)
            && !is_null($minute)
            && !is_null($second)
        ) {
            $dateTime = \mktime($hour, $minute, $second, $month, $day, $year);

            if ($dateTime === false) {
                return null;
            }

            return $dateTime;
        }

        return null;
    }

    private function getPrice(?string $amount, ?string $unit): float
    {
        if (!is_numeric($amount)) {
            return 0;
        }

        $amount = (int) $amount;

        switch ($unit) {
            case self::PRICE_UNIT_CENTI:
                return $amount / 100;

            case self::PRICE_UNIT_MILLI:
                return $amount / 1000;

            case self::PRICE_UNIT_MICRO:
                return $amount / 1000000;

            default:
                return $amount;
        }
    }

    private function unionPriceSegments(array $prices, array $segmentPrices, int $bestPriceCount): array
    {
        $newPrices = [];

        for ($i = 0; $i < $bestPriceCount; $i++) {
            if (!isset($segmentPrices[$i])) {
                break;
            }

            if (empty($prices)) {
                $newPrices = array_slice($segmentPrices, 0, $bestPriceCount);

                break;
            }

            /** @var Price $price */
            foreach ($prices as $price) {
                $newPrices[] = new Price(
                    self::SOURCE_ID,
                    $price->price + $segmentPrices[$i]->price,
                    array_merge($price->routes, $segmentPrices[$i]->routes),
                    null,
                    [$price->rawData, $segmentPrices[$i]->rawData]
                );
            }
        }

        return $newPrices;
    }

    private function filterPriceOptions(array $pricingOptions, array $excludedAgents): array
    {
        $result = array_values(array_filter($pricingOptions, function (array $option) use ($excludedAgents) {
            return count(array_intersect($excludedAgents, $option['agentIds'])) === 0;
        }));

        if (count($result) === 0) {
            return $pricingOptions; // all agents excluded, we must select someone
        }

        return $result;
    }
}
