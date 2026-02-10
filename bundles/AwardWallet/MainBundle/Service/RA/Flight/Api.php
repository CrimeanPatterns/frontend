<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Service\AccountBalanceCombinator\Combinator;
use AwardWallet\MainBundle\Service\RA\Flight\DTO\ApiSearchRequest;
use AwardWallet\MainBundle\Service\RA\Flight\DTO\ApiSearchResult;
use AwardWallet\MainBundle\Service\RA\Flight\DTO\ParserSelectorRequest;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Api
{
    private LoggerInterface $logger;

    private Connection $connection;

    private ApiCommunicator $apiCommunicator;

    private \Memcached $memcached;

    private EntityManagerInterface $em;

    private RouterInterface $router;

    private RouteParserSelector $routeParserSelector;

    private Combinator $combinator;

    private FlightDealSubscriber $flightDealSubscriber;

    private RequestProgressTracker $requestProgressTracker;

    public function __construct(
        LoggerFactory $loggerFactory,
        Connection $connection,
        ApiCommunicator $raApiCommunicator,
        \Memcached $memcached,
        EntityManagerInterface $em,
        RouterInterface $router,
        RouteParserSelector $routeParserSelector,
        Combinator $combinator,
        FlightDealSubscriber $flightDealSubscriber,
        RequestProgressTracker $requestProgressTracker
    ) {
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor([
            'class' => 'RAFlightApi',
        ]));
        $this->connection = $connection;
        $this->apiCommunicator = $raApiCommunicator;
        $this->memcached = $memcached;
        $this->em = $em;
        $this->router = $router;
        $this->routeParserSelector = $routeParserSelector;
        $this->combinator = $combinator;
        $this->flightDealSubscriber = $flightDealSubscriber;
        $this->requestProgressTracker = $requestProgressTracker;
    }

    /**
     * @throws SearchException
     */
    public function search(int $queryId): ApiSearchResult
    {
        $flightSearchQuery = $this->em->getRepository(RAFlightSearchQuery::class)->find($queryId);

        if (!$flightSearchQuery) {
            $this->logger->error($error = sprintf('query not found, id: %d', $queryId));

            throw new SearchException($error);
        }

        if ($flightSearchQuery->isAutoCreated() && !empty($mileValue = $flightSearchQuery->getMileValue())) {
            $syncQueryId = $this->flightDealSubscriber->syncByMileValue($mileValue->getId());

            if (is_null($syncQueryId)) {
                $this->logger->info($error = sprintf('query is not valid, id: %d', $queryId));

                throw new SearchException($error);
            }

            if ($syncQueryId !== $queryId) {
                $this->logger->info($error = sprintf('query is not sync, id: %d', $queryId));

                throw new SearchException($error);
            }

            $this->em->refresh($flightSearchQuery);
        }

        if ($flightSearchQuery->isDeleted()) {
            $this->logger->info($error = sprintf('query is deleted, id: %d', $queryId));

            throw new SearchException($error);
        }

        $this->cleanState($flightSearchQuery);

        /**
         * @var ApiSearchRequest[] $requests
         */
        $requests = [];
        $dates = [];
        $from = clone $flightSearchQuery->getDepDateFrom();
        $to = clone $flightSearchQuery->getDepDateTo();
        $nowTs = strtotime('today');

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        do {
            if ($from->getTimestamp() >= $nowTs) {
                $dates[] = clone $from;
            }

            $from->modify('+1 day');
        } while ($from <= $to);

        if (empty($dates)) {
            $this->logger->error($error = sprintf('query has no dates, id: %d', $queryId));
            $this->addQueryStateError($flightSearchQuery, 'Query has no dates');

            throw new SearchException($error);
        }

        $isAutoDetectParsers = $flightSearchQuery->getAutoSelectParsers();
        $firstSearch = $flightSearchQuery->getSearchCount() === 0;
        $mileValue = $flightSearchQuery->getMileValue();
        $autoCreated = $flightSearchQuery->isAutoCreated();

        if ($autoCreated && !is_null($mileValue)) {
            if (!$mileValue->getTrip()) {
                $this->logger->error($error = sprintf('query has no trip, id: %d', $queryId));
                $this->addQueryStateError($flightSearchQuery, 'Query has no trip');

                throw new SearchException($error);
            }

            $user = $mileValue->getTrip()->getUser();
        } else {
            $user = $flightSearchQuery->getUser();
        }

        if (!$user) {
            $this->logger->error($error = sprintf('user not defined, id: %d', $queryId));
            $this->addQueryStateError($flightSearchQuery, 'User not defined');

            throw new SearchException($error);
        }

        $parserList = $this->getParserList();
        $availableParsers = array_keys($parserList);
        $availableParsersIds = it($parserList)
            ->map(fn (array $parser) => $parser['id'])
            ->toArrayWithKeys();

        if ($isAutoDetectParsers) {
            $parsers = $availableParsers;
        } else {
            $parsers = it(explode(',', $flightSearchQuery->getParsers()))
                ->map(function (string $parser) {
                    return trim($parser);
                })
                ->toArray();
        }

        if (count($diff = array_diff($parsers, $availableParsers)) > 0) {
            $this->logger->info(sprintf('query #%d has disabled parsers: %s', $queryId, implode(', ', $diff)));
            $parsers = array_intersect($parsers, $availableParsers);
        }

        $depCodes = $flightSearchQuery->getDepartureAirports();
        $arrCodes = $flightSearchQuery->getArrivalAirports();
        $flightClasses = [];

        // parse classes from integer
        if ($flightSearchQuery->getFlightClass() & RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY) {
            $flightClasses[] = RAFlightSearchQuery::API_FLIGHT_CLASS_ECONOMY;
        }

        if ($flightSearchQuery->getFlightClass() & RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY) {
            $flightClasses[] = RAFlightSearchQuery::API_FLIGHT_CLASS_PREMIUM_ECONOMY;
        }

        if ($flightSearchQuery->getFlightClass() & RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS) {
            $flightClasses[] = RAFlightSearchQuery::API_FLIGHT_CLASS_BUSINESS;
        }

        if ($flightSearchQuery->getFlightClass() & RAFlightSearchQuery::FLIGHT_CLASS_FIRST) {
            $flightClasses[] = RAFlightSearchQuery::API_FLIGHT_CLASS_FIRST;
        }

        if ($isAutoDetectParsers) {
            $routeParsers = $this->routeParserSelector->getRouteParsers(
                (new ParserSelectorRequest())
                    ->addRoutes($flightSearchQuery->getDepartureAirports(), $flightSearchQuery->getArrivalAirports())
                    ->addDates($dates)
                    ->addFlightClasses($flightClasses)
                    ->addPassengersCount($flightSearchQuery->getAdults()),
                $availableParsersIds
            );
        }

        $successRequests = 0;
        $parserStat = [];
        $exceptions = [];
        $lastExceptionRequest = null;
        $searchId = StringHandler::getRandomCode(10);
        $flightSearchQuery->setLastSearchKey($searchId);
        $this->em->flush();

        $routes = [];
        $writeStat = function (
            string $depCode,
            string $arrCode,
            \DateTime $depDate,
            string $flightClass,
            int $passengersCount,
            bool $fullSearch
        ) use (&$routes) {
            $key = implode('_', [$depCode, $arrCode, $depDate->format('Y-m-d'), $flightClass, $passengersCount]);

            if (isset($routes[$key])) {
                return;
            }

            $routes[$key] = true;
            $this->routeParserSelector->addSearch(
                $depCode,
                $arrCode,
                $depDate,
                $flightClass,
                $passengersCount,
                $fullSearch
            );
        };
        $start = time();

        foreach ($dates as $date) {
            foreach ($parsers as $parser) {
                foreach ($depCodes as $depCode) {
                    foreach ($arrCodes as $arrCode) {
                        foreach ($flightClasses as $flightClass) {
                            if ($isAutoDetectParsers) {
                                $calculatedParsers = $routeParsers->getParsers(
                                    $depCode,
                                    $arrCode,
                                    $date,
                                    $flightClass,
                                    $flightSearchQuery->getAdults()
                                );
                                $isFullSearch = $routeParsers->isFullSearch(
                                    $depCode,
                                    $arrCode,
                                    $date,
                                    $flightClass,
                                    $flightSearchQuery->getAdults()
                                ) || $firstSearch;

                                if (!$isFullSearch) {
                                    if (!$autoCreated) {
                                        $calculatedParsers = array_diff($calculatedParsers, $flightSearchQuery->getExcludeParsersAsArray());

                                        if (!in_array($parser, $calculatedParsers, true)) {
                                            continue;
                                        }
                                    } else {
                                        $calculatedParsers = array_filter($calculatedParsers, function (string $parser) use ($user, $mileValue, $availableParsersIds) {
                                            $totalMilesSpent = $mileValue->getTotalMilesSpent();

                                            return !empty(it(
                                                $this->combinator->findCombinations(
                                                    $user,
                                                    $availableParsersIds[$parser],
                                                    $totalMilesSpent
                                                )
                                            )->first());
                                        });

                                        if (!in_array($parser, $calculatedParsers, true)) {
                                            continue;
                                        }
                                    }
                                }
                            }

                            $request = new ApiSearchRequest(
                                $parser,
                                $depCode,
                                $date,
                                $arrCode,
                                $flightClass,
                                $flightSearchQuery->getAdults(),
                                $flightSearchQuery->getId(),
                                $searchId
                            );
                            $requests[] = $request;

                            if (!isset($parserStat[$parser])) {
                                $parserStat[$parser] = [
                                    'success' => 0,
                                    'error' => 0,
                                ];
                            }

                            try {
                                $response = $this->apiCommunicator->makeRaRequest($this->getSearchQuery($request));

                                if (empty($response) || !is_string($response)) {
                                    throw new \RuntimeException('Empty response');
                                }

                                $response = json_decode($response, true);

                                if (empty($response) || !is_array($response)) {
                                    throw new \RuntimeException('Unable to parse response');
                                }

                                $requestId = $response['requestId'];
                                $this->requestProgressTracker->requestStarted($requestId, $queryId);
                                $successRequests++;
                                $parserStat[$parser]['success']++;

                                $writeStat(
                                    $depCode,
                                    $arrCode,
                                    $date,
                                    $flightClass,
                                    $flightSearchQuery->getAdults(),
                                    ($isAutoDetectParsers && $firstSearch)
                                    || (
                                        $isAutoDetectParsers
                                        && count($availableParsers) === count($totalParsers = $routeParsers->getParsers(
                                            $depCode,
                                            $arrCode,
                                            $date,
                                            $flightClass,
                                            $flightSearchQuery->getAdults()
                                        ))
                                        && count(array_intersect($totalParsers, $availableParsers)) === count($availableParsers)
                                    )
                                    || (
                                        !$isAutoDetectParsers
                                        && count($availableParsers) === count($parsers)
                                        && count(array_intersect($parsers, $availableParsers)) === count($parsers)
                                    )
                                );
                            } catch (\Exception $e) {
                                if (
                                    !($e instanceof ApiCommunicatorException)
                                    && !($e instanceof \RuntimeException)
                                ) {
                                    throw $e;
                                }

                                $exceptionMessage = $e->getMessage();

                                if (isset($exceptions[$exceptionMessage])) {
                                    $exceptions[$exceptionMessage]++;
                                } else {
                                    $exceptions[$exceptionMessage] = 1;
                                }

                                $lastExceptionRequest = $request;
                                $parserStat[$parser]['error']++;
                                $request->setError($exceptionMessage);
                            }
                        }
                    }
                }
            }
        }

        $this->logger->info(sprintf('created RA Flight Search queries, count: %d', count($requests)), array_filter([
            'queryId' => $queryId,
            'successRequests' => $successRequests,
            'exceptions' => $exceptions,
            'parserStat' => $parserStat,
            'autoDetectParsers' => $isAutoDetectParsers,
            'firstSearch' => $firstSearch,
            'selectParsers' => isset($routeParsers) ? $routeParsers->getAllParsers() : null,
            'duration_sec' => time() - $start,
        ]));

        if (!empty($exceptions) && !is_null($lastExceptionRequest)) {
            $this->addRequestStateError(
                $flightSearchQuery,
                $lastExceptionRequest,
                sprintf(
                    'RA Flight Search API errors: "%s"',
                    implode(
                        '", "',
                        it($exceptions)
                            ->mapIndexed(function (int $count, string $message) {
                                return sprintf('%s: %d', $message, $count);
                            })
                            ->toArray()
                    )
                )
            );
        }

        $this->addParserState($flightSearchQuery, $parserStat);
        $flightSearchQuery->setSubSearchCount(
            $flightSearchQuery->getSubSearchCount() + $successRequests
        );
        $flightSearchQuery->incrementSearchCount();
        $flightSearchQuery->setLastSearchDate(new \DateTime());
        $this->em->flush();

        return new ApiSearchResult($requests);
    }

    public function getParserList(): array
    {
        if (!empty($list = $this->memcached->get('RAFlightParserList_v3'))) {
            return $list;
        }

        try {
            $list = $this->apiCommunicator->getListRaProviders();

            if (empty($list) || !is_string($list)) {
                throw new \RuntimeException('Unable to get list of providers');
            }

            $list = json_decode($list, true);
            $list = it($list['providers'])
                ->reindex(fn (array $provider) => $provider['code'])
                ->map(fn (array $provider) => htmlspecialchars_decode($provider['displayName']))
                ->toArrayWithKeys();

            $providers = $this->connection->fetchAllKeyValue(
                'SELECT Code, ProviderID FROM Provider WHERE Code IN (:parsers)',
                ['parsers' => array_keys($list)],
                ['parsers' => Connection::PARAM_STR_ARRAY]
            );

            $list = it($list)
                ->mapIndexed(function (string $name, string $code) use ($providers) {
                    if (!isset($providers[$code])) {
                        return null;
                    }

                    return [
                        'code' => $code,
                        'name' => $name,
                        'id' => $providers[$code],
                    ];
                })
                ->filterNotNull()
                ->toArrayWithKeys();

            $this->memcached->set('RAFlightParserList_v3', $list, 3600);
        } catch (ApiCommunicatorException $e) {
            $this->logger->error(sprintf('Unable to get list of providers: %s', $e->getMessage()));

            return [];
        } catch (\RuntimeException $e) {
            $this->logger->error(sprintf('Unable to get list of providers: %s', $e->getMessage()));

            return [];
        }

        return $list;
    }

    private function getSearchQuery(ApiSearchRequest $request): array
    {
        return [
            'provider' => $request->getParser(),
            'departure' => [
                'airportCode' => $request->getDepCode(),
                'flexibility' => 0,
                'date' => $request->getDepDate()->format('Y-m-d'),
            ],
            'arrival' => $request->getArrCode(),
            'cabin' => $request->getCabin(),
            'passengers' => [
                'adults' => $request->getAdults(),
            ],
            'currency' => 'USD',
            'priority' => 1,
            'timeout' => 0, // zero is unlimited
            'callbackUrl' => $this->router->generate('aw_ra_flight_search_result', [], RouterInterface::ABSOLUTE_URL),
            'userData' => json_encode(
                [
                    'id' => $request->getQueryId(),
                    'parser' => $request->getParser(),
                    'searchId' => $request->getSearchId(),
                ]
            ),
        ];
    }

    private function cleanState(RAFlightSearchQuery $query): void
    {
        $query->setState(null);
        $this->em->flush();
    }

    private function addQueryStateError(RAFlightSearchQuery $query, string $error): void
    {
        $state = $query->getState();

        if (is_null($state)) {
            $state = [];
        }

        $state['query'] = [
            'error' => $error,
            'date' => date('c'),
        ];

        $query->setState($state);
        $this->em->flush();
    }

    private function addRequestStateError(RAFlightSearchQuery $query, ApiSearchRequest $request, string $error): void
    {
        $state = $query->getState();

        if (is_null($state)) {
            $state = [];
        }

        $state['request'] = [
            'error' => $error,
            'last_request' => sprintf(
                'parser: %s, depCode: %s, depDate: %s, arrCode: %s, cabin: %s, adults: %d',
                $request->getParser(),
                $request->getDepCode(),
                $request->getDepDate()->format('Y-m-d'),
                $request->getArrCode(),
                $request->getCabin(),
                $request->getAdults()
            ),
            'date' => date('c'),
        ];

        $query->setState($state);
        $this->em->flush();
    }

    private function addParserState(RAFlightSearchQuery $query, array $stat): void
    {
        $state = $query->getState();

        if (is_null($state)) {
            $state = [];
        }

        $state['parser'] = $stat;

        $query->setState($state);
        $this->em->flush();
    }
}
