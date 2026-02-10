<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Service\RA\Flight\DTO\ApiSearchRequest;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SearchCommand extends Command
{
    public static $defaultName = 'aw:ra:flight-search';

    private Api $api;

    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Api $api, Connection $connection, LoggerFactory $loggerFactory)
    {
        parent::__construct();

        $this->api = $api;
        $this->connection = $connection;
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor([
            'class' => 'SearchCommand',
        ]));
    }

    protected function configure()
    {
        $this
            ->setDescription('Search for flights')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Test mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('started');
        $daily = RAFlightSearchQuery::SEARCH_INTERVAL_DAILY;
        $weekly = RAFlightSearchQuery::SEARCH_INTERVAL_WEEKLY;
        $filter = '';

        if ($input->getOption('test')) {
            $filter = " AND Parsers = 'test'";
        }

        $stmt = $this->connection->executeQuery("
            SELECT RAFlightSearchQueryID
            FROM RAFlightSearchQuery
            WHERE 
                (
                    (
                        SearchInterval = $daily
                        AND (LastSearchDate IS NULL OR DAYOFYEAR(LastSearchDate) <> DAYOFYEAR(NOW()))
                    ) OR (
                        SearchInterval = $weekly
                        AND (LastSearchDate IS NULL OR YEARWEEK(LastSearchDate, 1) <> YEARWEEK(NOW(), 1))
                    )
                ) AND (
                    DepDateFrom >= CURRENT_DATE()
                    OR DepDateTo >= CURRENT_DATE()
                )
                AND DeleteDate IS NULL
                $filter
            ORDER BY LastSearchDate ASC
        ");

        $queries = 0;
        $failedQueries = [];
        $successRequests = 0;
        $errorRequests = 0;
        $errors = [];

        while ($row = $stmt->fetchAssociative()) {
            $queryId = $row['RAFlightSearchQueryID'];

            try {
                $result = $this->api->search($queryId);
                $queries++;
                $successRequests += \count($result->getSuccessRequests());
                $errorRequests += \count($requestsWithError = $result->getRequestsWithError());

                if (\count($requestsWithError) > 0) {
                    $key = 'query-' . $queryId;

                    if (!isset($errors[$key])) {
                        $errors[$key] = [];
                    }

                    $errors[$key] = array_merge(
                        $errors[$key],
                        it($requestsWithError)->map(function (ApiSearchRequest $request) {
                            return sprintf(
                                'parser: %s, depCode: %s, depDate: %s, arrCode: %s, cabin: %s, adults: %d, error: %s',
                                $request->getParser(),
                                $request->getDepCode(),
                                $request->getDepDate()->format('Y-m-d'),
                                $request->getArrCode(),
                                $request->getCabin(),
                                $request->getAdults(),
                                $request->getError()
                            );
                        })->toArray()
                    );
                }
            } catch (SearchException $e) {
                $failedQueries['query-' . $queryId] = $e->getMessage();
            }
        }

        $this->logger->info(sprintf('finished, processed %d queries', $queries), [
            'queries' => $queries,
            'failedQueries' => $failedQueries,
            'successRequests' => $successRequests,
            'errorRequests' => $errorRequests,
            'errors' => $errors,
        ]);

        return 0;
    }
}
