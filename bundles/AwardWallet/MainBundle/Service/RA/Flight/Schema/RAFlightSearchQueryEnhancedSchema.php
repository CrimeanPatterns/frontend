<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountBalanceCombinator\Combinator;
use AwardWallet\MainBundle\Service\EnhancedAdmin\AbstractEnhancedSchema;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ActionInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\EditActionInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\FormRenderer;
use AwardWallet\MainBundle\Service\EnhancedAdmin\PageRenderer;
use AwardWallet\MainBundle\Service\RA\Flight\Api;
use AwardWallet\MainBundle\Service\RA\Flight\DTO\ParserSelectorRequest;
use AwardWallet\MainBundle\Service\RA\Flight\RouteParserSelector;
use AwardWallet\MainBundle\Service\RA\Flight\Schema\Form\RAFlightSearchQueryModel;
use AwardWallet\MainBundle\Service\RA\Flight\Schema\Form\RAFlightSearchQueryType;
use AwardWallet\MainBundle\Service\RA\Flight\SearchTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class RAFlightSearchQueryEnhancedSchema implements EditActionInterface, ActionInterface
{
    private Connection $connection;

    private EntityManagerInterface $em;

    private FormFactoryInterface $formFactory;

    private AwTokenStorageInterface $tokenStorage;

    private Process $asyncProcess;

    private LocalizeService $localizeService;

    private RouteParserSelector $routeParserSelector;

    private Combinator $combinator;

    private Api $api;

    public function __construct(
        Connection $connection,
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        AwTokenStorageInterface $tokenStorage,
        Process $asyncProcess,
        LocalizeService $localizeService,
        RouteParserSelector $routeParserSelector,
        Combinator $combinator,
        Api $api
    ) {
        $this->connection = $connection;
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->tokenStorage = $tokenStorage;
        $this->asyncProcess = $asyncProcess;
        $this->localizeService = $localizeService;
        $this->routeParserSelector = $routeParserSelector;
        $this->combinator = $combinator;
        $this->api = $api;
    }

    public static function getSchema(): string
    {
        return 'RAFlightSearchQuery';
    }

    public function action(Request $request, PageRenderer $renderer, string $actionName): Response
    {
        switch ($actionName) {
            case 'search':
                $queryId = $request->query->get('id');

                if (empty($queryId) || !is_numeric($queryId)) {
                    throw new NotFoundHttpException();
                }

                $query = $this->em->find(RAFlightSearchQuery::class, $queryId);

                if (!$query || $query->isDeleted()) {
                    throw new NotFoundHttpException(sprintf('RAFlightSearchQuery #%d not found', $queryId));
                }

                $this->asyncProcess->execute(
                    new SearchTask($query->getId())
                );

                return new JsonResponse([
                    'success' => true,
                ]);

            case 'detect-parsers':
                $from = $request->request->get('from');

                if (!is_string($from)) {
                    throw new BadRequestHttpException('Parameter "from" must be a string');
                }

                $from = $this->stringToArray($from);

                if (empty($from)) {
                    throw new BadRequestHttpException('Parameter "from" must not be empty');
                }

                $to = $request->request->get('to');

                if (!is_string($to)) {
                    throw new BadRequestHttpException('Parameter "to" must be a string');
                }

                $to = $this->stringToArray($to);

                if (empty($to)) {
                    throw new BadRequestHttpException('Parameter "to" must not be empty');
                }

                $fromDate = $request->request->get('fromDate');

                if (!is_string($fromDate)) {
                    throw new BadRequestHttpException('Parameter "fromDate" must be a string');
                }

                $fromDate = date_create_from_format('Y-m-d', $fromDate);

                if ($fromDate === false) {
                    throw new BadRequestHttpException('Parameter "fromDate" must be a valid date in format "Y-m-d"');
                }

                $toDate = $request->request->get('toDate');

                if (!is_string($toDate)) {
                    throw new BadRequestHttpException('Parameter "toDate" must be a string');
                }

                $toDate = date_create_from_format('Y-m-d', $toDate);

                if ($toDate === false) {
                    throw new BadRequestHttpException('Parameter "toDate" must be a valid date in format "Y-m-d"');
                }

                $flightClasses = $request->request->get('flightClasses');

                if (!is_numeric($flightClasses)) {
                    throw new BadRequestHttpException('Parameter "flightClasses" must be a number');
                }

                $flightClasses = (int) $flightClasses;

                $numberOfPassengers = $request->request->get('numberOfPassengers');

                if (!is_numeric($numberOfPassengers)) {
                    throw new BadRequestHttpException('Parameter "numberOfPassengers" must be a number');
                }

                $numberOfPassengers = (int) $numberOfPassengers;

                if ($numberOfPassengers < 1 || $numberOfPassengers > 9) {
                    throw new BadRequestHttpException('Parameter "numberOfPassengers" must be between 1 and 9');
                }

                $excludedParsers = $request->request->get('excludedParsers');

                if (!is_array($excludedParsers)) {
                    $excludedParsers = [];
                }

                if (it($excludedParsers)->any(fn ($parser) => !is_string($parser))) {
                    throw new BadRequestHttpException('All elements of "excludedParsers" array must be strings');
                }

                $availableParsers = $this->api->getParserList();
                $parsers = $this->routeParserSelector->getRouteParsers(
                    (new ParserSelectorRequest())
                        ->addRoutes($from, $to)
                        ->addDates($this->getDates($fromDate, $toDate))
                        ->addFlightClasses($this->getFlightClasses($flightClasses))
                        ->addPassengersCount($numberOfPassengers),
                    array_map(fn (array $parser) => $parser['id'], $availableParsers)
                );

                $parsers = array_map(function (array $route) use ($availableParsers, $excludedParsers) {
                    if ($route['fullSearch']) {
                        $excluded = [];
                    } else {
                        $excluded = array_unique(array_merge(array_values(
                            array_diff(array_keys($availableParsers), $route['parsers'])
                        ), $excludedParsers));
                    }

                    $route['excluded'] = array_map(function (string $parser) use ($availableParsers) {
                        return $availableParsers[$parser]['name'] ?? $parser;
                    }, $excluded);
                    sort($route['excluded']);
                    $route['excludedAll'] = count($excluded) === count($availableParsers);

                    unset($route['parsers']);

                    return $route;
                }, $parsers->getAllParsers());

                $parsers = array_values(array_filter($parsers, function ($route) {
                    return !empty($route['excluded']);
                }));

                return new JsonResponse($parsers);

            case 'get-parsers':
                $queries = $request->request->get('queries');

                if (!is_array($queries)) {
                    throw new BadRequestHttpException('Parameter "queries" must be an array');
                }

                if (it($queries)->any(fn ($query) => !is_numeric($query))) {
                    throw new BadRequestHttpException('All elements of "queries" array must be numeric');
                }

                $queries = array_unique(array_map('intval', $queries));
                $queryList = $this->em->getRepository(RAFlightSearchQuery::class)->findBy([
                    'id' => $queries,
                    'autoSelectParsers' => true,
                    'deleteDate' => null,
                ], ['user' => 'ASC']);
                /** @var RAFlightSearchQuery[] $queryList */
                $queryListById = it($queryList)
                    ->reindex(fn (RAFlightSearchQuery $query) => $query->getId())
                    ->toArrayWithKeys();
                $requests = [];

                foreach ($queryList as $query) {
                    $requests[] = (new ParserSelectorRequest())
                        ->addRoutes($query->getDepartureAirports(), $query->getArrivalAirports())
                        ->addDates($this->getDates($query->getDepDateFrom(), $query->getDepDateTo()))
                        ->addFlightClasses($this->getFlightClasses($query->getFlightClass()))
                        ->addPassengersCount($query->getAdults());
                }

                $availableParsers = $this->api->getParserList();
                $availableParsersIds = array_map(fn (array $parser) => $parser['id'], $availableParsers);
                $parsers = $this->routeParserSelector->getRouteParsers($requests, $availableParsersIds);
                $result = [];
                $currentUser = null;

                foreach ($queries as $queryId) {
                    /** @var RAFlightSearchQuery $query */
                    $query = $queryListById[$queryId] ?? null;

                    if (!$query) {
                        $result[$queryId] = null;

                        continue;
                    }

                    $mileValue = $query->getMileValue();
                    $autoCreated = $query->isAutoCreated();

                    if ($autoCreated && $mileValue && (is_null($currentUser) || $currentUser->getId() !== $mileValue->getTrip()->getUser()->getId())) {
                        $currentUser = $mileValue->getTrip()->getUser();
                        $this->combinator->bootstrap($currentUser, array_values($availableParsersIds));
                    }

                    $state = $query->getState();
                    $queryParsers = [];

                    foreach ($query->getDepartureAirports() as $from) {
                        foreach ($query->getArrivalAirports() as $to) {
                            foreach ($this->getDates($query->getDepDateFrom(), $query->getDepDateTo()) as $date) {
                                foreach ($this->getFlightClasses($query->getFlightClass()) as $flightClass) {
                                    $calculatedParsers = $parsers->getParsers($from, $to, $date, $flightClass, $query->getAdults());
                                    $isFullSearch = $parsers->isFullSearch($from, $to, $date, $flightClass, $query->getAdults());

                                    if (!$isFullSearch) {
                                        if (!$autoCreated) {
                                            $calculatedParsers = array_diff($calculatedParsers, $query->getExcludeParsersAsArray());
                                        } else {
                                            $calculatedParsers = array_filter($calculatedParsers, function (string $parser) use ($mileValue, $availableParsersIds) {
                                                $totalMilesSpent = $mileValue->getTotalMilesSpent();

                                                return !empty(
                                                    it($this->combinator->findCombinations(
                                                        $mileValue->getTrip()->getUser(),
                                                        $availableParsersIds[$parser],
                                                        $totalMilesSpent
                                                    ))->first()
                                                );
                                            });
                                        }
                                    }

                                    $queryParsers = array_unique(array_merge($queryParsers, $calculatedParsers));
                                }
                            }
                        }
                    }

                    $result[$queryId] = array_map(function (string $parser) use ($availableParsers, $state) {
                        $label = $availableParsers[$parser]['name'] ?? $parser;
                        $hasError = $state && ($state['parser'][$parser]['error'] ?? 0) > 0;
                        $title = sprintf(
                            'last success requests: %s, last error requests: %s',
                            $this->localizeService->formatNumber($state['parser'][$parser]['success'] ?? 0),
                            $this->localizeService->formatNumber($state['parser'][$parser]['error'] ?? 0)
                        );

                        return sprintf(
                            '<span style="cursor: pointer; font-size: 10px; text-align: center; border: 0; background-color: %s; color: %s; padding: 2px; margin: 3px; text-wrap: nowrap;" title="%s">%s</span><wbr>',
                            $hasError ? '#f8d7da' : '#eaeaea',
                            $hasError ? '#ff0000' : '#818181',
                            $title,
                            $label
                        );
                    }, $queryParsers);

                    if (empty($result[$queryId])) {
                        $result[$queryId] = '<span style="font-size: 10px; color: #818181;">No parsers found</span>';
                    } else {
                        $result[$queryId] = '<div style="line-height: 1.8">' . implode('', $result[$queryId]) . '</div>';
                    }
                }

                return new JsonResponse($result);

            case 'find-user':
                $query = $request->query->get('query');

                if (!is_string($query)) {
                    return new JsonResponse([]);
                }

                $query = trim($query);

                if (empty($query)) {
                    return new JsonResponse([]);
                }

                $stmt = $this->connection->executeQuery('
                    SELECT 
                        UserID,
                        IF(
                            AccountLevel = :business,
                            Company,
                            CONCAT(FirstName, " ", LastName, " (", Email, ")")
                        ) AS Name
                    FROM Usr 
                    WHERE
                        (
                            AccountLevel = :business
                            AND (Company LIKE :query OR Login LIKE :query)
                        ) OR (
                            AccountLevel <> :business
                            AND (Email LIKE :query OR Login LIKE :query)
                        )
                    LIMIT 5',
                    [
                        'query' => "%$query%",
                        'business' => ACCOUNT_LEVEL_BUSINESS,
                    ]
                );

                $found = [];

                while ($row = $stmt->fetchAssociative()) {
                    $found[] = [
                        'label' => $row['Name'],
                        'value' => $row['UserID'],
                    ];
                }

                if (is_numeric($query) && (int) $query > 0) {
                    $user = $this->em->find(Usr::class, (int) $query);

                    if ($user) {
                        array_unshift($found, [
                            'label' => sprintf('%s (%s)', $user->getFullName(), $user->getEmail()),
                            'value' => $user->getId(),
                        ]);
                    }
                }

                return new JsonResponse($found);

            default:
                throw new NotFoundHttpException();
        }
    }

    public function editAction(Request $request, FormRenderer $renderer, ?int $id = null): Response
    {
        $edit = !is_null($id);

        if (is_null($id)) {
            /** @var Usr $user */
            $user = $this->tokenStorage->getToken()->getUser();
            $query = new RAFlightSearchQuery();
            $query->setUser($user);
        } else {
            $query = $this->em->find(RAFlightSearchQuery::class, $id);

            if (!$query || $query->isDeleted()) {
                throw new NotFoundHttpException(sprintf('RAFlightSearchQuery #%d not found', $id));
            }

            if (!empty($query->getMileValue())) {
                throw new BadRequestHttpException('Query is linked to MileValue and cannot be edited');
            }
        }

        $form = $this->formFactory->create(RAFlightSearchQueryType::class, $query);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RAFlightSearchQueryModel $data */
            $data = $form->getData();

            $query->setDepartureAirports($this->stringToArray($data->getFromAirports()));
            $query->setArrivalAirports($this->stringToArray($data->getToAirports()));
            $query->setDepDateFrom($data->getFromDate());
            $query->setDepDateTo($data->getToDate());
            $query->setFlightClass($data->getFlightClass());
            $query->setAdults($data->getAdults());
            $query->setSearchInterval($data->getSearchInterval());
            $query->setAutoSelectParsers($data->getAutoSelectParsers());

            if (is_array($parsers = $data->getParsers())) {
                $query->setParsersFromArray($parsers);
            } else {
                $query->setParsers(null);
            }

            if (is_array($excludeParsers = $data->getExcludeParsers())) {
                $query->setExcludeParsersFromArray($excludeParsers);
            } else {
                $query->setExcludeParsers(null);
            }

            $query->setEconomyMilesLimit($data->getEconomyMilesLimit());
            $query->setPremiumEconomyMilesLimit($data->getPremiumEconomyMilesLimit());
            $query->setBusinessMilesLimit($data->getBusinessMilesLimit());
            $query->setFirstMilesLimit($data->getFirstMilesLimit());
            $query->setMaxTotalDuration($data->getMaxTotalDuration());
            $query->setMaxSingleLayoverDuration($data->getMaxSingleLayoverDuration());
            $query->setMaxTotalLayoverDuration($data->getMaxTotalLayoverDuration());
            $query->setMaxStops(empty($data->getMaxStops()) ? null : $data->getMaxStops());

            // set flight class by filled limit fields. If field is filled, then add this class to the list
            $flightClasses = 0;

            if (!empty($data->getEconomyMilesLimit())) {
                $flightClasses |= RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY;
            }

            if (!empty($data->getPremiumEconomyMilesLimit())) {
                $flightClasses |= RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY;
            }

            if (!empty($data->getBusinessMilesLimit())) {
                $flightClasses |= RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS;
            }

            if (!empty($data->getFirstMilesLimit())) {
                $flightClasses |= RAFlightSearchQuery::FLIGHT_CLASS_FIRST;
            }

            if (empty($flightClasses)) {
                $form->addError(new FormError(/** @Ignore */ 'At least one class must be selected'));
            } else {
                $query->setFlightClass($flightClasses);

                if ($edit) {
                    $query->setUpdateDate(new \DateTime());
                } else {
                    $this->em->persist($query);
                }

                $this->em->flush();

                if ($edit || $query->getSearchInterval() == RAFlightSearchQuery::SEARCH_INTERVAL_ONCE) {
                    $this->asyncProcess->execute(
                        new SearchTask($query->getId())
                    );
                }

                $backTo = $request->getSession()->get(AbstractEnhancedSchema::BACK_URL_SESSION_KEY);

                if (empty($backTo)) {
                    $backTo = sprintf('/manager/list.php?Schema=%s', self::getSchema());
                }

                return new RedirectResponse($backTo);
            }
        }

        return $renderer->render($form, true);
    }

    private function stringToArray(?string $string): ?array
    {
        if (empty($string)) {
            return null;
        }

        return array_map('strtoupper', array_map('trim', explode(',', $string)));
    }

    /**
     * @return \DateTime[]
     */
    private function getDates(\DateTime $startDate, \DateTime $endDate): array
    {
        $from = clone $startDate;
        $to = clone $endDate;
        $nowTs = strtotime('today');

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $dates = [];

        do {
            if ($from->getTimestamp() >= $nowTs) {
                $dates[] = clone $from;
            }

            $from->modify('+1 day');
        } while ($from <= $to);

        return $dates;
    }

    /**
     * @return string[]
     */
    private function getFlightClasses(int $flightClasses): array
    {
        $classes = [];

        if ($flightClasses & RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY) {
            $classes[] = RAFlightSearchQuery::API_FLIGHT_CLASS_ECONOMY;
        }

        if ($flightClasses & RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY) {
            $classes[] = RAFlightSearchQuery::API_FLIGHT_CLASS_PREMIUM_ECONOMY;
        }

        if ($flightClasses & RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS) {
            $classes[] = RAFlightSearchQuery::API_FLIGHT_CLASS_BUSINESS;
        }

        if ($flightClasses & RAFlightSearchQuery::FLIGHT_CLASS_FIRST) {
            $classes[] = RAFlightSearchQuery::API_FLIGHT_CLASS_FIRST;
        }

        return $classes;
    }
}
