<?php

namespace AwardWallet\MainBundle\Service\RA\Hotel;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Api
{
    protected const API_URL = 'https://ra-hotels.awardwallet.com';

    protected LoggerInterface $logger;

    protected \Memcached $memcached;

    protected Connection $connection;

    protected RouterInterface $router;

    protected \HttpDriverInterface $curlDriver;

    protected string $hotelsApiKey;

    protected bool $enabledApiCallback;

    public function __construct(
        LoggerInterface $logger,
        \Memcached $memcached,
        Connection $connection,
        RouterInterface $router,
        \HttpDriverInterface $curlDriver,
        string $hotelsApiKey,
        Config $config
    ) {
        $this->logger = $logger;
        $this->memcached = $memcached;
        $this->connection = $connection;
        $this->router = $router;
        $this->curlDriver = $curlDriver;
        $this->hotelsApiKey = $hotelsApiKey;
        $this->enabledApiCallback = $config->enabledApiCallback();
    }

    /**
     * @param string[] $providers
     */
    public function search(
        array $providers,
        ?string $destination,
        ?string $placeId,
        \DateTime $checkInDate,
        \DateTime $checkOutDate,
        int $numberOfRooms,
        int $numberOfAdults,
        int $numberOfKids,
        string $channelName,
        string $searchId
    ): array {
        $baseQuery = [
            'destination' => $destination,
            'checkInDate' => $checkInDate->format('Y-m-d'),
            'checkOutDate' => $checkOutDate->format('Y-m-d'),
            'numberOfRooms' => $numberOfRooms,
            'numberOfAdults' => $numberOfAdults,
            'numberOfKids' => $numberOfKids,
            'downloadPreview' => true,
            'priority' => 9,
        ];

        if ($this->enabledApiCallback) {
            $baseQuery['callbackUrl'] = 'https://awardwallet.com' . $this->router->generate('aw_hotels_data_callback');
        }

        $results = [
            'requests' => [],
            'cached' => [],
            'steps' => 0,
            'processed' => 0,
            'errors' => [],
        ];
        $start = microtime(true);

        foreach ($providers as $provider) {
            $this->logInfo('searching provider ' . $provider);
            $results['processed']++;
            $cacheKey = $this->getCacheKey(
                $provider,
                $placeId ?? $destination,
                $checkInDate,
                $checkOutDate,
                $numberOfRooms,
                $numberOfAdults,
                $numberOfKids,
                true
            );

            $cachedHotels = $this->memcached->get($cacheKey);

            if (!empty($cachedHotels)) {
                $this->logInfo('got cached result');
                $results['cached'][] = [
                    'channelName' => $channelName,
                    'requestId' => null,
                    'searchId' => $searchId,
                    'provider' => $provider,
                    'hotels' => $cachedHotels,
                ];
                $results['steps']++;

                continue;
            }

            $query = array_merge($baseQuery, [
                'provider' => $provider,
                'userData' => json_encode([
                    'provider' => $provider,
                    'cacheKey' => $cacheKey,
                    'channelName' => $channelName,
                    'searchId' => $searchId,
                ]),
            ]);

            try {
                $this->logInfo('calling api');
                $response = $this->call(
                    Request::METHOD_POST,
                    '/v1/search',
                    json_encode($query)
                );
                $this->logInfo('decoding response');
                $data = json_decode($response->body, true);

                if (!is_array($data) || !isset($data['requestId'])) {
                    $this->logInfo('invalid response');

                    throw new ApiRequestException('response is not an array', ['request' => '/v1/search', 'query' => $query, 'response' => $response->body]);
                }

                $results['requests'][] = $data['requestId'];
                $results['steps']++;
            } catch (ApiRequestException $e) {
                $this->logError($e->getMessage(), $e->getContext());
                $results['errors'][] = [
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ];

                continue;
            }
        }

        $totalTime = round(microtime(true) - $start, 4);

        $this->logInfo(sprintf('queries sent: %d, total time: %.4f', count($results['requests']), $totalTime), [
            'baseQuery' => $baseQuery,
            'providers' => $providers,
            'errors' => $results['errors'],
            'processed' => $results['processed'],
            'cachedCount' => count($results['cached']),
        ]);

        return $results;
    }

    public function getResults(string $requestId): ?array
    {
        try {
            $response = $this->call(Request::METHOD_GET, '/v1/getResults/' . $requestId);
            $data = json_decode($response->body, true);
            $context = [
                'request' => '/v1/getResults/' . $requestId,
                'response' => $response->body,
                'json' => $data,
            ];

            if (!is_array($data)) {
                throw new ApiRequestException('response is not an array', $context);
            }

            $state = $data['state'] ?? null;

            if ($state !== 'success') {
                return null;
            }

            $userData = $data['userData'] ?? null;
            $userDataArray = $userData ? json_decode($userData, true) : null;

            if (!is_array($userDataArray)) {
                throw new ApiRequestException('invalid user data', $context);
            }

            if (
                empty($userDataArray['cacheKey'] ?? null)
                || empty($userDataArray['provider'] ?? null)
                || empty($userDataArray['channelName'] ?? null)
                || empty($userDataArray['searchId'] ?? null)
            ) {
                throw new ApiRequestException('invalid user data fields', array_merge($context, ['userData' => $userDataArray]));
            }

            $hotels = $data['hotels'] ?? null;

            if (!is_array($hotels) || empty($hotels)) {
                throw new ApiRequestException('invalid hotels data', $context);
            }

            return [
                'userData' => $userDataArray,
                'hotels' => $hotels,
            ];
        } catch (ApiRequestException $e) {
            $this->logError($e->getMessage(), $e->getContext());

            return null;
        }
    }

    public function getParserList(): array
    {
        if (!empty($list = $this->memcached->get('RAHotelParserList'))) {
            return $list;
        }

        try {
            $response = $this->call(Request::METHOD_GET, '/v1/providers/list');
            $data = json_decode($response->body, true);
            $context = [
                'request' => '/v1/providers/list',
                'response' => $response->body,
            ];

            if (!is_array($data)) {
                throw new ApiRequestException('response is not an array', $context);
            }

            if (!isset($data['providers']) || !is_array($data['providers'])) {
                throw new ApiRequestException('response does not contain providers list', $context);
            }

            $list = it($data['providers'])
                ->map(function (array $provider) {
                    $provider['displayName'] = htmlspecialchars_decode($provider['displayName']);
                    $provider['shortName'] = htmlspecialchars_decode($provider['shortName']);

                    if (false !== strpos($provider['shortName'], 'IHG Hotels')) {
                        $provider['shortName'] = 'IHG';
                    }

                    return $provider;
                })
                ->toArray();
            $list = array_column($list, null, 'code');
            $providersIds = $this->connection->fetchAllKeyValue(
                'SELECT Code, ProviderID FROM Provider WHERE Code IN (:codes)',
                ['codes' => array_keys($list)],
                ['codes' => Connection::PARAM_STR_ARRAY]
            );

            foreach ($providersIds as $code => $id) {
                $list[$code]['providerId'] = (int) $id;
            }

            // cache for 24 hours
            $this->memcached->set('RAHotelParserList', $list, 3600 * 24);
        } catch (ApiRequestException $e) {
            $this->logError($e->getMessage(), $e->getContext());

            return [];
        }

        return $list;
    }

    protected function call(string $method, string $path, ?string $data = null): \HttpDriverResponse
    {
        $url = self::API_URL . $path;
        $this->logInfo("calling api $method $url");
        $response = $this->curlDriver->request(new \HttpDriverRequest($url, $method, $data, [
            'User-Agent' => 'AwardWallet',
            'Content-Type' => 'application/json',
            'X-Authentication' => $this->hotelsApiKey,
        ]));
        $isOk = $response->httpCode >= 200 && $response->httpCode < 300;
        $this->logInfo("request finished: ($response->httpCode) " . ($response->headers['X-Request-Id'] ?? "unknown"));

        if (!$isOk) {
            throw new ApiRequestException('api request failed', ['url' => $url, 'method' => $method, 'data' => $data, 'responseCode' => $response->httpCode]);
        }

        return $response;
    }

    protected function getCacheKey(
        string $provider,
        string $destination,
        \DateTime $checkInDate,
        \DateTime $checkOutDate,
        int $numberOfRooms,
        int $numberOfAdults,
        int $numberOfKids,
        bool $downloadPreview
    ): string {
        return 'hotelsQueryRequest' .
            hash('sha256', json_encode([
                'provider' => $provider,
                'destination' => $destination,
                'checkInDate' => $checkInDate->format('Y-m-d'),
                'checkOutDate' => $checkOutDate->format('Y-m-d'),
                'numberOfRooms' => $numberOfRooms,
                'numberOfAdults' => $numberOfAdults,
                'numberOfKids' => $numberOfKids,
                'downloadPreview' => $downloadPreview,
            ]));
    }

    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[RA Hotels Info] %s', $message), $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error(sprintf('[RA Hotels Error] %s', $message), $context);
    }
}
