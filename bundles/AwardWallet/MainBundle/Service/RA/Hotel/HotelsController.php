<?php

namespace AwardWallet\MainBundle\Service\RA\Hotel;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use AwardWallet\MainBundle\Service\Hotels\Thumbnail;
use AwardWallet\MainBundle\Service\RA\Hotel\DTO\Hotel;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/hotel-search")
 */
class HotelsController extends AbstractController
{
    private Api $api;

    private Thumbnail $thumbnail;

    private Connection $connection;

    private LoggerInterface $logger;

    private LocalizeService $localizeService;

    private RouterInterface $router;

    private \Memcached $memcached;

    private ClientInterface $client;

    public function __construct(
        ApiResolver $apiResolver,
        Thumbnail $thumbnail,
        Connection $connection,
        LoggerInterface $logger,
        LocalizeService $localizeService,
        RouterInterface $router,
        \Memcached $memcached,
        ClientInterface $client
    ) {
        $this->api = $apiResolver->getApi();
        $this->thumbnail = $thumbnail;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->localizeService = $localizeService;
        $this->router = $router;
        $this->memcached = $memcached;
        $this->client = $client;
    }

    /**
     * @Route("/", name="aw_hotels_index", options={"expose"=true})
     * @Security("is_granted('ROLE_STAFF')")
     * @Template("@Module/RA/Hotel/Template/index.html.twig")
     */
    public function indexAction(AwTokenStorageInterface $awTokenStorage, ClientInterface $client, Config $config, Environment $twigEnv): array
    {
        /** @var Usr $user */
        $user = $awTokenStorage->getUser();

        $twigEnv->addGlobal('webpack', true);

        return [
            'providers' => $this->getProvidersListWithBalances($user),
            'centrifuge' => $this->getCentrifugeConfig($user, $client),
            'debug' => !$config->enabledApiCallback(),
        ];
    }

    /**
     * @Route("/data/search", name="aw_hotels_data_search", options={"expose"=true}, methods={"POST"})
     * @Security("is_granted('ROLE_STAFF')")
     * @JsonDecode
     */
    public function searchAction(Request $request): JsonResponse
    {
        try {
            // check providers
            $providers = $request->request->get('providers');

            if (!is_array($providers) || empty($providers)) {
                throw new InvalidInputException('Providers is required');
            }

            if (!it($providers)->all('is_string')) {
                throw new InvalidInputException('Providers must contain only strings');
            }

            $allProviders = array_keys($this->api->getParserList());

            if (!empty($invalidProviders = array_diff($providers, $allProviders))) {
                throw new InvalidInputException(sprintf('Invalid providers: "%s"', implode('", "', $invalidProviders)));
            }

            // check destination
            $destination = $request->request->get('destination');

            if (!is_string($destination) || empty(trim($destination))) {
                throw new InvalidInputException('Destination is required');
            }

            $destination = trim($destination);

            // check place_id
            $placeId = $request->request->get('place_id');

            if (!is_string($placeId) || empty(trim($placeId))) {
                $placeId = null;
            } else {
                $placeId = trim($placeId);
            }

            // check check-in date
            $checkInDate = $this->getDateFromRequest($request, 'checkIn', 'Check-in');

            // check check-out date
            $checkOutDate = $this->getDateFromRequest($request, 'checkOut', 'Check-out');

            if ($checkOutDate <= $checkInDate) {
                throw new InvalidInputException('Check-out date must be greater than check-in date');
            }

            // check number of rooms
            $numberOfRooms = $this->getIntFromRequest($request, 'numberOfRooms', 'Number of rooms', 1, 9);

            // check number of adults
            $numberOfAdults = $this->getIntFromRequest($request, 'numberOfAdults', 'Number of adults', 1, 9);

            // check number of kids
            $numberOfKids = $this->getIntFromRequest($request, 'numberOfKids', 'Number of kids', 0, 9, 0);

            // check channel name
            $channelName = $request->request->get('channelName');

            if (!is_string($channelName) || empty(trim($channelName))) {
                throw new InvalidInputException('Channel name is required');
            }

            $channelName = trim($channelName);

            // check search id
            $searchId = $request->request->get('searchId');

            if (!is_string($searchId) || empty(trim($searchId))) {
                throw new InvalidInputException('Search ID is required');
            }

            $result = $this->api->search(
                $providers,
                $destination,
                $placeId,
                $checkInDate,
                $checkOutDate,
                $numberOfRooms,
                $numberOfAdults,
                $numberOfKids,
                $channelName,
                $searchId
            );
            $cachedResults = $result['cached'];
            $processed = $result['processed'];
            $steps = $result['steps'];
            $response = [
                'requests' => $result['requests'],
                'steps' => $steps,
            ];
            $responseCode = Response::HTTP_OK;

            if ($steps === 0) {
                $this->logger->critical('RA Hotels search returned 0 steps', [
                    'processed' => $processed,
                    'errors' => $result['errors'],
                ]);

                $responseCode = Response::HTTP_SERVICE_UNAVAILABLE;
                $response = [
                    'error' => 'Service is unavailable',
                ];
            } elseif ($steps !== $processed) {
                $this->logger->critical('RA Hotels search returned less processed steps than expected', [
                    'processed' => $processed,
                    'steps' => $steps,
                    'errors' => $result['errors'],
                ]);

                $response['partial'] = true;

                if (count($result['errors']) > 0) {
                    $response['errors'] = $result['errors'];
                }
            }

            foreach ($cachedResults as $cached) {
                $this->publishHotels(
                    $cached['channelName'],
                    $cached['requestId'],
                    $cached['searchId'],
                    $cached['provider'],
                    array_map(
                        fn (array $hotel) => $this->mapHotel($hotel, $cached['provider'], false),
                        $cached['hotels']
                    )
                );
            }

            return new JsonResponse($response, $responseCode);
        } catch (InvalidInputException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/data/callback", name="aw_hotels_data_callback", methods={"POST"})
     */
    public function callbackAction(Request $request, string $raHotelCallbackPassword): Response
    {
        $checkAccess = $request->getUser() === 'awardwallet' && $request->getPassword() === $raHotelCallbackPassword;

        if (!$checkAccess) {
            return new Response(Response::$statusTexts[Response::HTTP_FORBIDDEN], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $baseContext = [
            'request' => '/hotels/data/callback',
            'response' => $request->getContent(),
            'json' => $data,
        ];

        if (!is_array($data) || !is_array($data['response'] ?? null)) {
            $this->logError('invalid data, not an array', $baseContext);
        }

        foreach ($data['response'] as $response) {
            $baseItemContext = array_merge($baseContext, [
                'json' => $response,
            ]);

            $requestId = $response['requestId'] ?? null;

            if (empty($requestId)) {
                $this->logError('invalid request id', $baseItemContext);

                continue;
            }

            $state = $response['state'] ?? null;
            $message = $response['message'] ?? null;

            if ($state !== 'success') {
                $this->logInfo(sprintf('state is not success: "%s", message: "%s"', $state, $message), $baseItemContext);

                continue;
            }

            $userData = $response['userData'] ?? null;
            $userDataArray = $userData ? json_decode($userData, true) : null;

            if (!is_array($userDataArray)) {
                $this->logError('invalid user data', $baseItemContext);

                continue;
            }

            if (
                empty($userDataArray['cacheKey'] ?? null)
                || empty($userDataArray['provider'] ?? null)
                || empty($userDataArray['channelName'] ?? null)
                || empty($userDataArray['searchId'] ?? null)
            ) {
                $this->logError('invalid user data fields', array_merge($baseItemContext, [
                    'userData' => $userDataArray,
                ]));

                continue;
            }

            $hotels = $response['hotels'] ?? null;

            if (!is_array($hotels) || empty($hotels)) {
                $this->logError('invalid hotels data', $baseItemContext);

                continue;
            }

            $results = [];

            foreach ($hotels as $hotel) {
                $results[] = $this->mapHotel($hotel, $userDataArray['provider']);
            }

            // cache hotels for 5 minutes
            $this->memcached->set($userDataArray['cacheKey'], $hotels, 60 * 5);
            $this->publishHotels(
                $userDataArray['channelName'],
                $requestId,
                $userDataArray['searchId'],
                $userDataArray['provider'],
                $results
            );
        }

        return new Response('OK');
    }

    /**
     * @Route(
     *     "/data/getResult/{requestId}",
     *     name="aw_hotels_data_getresult",
     *     options={"expose"=true},
     *     requirements={"requestId"=".+"}
     * )
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function getResultAction(string $requestId): JsonResponse
    {
        $response = $this->api->getResults($requestId);

        if (is_array($response)) {
            $userData = $response['userData'];
            $hotels = $response['hotels'];
            $results = [];

            foreach ($hotels as $hotel) {
                $results[] = $this->mapHotel($hotel, $userData['provider']);
            }

            // cache hotels for 5 minutes
            $this->memcached->set($userData['cacheKey'], $hotels, 60 * 5);
            $this->publishHotels(
                $userData['channelName'],
                $requestId,
                $userData['searchId'],
                $userData['provider'],
                $results
            );
        }

        return new JsonResponse([]);
    }

    /**
     * @Route("/data/thumbnial/{key}", name="aw_hotels_thumbnail")
     * @Security("is_granted('ROLE_USER')")
     */
    public function thumbnailAction(Request $request, string $key): Response
    {
        return $this->thumbnail->getResponse(
            $key,
            UserAgentUtils::isBrowserWebpCompatible(
                $request->headers->get('user-agent'),
                $request->headers->get('accept')
            )
        );
    }

    private function publishHotels(
        string $channelName,
        ?string $requestId,
        string $searchId,
        string $provider,
        array $hotels
    ): void {
        $this->client->publish($channelName, [
            'requestId' => $requestId,
            'searchId' => $searchId,
            'provider' => $provider,
            'hotels' => $hotels,
        ]);
    }

    private function getProvidersListWithBalances(Usr $user): array
    {
        $providers = $this->api->getParserList();
        $accounts = $this->connection->fetchAllKeyValue(
            'SELECT p.Code, SUM(a.Balance) AS Balance
            FROM Account a
            JOIN Provider p ON p.ProviderID = a.ProviderID
            WHERE a.UserID = :userId AND p.Code IN (:providers)
            GROUP BY p.Code',
            [
                'userId' => $user->getId(),
                'providers' => array_column($providers, 'code'),
            ],
            [
                'userId' => \PDO::PARAM_INT,
                'providers' => Connection::PARAM_STR_ARRAY,
            ]
        );

        foreach ($providers as $code => &$provider) {
            unset($provider['providerId']);
            $provider['balance'] = $accounts[$code]['Balance'] ?? 0;
        }

        return $providers;
    }

    private function getCentrifugeConfig(Usr $user, ClientInterface $client): array
    {
        $centrifugeConfig = $client->getClientData();
        $centrifugeConfig['channelName'] = UserMessaging::getChannelName(
            sprintf('raHotelsResult%s%d', bin2hex(random_bytes(4)), time()),
            $user->getId()
        );

        return $centrifugeConfig;
    }

    /**
     * @throws InvalidInputException
     */
    private function getDateFromRequest(Request $request, string $key, string $name): \DateTime
    {
        $date = $request->request->get($key);

        if (!is_string($date) || empty($date)) {
            throw new InvalidInputException(sprintf('%s date is required', $name));
        }

        $date = date_create_from_format('Y-m-d', $date);

        if (!$date) {
            throw new InvalidInputException(sprintf('Invalid %s date', $name));
        }

        return $date;
    }

    /**
     * @throws InvalidInputException
     */
    private function getIntFromRequest(Request $request, string $key, string $name, int $min, int $max, ?int $default = null): int
    {
        $value = $request->request->get($key);
        $isRequired = is_null($default);

        if (!is_numeric($value) || empty($value)) {
            if (!$isRequired) {
                return $default;
            }

            throw new InvalidInputException(sprintf('%s is required', $name));
        }

        $value = (int) $value;

        if ($value < $min || $value > $max) {
            throw new InvalidInputException(sprintf('%s must be between %d and %d', $name, $min, $max));
        }

        return $value;
    }

    private function logInfo(string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[RA Hotels Info] %s', $message), $context);
    }

    private function logError(string $message, array $context = []): void
    {
        $this->logger->error(sprintf('[RA Hotels Error] %s', $message), $context);
    }

    private function mapHotel(array $hotel, string $provider, bool $processThumbnail = true): Hotel
    {
        $key = sha1($hotel['address']['lat'] . '_' . $hotel['address']['lng']);
        $thumbKey = isset($hotel['preview']) ? $key : 'default';

        if ($thumbKey !== 'default' && $processThumbnail) {
            $this->thumbnail->processing($thumbKey, base64_decode($hotel['preview']));
        }

        $entity = new Hotel();
        $entity->key = $key;
        $entity->name = $hotel['name'];
        $entity->checkInDate = $hotel['checkInDate'];
        $entity->checkOutDate = $hotel['checkOutDate'];
        $entity->description = $hotel['hotelDescription'] ?? null;
        $entity->numberOfNights = $hotel['numberOfNights'];
        $entity->pointsPerNight = $hotel['pointsPerNight'] ?? null;
        $entity->pointsPerNightFormatted = isset($hotel['pointsPerNight']) ? $this->localizeService->formatNumber($hotel['pointsPerNight']) : null;
        $entity->cashPerNight = $hotel['cashPerNight'] ?? null;
        $entity->cashPerNightFormatted =
            isset($hotel['cashPerNight'])
                ? $this->localizeService->formatCurrency($hotel['cashPerNight'], $hotel['originalCurrency'] ?? 'USD')
                : null;
        $entity->address = $hotel['address']['text'];
        $entity->thumb = $this->router->generate('aw_hotels_thumbnail', ['key' => $thumbKey]);
        $entity->rating = $hotel['rating'] ?? null;
        $entity->ratingFormatted = isset($hotel['rating']) ? $this->localizeService->formatNumber($hotel['rating'], 2) : null;
        $entity->distance = $hotel['distance'] ?? null;
        $entity->distanceFormatted = isset($hotel['distance']) ? $this->localizeService->formatNumber($hotel['distance'], 2) : null;
        $entity->providercode = $provider;

        return $entity;
    }
}
