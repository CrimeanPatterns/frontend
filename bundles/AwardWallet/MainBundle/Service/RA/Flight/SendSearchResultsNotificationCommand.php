<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Entity\RAFlightSearchRoute;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary\BestMileageDeals;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SendSearchResultsNotificationCommand extends Command
{
    public static $defaultName = 'aw:ra:send-search-results-notification';

    private RequestProgressTracker $requestProgressTracker;

    private Connection $connection;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    private string $protoAndHost;

    private AppBot $appBot;

    private Mailer $mailer;

    private FlightDealSubscriber $flightDealSubscriber;

    private EmailFormatter $emailFormatter;

    public function __construct(
        RequestProgressTracker $requestProgressTracker,
        Connection $connection,
        EntityManagerInterface $entityManager,
        LoggerFactory $loggerFactory,
        string $protoAndHost,
        AppBot $appBot,
        Mailer $mailer,
        FlightDealSubscriber $flightDealSubscriber,
        EmailFormatter $emailFormatter
    ) {
        parent::__construct();

        $this->requestProgressTracker = $requestProgressTracker;
        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor([
            'class' => 'ResultsNotification',
        ]));
        $this->protoAndHost = $protoAndHost;
        $this->appBot = $appBot;
        $this->mailer = $mailer;
        $this->flightDealSubscriber = $flightDealSubscriber;
        $this->emailFormatter = $emailFormatter;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send search results notification');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $routesStmt = $this->connection->prepare('
            SELECT rs.RAFlightSearchRequestID, rs.RAFlightSearchRouteID
            FROM RAFlightSearchResponse rs
                JOIN RAFlightSearchRequest rq ON rs.RAFlightSearchRequestID = rq.RAFlightSearchRequestID
            WHERE rq.RAFlightSearchQueryID = ?
        ');
        $processed = 0;
        $startTime = microtime(true);

        foreach ($this->requestProgressTracker->getCompletedQueries() as $query) {
            $queryId = (int) $query['RAFlightSearchQueryID'];
            $totalRequests = $query['Total'];
            $raQuery = $this->entityManager->find(RAFlightSearchQuery::class, $queryId);

            if (!$raQuery) {
                $this->logger->info(sprintf('query not found, id: %d', $queryId));

                continue;
            }

            if ($raQuery->isAutoCreated() && !empty($mileValue = $raQuery->getMileValue())) {
                $syncQueryId = $this->flightDealSubscriber->syncByMileValue($mileValue->getId());

                if (is_null($syncQueryId)) {
                    $this->logger->info(sprintf('query is not valid, id: %d', $queryId));

                    continue;
                }

                if ($syncQueryId !== $queryId) {
                    $this->logger->info(sprintf('query is not sync, id: %d', $queryId));

                    continue;
                }

                $this->entityManager->refresh($raQuery);
            }

            if ($raQuery->isDeleted()) {
                $this->logger->info(sprintf('query is deleted, id: %d', $queryId));

                continue;
            }

            $q = $routesStmt->executeQuery([$queryId]);
            $routes = [];

            while ($row = $q->fetchAssociative()) {
                if (!isset($routes[$row['RAFlightSearchRequestID']])) {
                    $routes[$row['RAFlightSearchRequestID']] = [];
                }

                $routes[$row['RAFlightSearchRequestID']][] = $row['RAFlightSearchRouteID'];
            }

            $routes = it($routes)
                ->mapIndexed(function (array $routeIds, string $requestId) {
                    $routesEntities = $this->entityManager->getRepository(RAFlightSearchRoute::class)->findBy([
                        'id' => $routeIds,
                    ]);

                    $isOnlyArchived = it($routesEntities)
                        ->all(fn (RAFlightSearchRoute $route) => $route->isArchived());

                    if ($isOnlyArchived) {
                        $this->logger->info(sprintf('all routes for request %s are archived', $requestId));

                        return null;
                    }

                    return $routesEntities;
                })
                ->filterNotNull()
                ->flatten()
                ->toArray();

            if (empty($routes)) {
                $this->logger->info(sprintf('no routes found for query %d', $queryId));

                continue;
            }

            if ($raQuery->isAutoCreated()) {
                $this->logger->info(sprintf('notifying for auto created query %d', $queryId));

                $user = $raQuery->getMileValue() && $raQuery->getMileValue()->getTrip()
                    ? $raQuery->getMileValue()->getTrip()->getUser()
                    : $raQuery->getUser();

                if (!in_array($user->getId(), FlightDealSubscriber::STAFF_USER_IDS)) {
                    $this->logger->info(sprintf('skipping slack notification for user %d', $user->getId()));
                } else {
                    $this->sendToSlack($raQuery, $routes);
                }

                $this->sendEmail($raQuery, $routes);
            } else {
                $this->sendToSlack($raQuery, $routes);
            }

            $processed++;
            $this->requestProgressTracker->deleteRequests($queryId);
            $this->logger->info(sprintf('processed results for query %d', $queryId), [
                'total' => $totalRequests,
            ]);

            if (($processed % 100) == 0) {
                $this->entityManager->clear();
                $now = microtime(true);
                $speed = round(100 / ($now - $startTime), 2);
                $this->logger->info(sprintf('processed %d queries, mem: %s Mb, speed: %s q/s',
                    $processed,
                    round(memory_get_usage(true) / 1024 / 1024, 1),
                    $speed
                ));
                $startTime = $now;
            }
        }

        $this->logger->info(sprintf('done, processed %d queries', $processed));

        return 0;
    }

    /**
     * @param RAFlightSearchRoute[] $routes
     */
    private function sendToSlack(RAFlightSearchQuery $query, array $routes): void
    {
        $containsFlag = it($routes)
            ->any(fn (RAFlightSearchRoute $route) => $route->isFlag());

        if ($containsFlag) {
            $this->logger->info(sprintf('query %d contains flagged routes', $query->getId()));
        }

        $link = sprintf(
            '%s/manager/list.php?RAFlightSearchQueryID=%d&Schema=RAFlightSearchRoute&Archived=0',
            $this->protoAndHost,
            $query->getId()
        );
        $routes = sprintf('[%s] -> [%s]', implode(', ', $query->getDepartureAirports()), implode(', ', $query->getArrivalAirports()));

        if ($query->isAutoCreated() && $query->getMileValue() && $query->getMileValue()->getTrip()) {
            $userName = $query->getMileValue()->getTrip()->getUser()->getFullName();
        } elseif ($query->getUser()) {
            $userName = $query->getUser()->getFullName();
        } else {
            $userName = 'Unknown User';
        }

        $text = sprintf(
            $containsFlag ? 'Flagged Result Found for Search %s for %s' : 'Flights found for Search %s for %s',
            $routes,
            $userName
        );

        // send to slack
        $this->appBot->send(Slack::CHANNEL_AW_AWARD_ALERTS, [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => $text,
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => sprintf(
                            '<%s|View Search Results>',
                            $link
                        ),
                    ],
                ],
            ],
        ]);
    }

    private function sendEmail(RAFlightSearchQuery $query, array $routes): void
    {
        $mileValue = $query->getMileValue();

        if (!$mileValue) {
            $this->logger->info(sprintf('no mile value found for query %d', $query->getId()));

            return;
        }

        $trip = $mileValue->getTrip();

        if (!$trip) {
            $this->logger->info(sprintf('no trip found for query %d', $query->getId()));

            return;
        }

        $user = $trip->getUser();

        try {
            $emailData = $this->emailFormatter->format($mileValue, $routes, $user);

            if (empty($emailData)) {
                $this->logger->info(sprintf('no email data found for query %d', $query->getId()));

                return;
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->critical(sprintf('formatting error for query %d: %s', $query->getId(), $e->getMessage()));

            return;
        }

        // log email data for debugging
        // take real test data for test email, then delete
        $this->logger->info('best mileage deals email data', [
            'userId_int' => $user->getId(),
            'emailData_array' => $emailData,
        ]);

        $template = new BestMileageDeals($user);
        $template->data = $emailData;
        // TODO: remove this line after testing
        $template->setEmail('marketing@AwardWallet.com');
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message);
    }
}
