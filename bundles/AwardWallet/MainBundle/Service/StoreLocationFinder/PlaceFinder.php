<?php

namespace AwardWallet\MainBundle\Service\StoreLocationFinder;

use AwardWallet\MainBundle\Service\Cache\Memoizer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;

class PlaceFinder
{
    protected const GOOGLE_GEO_API_NEARBY_PLACES_URL = "https://maps.googleapis.com/maps/api/place/nearbysearch/json";
    protected const GOOGLE_GEO_API_ADDRESSES_URL = "https://maps.googleapis.com/maps/api/place/textsearch/json";

    protected const STATUS_OK = 'OK';
    protected const STATUS_ZERO_RESULTS = 'ZERO_RESULTS';
    protected const STATUS_OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';
    protected const STATUS_REQUEST_DENIED = 'REQUEST_DENIED';
    protected const STATUS_INVALID_REQUEST = 'INVALID_REQUEST';
    protected const STATUS_UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    private Client $guzzleClient;

    private string $googleApiKey;

    private LoggerInterface $logger;

    private Memoizer $memoizer;

    public function __construct(
        Client $guzzleClientDefault,
        LoggerInterface $statLogger,
        string $googleApiKey,
        Memoizer $memoizer
    ) {
        $this->guzzleClient = $guzzleClientDefault;
        $this->googleApiKey = $googleApiKey;
        $this->logger = $statLogger;
        $this->memoizer = $memoizer;
    }

    public function getNearbyPlacesByNameIter(
        string $lat,
        string $lng,
        int $radius,
        array $optionalParams = [],
        array $logContext = []
    ): \Iterator {
        $query = array_merge($optionalParams, [
            'location' => "{$lat},{$lng}",
            'radius' => $radius,
        ]);

        return $this->doGetPlacesIter(self::GOOGLE_GEO_API_NEARBY_PLACES_URL, $query, $logContext);
    }

    public function getPlacesByAddressIter(string $address, array $logContext = []): \Iterator
    {
        return $this->doGetPlacesIter(
            self::GOOGLE_GEO_API_ADDRESSES_URL,
            [
                'query' => $address,
            ],
            $logContext
        );
    }

    protected function doGetPlacesIter(string $baseUrl, array $queryMixin, array $logContext = []): \Iterator
    {
        do {
            $searchParams = array_merge(
                $queryMixin,
                isset($nextPageToken) ? ['pagetoken' => $nextPageToken] : []
            );

            $url = $baseUrl . '?' . http_build_query(array_merge(
                ['key' => $this->googleApiKey],
                $searchParams
            ));
            $nextPageToken = null;

            ['status' => $status, 'body' => $body] = $this->memoizer->memoizeWithLog(
                'places_finder_4',
                SECONDS_PER_DAY * 30, // one month
                function (string $url) use ($searchParams, $logContext) {
                    foreach (range(1, 3) as $retry) {
                        $this->logger->info('Places API search request: "' . json_encode($searchParams) . '"', $logContext);

                        try {
                            $resposne = $this->guzzleClient->get($url);
                            $lastException = null;

                            break;
                        } catch (ConnectException $e) {
                            if (3 === $retry) {
                                break;
                            }

                            sleep(0.5 * $retry);
                            $lastException = $e;
                        }
                    }

                    if ($lastException) {
                        throw new \RuntimeException('Failed Google Places API communication.', 0, $lastException);
                    }

                    return [
                        'body' => @json_decode((string) $resposne->getBody(), true),
                        'status' => $resposne->getStatusCode(),
                    ];
                },
                $url
            );

            if (
                (200 === $status)
                && is_array($body)
                && isset($body['status'])
            ) {
                switch ($body['status']) {
                    case self::STATUS_OVER_QUERY_LIMIT: throw new \RuntimeException('Google Places limit exceeded');

                    case self::STATUS_REQUEST_DENIED: throw new \RuntimeException('Google Places access denied');

                    case self::STATUS_UNKNOWN_ERROR: // TODO: throw
                    case self::STATUS_INVALID_REQUEST: // TODO: throw
                    case self::STATUS_ZERO_RESULTS:
                        $this->logger->info('No places found', $logContext);

                        yield from [];

                        break;

                    case self::STATUS_OK:
                        if (
                            isset($body['results'])
                            && is_array($body['results'])
                        ) {
                            $this->logger->info(count($body['results']) . ' place(s) found', $logContext);

                            $missingFields = false;

                            foreach ($body['results'] as $place) {
                                if (
                                    isset(
                                        $place['name'],
                                        $place['geometry']['location']['lat'],
                                        $place['geometry']['location']['lng']
                                    )
                                    && (
                                        isset($place['vicinity'])
                                        || isset($place['formatted_address'])
                                    )
                                ) {
                                    yield $place;
                                } else {
                                    $missingFields = true;
                                }
                            }

                            if ($missingFields) {
                                $this->logger->warning('missing fields', $logContext);
                            }
                        }

                        if (isset($body['next_page_token'])) {
                            $nextPageToken = $body['next_page_token'];
                        }

                        break;

                    default: throw new \RuntimeException("Unknown response status {$body['status']}");
                }
            }
        } while (isset($nextPageToken));
    }
}
