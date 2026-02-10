<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Entity\RAFlightSearchRoute;
use AwardWallet\MainBundle\Entity\RAFlightSearchRouteSegment;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Service\AccountBalanceCombinator\Combinator;
use AwardWallet\MainBundle\Service\LogProcessor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CallbackController extends AbstractController
{
    private EntityManagerInterface $em;

    private Connection $connection;

    private LogProcessor $logProcessor;

    private LoggerInterface $logger;

    private string $callbackPassword;

    private Combinator $combinator;

    private RequestProgressTracker $requestProgressTracker;

    private FlightDealSubscriber $flightDealSubscriber;

    private BetterDealChecker $betterDealChecker;

    private array $baseContext;

    public function __construct(
        EntityManagerInterface $em,
        Connection $connection,
        LoggerFactory $loggerFactory,
        string $raCallbackPassword,
        Combinator $combinator,
        RequestProgressTracker $requestProgressTracker,
        FlightDealSubscriber $flightDealSubscriber,
        BetterDealChecker $betterDealChecker
    ) {
        $this->em = $em;
        $this->connection = $connection;
        $this->baseContext = ['class' => 'RAFlightCallback'];
        $this->logProcessor = $loggerFactory->createProcessor($this->baseContext);
        $this->logger = $loggerFactory->createLogger($this->logProcessor);
        $this->callbackPassword = $raCallbackPassword;
        $this->combinator = $combinator;
        $this->requestProgressTracker = $requestProgressTracker;
        $this->flightDealSubscriber = $flightDealSubscriber;
        $this->betterDealChecker = $betterDealChecker;
    }

    /**
     * @Route(
     *     "/api/reward-availability/search/result",
     *     name="aw_ra_flight_search_result",
     *     methods={"POST"}
     * )
     */
    public function callbackAction(Request $request): Response
    {
        if (!$this->checkAccess($request->getUser(), $request->getPassword())) {
            return new Response(Response::$statusTexts[Response::HTTP_FORBIDDEN], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !is_array($data['response'] ?? null)) {
            $this->logger->error('invalid data, not an array');

            return new Response(Response::$statusTexts[Response::HTTP_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
        }

        foreach ($data['response'] as $response) {
            try {
                $this->processResult($response);
            } catch (CallbackException $e) {
                $this->logger->info($e->getMessage(), $e->getContext());
            } finally {
                $this->logProcessor->setBaseContext($this->baseContext);
            }
        }

        return new Response('OK');
    }

    private function checkAccess(?string $user, ?string $password): bool
    {
        $result = $user === 'awardwallet' && $password === $this->callbackPassword;

        if (!$result) {
            $this->logger->error('invalid credentials');
        }

        return $result;
    }

    private function processResult(array $data): void
    {
        $requestId = $data['requestId'] ?? null;

        if (empty($requestId)) {
            throw new CallbackException('Invalid request id');
        }

        $this->logProcessor->pushContext(['requestId' => $requestId]);

        $requestDate = $data['requestDate'] ?? null;

        if (empty($requestDate)) {
            throw new CallbackException('Invalid request date');
        }

        $state = $data['state'] ?? null;
        $message = $data['message'] ?? null;

        if ($state != 1) {
            $this->logger->info(sprintf(
                'flight search data has not been successfully retrieved (state: %s, message: "%s")',
                $state,
                $message
            ));

            return;
        }

        $userData = $data['userData'] ?? null;
        $userDataArray = $userData ? json_decode($userData, true) : null;

        if (!is_array($userDataArray)) {
            throw new CallbackException('Invalid user data');
        }

        if (!isset($userDataArray['id']) || !isset($userDataArray['parser'])) {
            throw new CallbackException('Invalid user data, id or parser is not set');
        }

        /** @var RAFlightSearchQuery $query */
        $query = $this->em->find(RAFlightSearchQuery::class, $userDataArray['id']);

        if (!$query) {
            $this->logger->info(sprintf('query not found, id: %d', $userDataArray['id']));

            return;
        }

        $this->logProcessor->pushContext(['query' => $query->getId()]);

        if ($query->isAutoCreated() && !empty($mileValue = $query->getMileValue())) {
            $syncQueryId = $this->flightDealSubscriber->syncByMileValue($mileValue->getId());

            if (is_null($syncQueryId)) {
                $this->logger->info(sprintf('query is not valid, id: %d', $query->getId()));

                return;
            }

            if ($syncQueryId !== $query->getId()) {
                $this->logger->info(sprintf('query is not sync, id: %d', $query->getId()));

                return;
            }

            $this->em->refresh($query);
        }

        if ($query->isDeleted()) {
            $this->logger->info(sprintf('query is deleted, id: %d', $query->getId()));

            return;
        }

        if ($query->getAutoSelectParsers()) {
            if (!isset($userDataArray['searchId']) || $userDataArray['searchId'] !== $query->getLastSearchKey()) {
                $this->logger->info(sprintf('invalid search id "%s"', $userDataArray['searchId'] ?? ''));

                return;
            }

            $mileValue = $query->getMileValue();
            $autoCreated = $query->isAutoCreated();

            if (!$autoCreated) {
                if (in_array($userDataArray['parser'], $query->getExcludeParsersAsArray())) {
                    $this->logger->info(sprintf('excluded parser "%s"', $userDataArray['parser']));

                    return;
                }
            } else {
                $providerId = $this->connection->fetchOne('SELECT ProviderID FROM Provider WHERE Code = ?', [$userDataArray['parser']]);

                if ($providerId === false) {
                    $this->logger->info(sprintf('provider not found "%s"', $userDataArray['parser']));

                    return;
                }

                if (!$mileValue->getTrip()) {
                    $this->logger->info(sprintf('no trip found for mile value %d, query %d', $mileValue->getId(), $query->getId()));

                    return;
                }

                $user = $mileValue->getTrip()->getUser();

                if (!$user) {
                    $this->logger->info(sprintf('no user found for mile value %d, query %d', $mileValue->getId(), $query->getId()));

                    return;
                }

                $totalMilesSpent = $mileValue->getTotalMilesSpent();
                $hasAccounts = !empty(it(
                    $this->combinator->findCombinations(
                        $user,
                        $providerId,
                        $totalMilesSpent
                    )
                )->first());

                if (!$hasAccounts) {
                    $this->logger->info(sprintf('no accounts found for provider "%s"', $userDataArray['parser']));

                    return;
                }
            }
        } else {
            $parsers = array_map('trim', explode(',', $query->getParsers()));

            if (!in_array($userDataArray['parser'], $parsers)) {
                $this->logger->info(sprintf('invalid parser "%s"', $userDataArray['parser']));

                return;
            }
        }

        $routes = $data['routes'] ?? null;

        if (!is_array($routes) || empty($routes)) {
            throw new CallbackException('Invalid routes data');
        }

        $startDate = clone $query->getDepDateFrom();
        $endDate = clone $query->getDepDateTo();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $startDate->setTime(0, 0, 0);
        $endDate->modify('+1 day')->setTime(0, 0, 0);

        foreach ($routes as $route) {
            try {
                $this->processRoute($query, $startDate, $endDate, $route, $userDataArray['parser'], $requestId);
            } catch (CallbackException $e) {
                $this->logger->info($e->getMessage(), $e->getContext());
            }
        }
    }

    private function processRoute(
        RAFlightSearchQuery $query,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        array $route,
        string $parser,
        string $requestId
    ): void {
        $segments = $route['segments'] ?? null;

        if (!is_array($segments) || empty($segments)) {
            throw new CallbackException('Invalid segments data');
        }

        $totalDistance = 0;
        $cabinsDurations = [];
        $maxLayoverDuration = 0;

        foreach ($segments as $segment) {
            if (is_null($segment['departure']['airport'] ?? null) || is_null($segment['arrival']['airport'] ?? null)) {
                throw new CallbackException('Invalid segment airport');
            }

            if (is_null($segment['departure']['dateTime'] ?? null) || is_null($segment['arrival']['dateTime'] ?? null)) {
                throw new CallbackException('Invalid segment date');
            }

            if (is_null($segment['cabin'] ?? null)) {
                throw new CallbackException('Invalid segment cabin');
            }

            if (is_null($this->convertCabinToInt($segment['cabin']))) {
                throw new CallbackException(sprintf('Invalid cabin "%s"', $segment['cabin']));
            }

            if (is_null($segment['times']['flight'] ?? null)) {
                throw new CallbackException('Invalid segment flight duration');
            }

            $flightDuration = Duration::parseSeconds($segment['times']['flight']);

            if (is_null($flightDuration)) {
                throw new CallbackException('Invalid format of segment flight duration');
            }

            if (isset($segment['times']['layover']) && is_null(Duration::parseSeconds($segment['times']['layover']))) {
                throw new CallbackException('Invalid format of segment layover duration');
            }

            if (!isset($cabinsDurations[$segment['cabin']])) {
                $cabinsDurations[$segment['cabin']] = 0;
            }

            $cabinsDurations[$segment['cabin']] += $flightDuration;
            $totalDistance += $this->calcDistance($segment['departure']['airport'], $segment['arrival']['airport']);
            $maxLayoverDuration = max(
                $maxLayoverDuration,
                Duration::parseSeconds($segment['times']['layover'] ?? '') ?? 0
            );
        }

        if ($totalDistance == 0) {
            throw new CallbackException(sprintf('Invalid distance, segments: %s', json_encode($segments)));
        }

        if (is_null($route['mileCost']['miles'] ?? null)) {
            throw new CallbackException('Invalid mile cost data');
        }

        $context = [
            'route' => sprintf('%s - %s', $segments[0]['departure']['airport'], $segments[count($segments) - 1]['arrival']['airport']),
            'depDate' => date('Y-m-d H:i:s', strtotime($segments[0]['departure']['dateTime'])),
        ];

        // DepartureAirports
        if (!in_array($segments[0]['departure']['airport'], $query->getDepartureAirports())) {
            throw new CallbackException('Departure airport does not match', array_merge($context, ['expected' => $query->getDepartureAirports(), 'actual' => $segments[0]['departure']['airport']]));
        }

        // ArrivalAirports
        if (!in_array($segments[count($segments) - 1]['arrival']['airport'], $query->getArrivalAirports())) {
            throw new CallbackException('Arrival airport does not match', array_merge($context, ['expected' => $query->getArrivalAirports(), 'actual' => $segments[count($segments) - 1]['arrival']['airport']]));
        }

        // DepDateFrom, DepDateTo
        try {
            $firstDepDate = new \DateTime($segments[0]['departure']['dateTime']);
        } catch (\Exception $e) {
            throw new CallbackException(sprintf('Invalid format of departure date "%s"', $segments[0]['departure']['dateTime']));
        }

        if ($firstDepDate < $startDate || $firstDepDate >= $endDate) {
            throw new CallbackException('Departure date does not match', array_merge($context, ['expected' => $startDate->format('Y-m-d H:i:s') . ' - ' . $endDate->format('Y-m-d H:i:s'), 'actual' => $firstDepDate->format('Y-m-d H:i:s')]));
        }

        // Adults
        if (!is_null($route['tickets'] ?? null) && $route['tickets'] < $query->getAdults()) {
            throw new CallbackException('Count of tickets less than adults', array_merge($context, ['expected' => $query->getAdults(), 'actual' => $route['tickets']]));
        }

        // MaxTotalDuration
        if (
            !is_null($query->getMaxTotalDuration())
            && (
                $actual = (
                    (Duration::parseSeconds($route['times']['flight'] ?? '') ?? 0)
                    + (Duration::parseSeconds($route['times']['layover'] ?? '') ?? 0)
                ) / 3600
            ) > $query->getMaxTotalDuration()
        ) {
            throw new CallbackException('Total duration exceeds the limit', array_merge($context, ['expected' => $query->getMaxTotalDuration(), 'actual' => round($actual, 2)]));
        }

        // MaxSingleLayoverDuration
        if (
            !is_null($query->getMaxSingleLayoverDuration())
            && (
                $actual = ($maxLayoverDuration / 3600)
            ) > $query->getMaxSingleLayoverDuration()
        ) {
            throw new CallbackException('Single layover duration exceeds the limit', array_merge($context, ['expected' => $query->getMaxSingleLayoverDuration(), 'actual' => round($actual, 2)]));
        }

        // MaxTotalLayoverDuration
        if (
            !is_null($query->getMaxTotalLayoverDuration())
            && (
                $actual = ((Duration::parseSeconds($route['times']['layover'] ?? '') ?? 0) / 3600)
            ) > $query->getMaxTotalLayoverDuration()
        ) {
            throw new CallbackException('Total layover duration exceeds the limit', array_merge($context, ['expected' => $query->getMaxTotalLayoverDuration(), 'actual' => round($actual, 2)]));
        }

        // MaxStops
        if (
            !is_null($query->getMaxStops())
            && ($actual = $route['numberOfStops'] ?? 0) > $query->getMaxStops()
        ) {
            throw new CallbackException('Count of stops exceeds the limit', array_merge($context, ['expected' => $query->getMaxStops(), 'actual' => $actual]));
        }

        // FlightClass

        /**
         * Classes are ranked as follows
         * 1 - Economy
         * 2 - Premium Economy
         * 3 - Business
         * 4 - First.
         *
         * Rule 1: Always allow higher classes of service
         * a business-class search will always allow first class flights
         * an economy search will always allow premiumEconomy, business, or first class flights.
         *
         * Rule 2: Allow 25% of travel time to be in a lower class of service.
         * Business class search may display one or more flight segments in economy or premium economy as long as the sum of the flight hours in a lower class of service are <= 25% of the total travel time (excluding layovers).
         *
         * Rule 3: How to handle requests with a combination of cabin classes
         * If admin selects the search criteria "Economy + Business”
         * Economy Miles Limit = 20,000
         * Business Miles Limit = 80,000
         *
         * Example Itinerary - Mostly Business Class
         *
         * BOS-JFK (1hr) - Economy (11% of flight time)
         * JFK-LHR (8hr) - Business (89% of flight time)
         * Total flight time = 9h (8+1)
         *
         * This result has 89% of flight time in business class (or higher).
         * This meets the 75% rule. If the cost of the itinerary is <=80,000 (business miles limit), than the result should be saved.
         *
         * Final Example
         *
         * If admin selects the search criteria "PremiumEconomy + Business”
         * PremiumEconomy Miles Limit = 60,000
         * Business Miles Limit = 80,000
         * We find an itinerary with 4 flight segments:
         * Flight 1 - 25% Economy Class
         * Flight 2 - 15% Premium Economy
         * Flight 3 - 55% Business
         * Flight 4 - 5% First
         *
         * Allowed for first? - FALSE - 5% of total is in first class or higher
         * Allowed for business? - FALSE - 60% of total is in business class or higher (55% business + 5% first)
         * Allowed for premiumEconomy - TRUE - 75% of total is in premium economy or higher (15% premium + 55% business + 5% first)
         * Allowed for economy - TRUE
         */
        $cabinPercentages = [
            RAFlightSearchQuery::API_FLIGHT_CLASS_FIRST => 0,
            RAFlightSearchQuery::API_FLIGHT_CLASS_BUSINESS => 0,
            RAFlightSearchQuery::API_FLIGHT_CLASS_PREMIUM_ECONOMY => 0,
            RAFlightSearchQuery::API_FLIGHT_CLASS_ECONOMY => 0,
        ];
        $cabinLimits = [
            RAFlightSearchQuery::API_FLIGHT_CLASS_FIRST => $query->getFirstMilesLimit(),
            RAFlightSearchQuery::API_FLIGHT_CLASS_BUSINESS => $query->getBusinessMilesLimit(),
            RAFlightSearchQuery::API_FLIGHT_CLASS_PREMIUM_ECONOMY => $query->getPremiumEconomyMilesLimit(),
            RAFlightSearchQuery::API_FLIGHT_CLASS_ECONOMY => $query->getEconomyMilesLimit(),
        ];
        $totalDuration = array_sum($cabinsDurations);
        $threshold = 0.75;
        $basePercentage = 0;
        $primaryCabin = null;

        foreach ($cabinPercentages as $cabin => $percentage) {
            $duration = $cabinsDurations[$cabin] ?? 0;
            $cabinPercentages[$cabin] = round($duration / $totalDuration, 2);
            $basePercentage += $cabinPercentages[$cabin];

            if ($basePercentage >= $threshold) {
                $primaryCabin = $cabin;

                break;
            }
        }

        if (is_null($primaryCabin)) {
            throw new CallbackException('Primary cabin not found');
        }

        $limits = array_slice($cabinLimits, array_search($primaryCabin, array_keys($cabinLimits)));
        $limits = array_filter($limits, fn ($limit) => !is_null($limit) && $limit > 0);

        if (empty($limits)) {
            throw new CallbackException(sprintf('Miles limit not found for cabin "%s"', $primaryCabin));
        }

        $passedLimits = array_filter($limits, fn ($limit) => $limit >= $route['mileCost']['miles']);
        $notPassedLimits = array_filter($limits, fn ($limit) => $limit < $route['mileCost']['miles']);

        if (!empty($passedLimits)) {
            $this->logger->info(sprintf('success, cabin: %s, limits: %s, actual: %s', $primaryCabin, json_encode($limits), $route['mileCost']['miles']), array_merge($context, [
                'limits' => $limits,
                'actual' => $route['mileCost']['miles'],
                'cabinPercentages' => $cabinPercentages,
                'cabinLimits' => $cabinLimits,
                'passedLimits' => $passedLimits,
                'notPassedLimits' => $notPassedLimits,
            ]));

            $this->em->refresh($query);
            $entityRoute = new RAFlightSearchRoute();
            $entityRoute
                ->setFlightDuration($route['times']['flight'] ?? null)
                ->setLayoverDuration($route['times']['layover'] ?? null)
                ->setStops($route['numberOfStops'] ?? null)
                ->setTickets($route['tickets'] ?? null)
                ->setAwardTypes($route['awardTypes'] ?? null)
                ->setMileCostProgram($route['mileCost']['program'] ?? null)
                ->setMileCost($route['mileCost']['miles'])
                ->setCurrency($route['cashCost']['currency'] ?? null)
                ->setConversionRate($route['cashCost']['conversionRate'] ?? null)
                ->setTaxes($route['cashCost']['taxes'] ?? null)
                ->setFees($route['cashCost']['fees'] ?? null)
                ->setTotalDistance($totalDistance)
                ->setParser($parser)
                ->setItineraryCOS($primaryCabin)
                ->setApiRequestID($requestId);

            if ($query->isAutoCreated()) {
                if (empty($mileValue = $query->getMileValue())) {
                    throw new CallbackException('Mile value not found');
                }

                if (empty($trip = $mileValue->getTrip())) {
                    throw new CallbackException('Trip not found');
                }

                $tickets = $entityRoute->getTickets() ?? $mileValue->getTravelersCount();
                $isBetterDeal = $this->betterDealChecker->isBetterDeal(
                    ($entityRoute->getMileCost() ?? 0) * $tickets,
                    $mileValue->getTotalMilesSpent(),
                    round($entityRoute->getTotalDuration() / 3600, 2),
                    round($trip->getUTCEndDate()->getTimestamp() - $trip->getUTCStartDate()->getTimestamp() / 3600, 2)
                );
            }

            if (
                ($query->isAutoCreated() && isset($isBetterDeal) && $isBetterDeal)
                || !$query->isAutoCreated()
            ) {
                foreach ($segments as $segment) {
                    $entitySegment = new RAFlightSearchRouteSegment();
                    $entitySegment
                        ->setDepDate(date_create($segment['departure']['dateTime']))
                        ->setDepCode($segment['departure']['airport'])
                        ->setDepTerminal($segment['departure']['terminal'] ?? null)
                        ->setArrDate(date_create($segment['arrival']['dateTime']))
                        ->setArrCode($segment['arrival']['airport'])
                        ->setArrTerminal($segment['arrival']['terminal'] ?? null)
                        ->setMeal($segment['meal'] ?? null)
                        ->setService($segment['cabin'] ?? null)
                        ->setFareClass($segment['fareClass'] ?? null)
                        ->setFlightNumbers(isset($segment['flightNumbers']) && is_array($segment['flightNumbers']) ? $segment['flightNumbers'] : null)
                        ->setAirlineCode($segment['airlineCode'] ?? null)
                        ->setAircraft($segment['aircraft'] ?? null)
                        ->setFlightDuration($segment['times']['flight'] ?? null)
                        ->setLayoverDuration($segment['times']['layover'] ?? null);

                    $entityRoute->addSegment($entitySegment);
                }

                /** @var RAFlightSearchRoute[] $existingRoutes */
                $existingRoutes = [];

                foreach ($query->getRoutes() as $existingRoute) {
                    if ($entityRoute->equals($existingRoute)) {
                        $existingRoutes[] = $existingRoute;
                    }
                }

                if (!empty($existingRoutes)) {
                    $this->logger->info(sprintf(
                        'route already exists, ids: %s',
                        implode(', ', array_map(fn (RAFlightSearchRoute $route) => $route->getId(), $existingRoutes))
                    ), $context);

                    usort($existingRoutes, fn (RAFlightSearchRoute $a, RAFlightSearchRoute $b) => $a->getCreateDate() <=> $b->getCreateDate());
                    $newEntityRoute = $entityRoute;
                    $entityRoute = array_pop($existingRoutes);

                    foreach ($existingRoutes as $existingRoute) {
                        $entityRoute->updateByRoute($existingRoute);
                        $query->removeRoute($existingRoute);
                        $this->em->remove($existingRoute);
                    }

                    $entityRoute->updateByRoute($newEntityRoute);
                    $this->em->flush();
                    $this->logger->info(sprintf('route updated, id: %s', $entityRoute->getId()), $context);
                } else {
                    $query->addRoute($entityRoute);
                    $this->em->persist($entityRoute);
                    $this->em->flush();
                    $this->logger->info(sprintf('route created, id: %s', $entityRoute->getId()), $context);
                }

                $this->requestProgressTracker->responseReceived($requestId, [$entityRoute->getId()]);
            }
        } else {
            $this->logger->info(sprintf('miles limit exceeded, cabin: %s, limits: %s, actual: %s', $primaryCabin, json_encode($limits), $route['mileCost']['miles']), array_merge($context, [
                'limits' => $limits,
                'actual' => $route['mileCost']['miles'],
                'cabinPercentages' => $cabinPercentages,
                'cabinLimits' => $cabinLimits,
                'passedLimits' => $passedLimits,
                'notPassedLimits' => $notPassedLimits,
            ]));
        }
    }

    private function calcDistance(string $depCode, string $arrCode): float
    {
        $dep = $this->connection->executeQuery('SELECT Lat, Lng FROM AirCode WHERE AirCode = ?', [$depCode])->fetchAssociative();

        if (!$dep) {
            $dep = $this->connection->executeQuery('SELECT Lat, Lng FROM StationCode WHERE StationCode = ?', [$depCode])->fetchAssociative();
        }

        $arr = $this->connection->executeQuery('SELECT Lat, Lng FROM AirCode WHERE AirCode = ?', [$arrCode])->fetchAssociative();

        if (!$arr) {
            $arr = $this->connection->executeQuery('SELECT Lat, Lng FROM StationCode WHERE StationCode = ?', [$arrCode])->fetchAssociative();
        }

        if (!$dep || is_null($dep['Lat']) || is_null($dep['Lng'])) {
            $this->logger->warning(sprintf('no geolocation for airport "%s"', $depCode));

            return 0;
        }

        if (!$arr || is_null($arr['Lat']) || is_null($arr['Lng'])) {
            $this->logger->warning(sprintf('no geolocation for airport "%s"', $arrCode));

            return 0;
        }

        return Geo::distance($dep['Lat'], $dep['Lng'], $arr['Lat'], $arr['Lng']);
    }

    private function convertCabinToInt(string $cabin): ?int
    {
        switch ($cabin) {
            case RAFlightSearchQuery::API_FLIGHT_CLASS_ECONOMY:
                return RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY;

            case RAFlightSearchQuery::API_FLIGHT_CLASS_PREMIUM_ECONOMY:
                return RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY;

            case RAFlightSearchQuery::API_FLIGHT_CLASS_BUSINESS:
                return RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS;

            case RAFlightSearchQuery::API_FLIGHT_CLASS_FIRST:
                return RAFlightSearchQuery::FLIGHT_CLASS_FIRST;

            default:
                return null;
        }
    }
}
