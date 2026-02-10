<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Security\RealUserDetector;
use AwardWallet\MainBundle\Service\ElasticSearch\Client;
use AwardWallet\MainBundle\Service\ElasticSearch\FieldAggregator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixBotUsersCommand extends Command
{
    public static $defaultName = 'aw:fix-bot-users';
    /**
     * @var Client
     */
    private $esClient;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var FieldAggregator
     */
    private $fieldAggregator;
    /**
     * @var false|int
     */
    private $startTime;
    /**
     * @var false|int
     */
    private $endTime;
    /**
     * @var RealUserDetector
     */
    private $realUserDetector;

    public function __construct(
        Client $esClient,
        Connection $connection,
        LoggerInterface $logger,
        FieldAggregator $fieldAggregator,
        RealUserDetector $realUserDetector
    ) {
        parent::__construct();

        $this->esClient = $esClient;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->fieldAggregator = $fieldAggregator;
        $this->realUserDetector = $realUserDetector;
    }

    public function configure()
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply fixes, otherwise dry run')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'days to analyze', 30)
            ->addOption('invalid-passwords-count', null, InputOption::VALUE_REQUIRED,
                'how many invalid passwords we got in this period to trigger analysis', 15)
            ->addOption('no-valid-accounts', null, InputOption::VALUE_NONE, 'only users with no valid accounts')
            ->addOption('user-age', null, InputOption::VALUE_REQUIRED, 'only users with account younger than N days')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'only users with account younger than N days')
            ->addOption('no-mobile-app', null, InputOption::VALUE_NONE, 'only users without mobile app')
            ->addOption('no-mailboxes', null, InputOption::VALUE_NONE, 'only users without mailboxes')
            ->addOption('login-suffix', null, InputOption::VALUE_REQUIRED, 'only users with this login ending')
            ->addOption('active-days', null, InputOption::VALUE_REQUIRED,
                'only users who were active no more than N days');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $this->startTime = strtotime("00:00", strtotime("-{$this->input->getOption("days")} day"));
        $this->endTime = strtotime("23:59");

        $this->logger->info("searching bot users, who registered user, and checked multiple accounts without success");
        $users = it($this->getUsersWithMultipleCheckErrors())
            ->filter([$this, "filterByActiveDays"])
            ->map([$this, "analyzeUser"])
            ->map([$this, "addUserInfo"])
            ->filter(function (array $row) {
                return $row["Fraud"] !== "deleted";
            })
            ->filter(function (array $row) {
                $userAge = $this->input->getOption('user-age');

                return $userAge === null || $row['UserAge'] <= $userAge;
            })
            ->filter(function (array $row) {
                if ($this->input->getOption('no-valid-accounts')) {
                    return (float) $row['validAccountsScore'] < 0.001;
                }

                return true;
            })
            ->filter(function (array $row) {
                if ($this->input->getOption('no-mobile-app')) {
                    return (float) $row['mobileAppScore'] < 0.001;
                }

                return true;
            })
            ->filter(function (array $row) {
                if ($this->input->getOption('no-mailboxes')) {
                    return (float) $row['mailboxScore'] < 0.001;
                }

                return true;
            })
            ->filter(function (array $row) {
                $suffix = $this->input->getOption('login-suffix');

                if ($suffix !== null) {
                    return substr($row['Login'], -strlen($suffix)) === $suffix;
                }

                return true;
            })
            ->map(function (array $row) {
                $row['Fixed'] = "No";

                if ($this->input->getOption('apply') && $row['Fraud'] !== 'deleted') {
                    $this->logger->info("deleting user {$row['userId']} as bot");
                    $this->connection->executeUpdate("delete from Usr where UserID = ? limit 1", [$row['userId']]);
                    $row['Fixed'] = "Yes";
                }

                return $row;
            })
            ->toArray();
        $this->displayResults($users);

        $this->logger->info("done");

        return 0;
    }

    /**
     * @internal
     */
    public function analyzeUser(array $user): array
    {
        $score = $this->realUserDetector->getScore($user["userId"]);

        return array_merge($user, $score->toArray());
    }

    /**
     * @internal
     */
    public function addUserInfo(array $user): array
    {
        static $query;

        if ($query === null) {
            $query = $this->connection->prepare("select Login, CreationDateTime, LastLogonDateTime, Fraud from Usr where UserID = ?");
        }

        $query->execute([$user["userId"]]);
        $info = $query->fetch(FetchMode::ASSOCIATIVE);

        if ($info === false) {
            $info = [
                "Login" => "deleted",
                "CreationDateTime" => "deleted",
                "LastLogonDateTime" => "deleted",
                "Fraud" => "deleted",
                "UserAge" => "deeleted",
            ];
        }

        return array_merge($user, $info, ["UserAge" => round((time() - strtotime($info['CreationDateTime'])) / 86400)]);
    }

    public function filterByActiveDays(array $user): bool
    {
        $days = $this->input->getOption('active-days');

        if ($days === null) {
            return true;
        }

        static $daysByUser;

        if ($daysByUser === null) {
            $aggs = $this->esClient->aggregate(
                "UserID: " . $this->getFilterByUserId() . " AND first_line_request: *",
                [
                    "user" => [
                        'terms' => [
                            'field' => 'UserID',
                            'size' => 1000000,
                            'order' => [
                                '_count' => 'desc',
                            ],
                        ],
                        'aggs' => [
                            "date" => [
                                'date_histogram' => [
                                    'field' => '@timestamp',
                                    'interval' => '1d',
                                    'time_zone' => 'UTC',
                                    'min_doc_count' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
                $this->startTime,
                $this->endTime
            );

            $daysByUser = it($aggs["user"]["buckets"])
                ->flatMap(function (array $value) {
                    return [$value['key'] => count($value['date']['buckets'])];
                })
                ->toArrayWithKeys()
            ;
        }

        return isset($daysByUser[$user['userId']]) && $daysByUser[$user['userId']] <= $days;
    }

    private function getUsersWithMultipleCheckErrors(): array
    {
        $query = '"account saved" AND NOT context.errorCode: 1 AND NOT context.errorCode: 6';
        $query .= ' AND context.AccountUserID: ' . $this->getFilterByUserId();

        $errors = $this->fieldAggregator->aggregate(
            $query,
            "context.AccountUserID",
            $this->startTime,
            $this->endTime
        );

        $invalidPassCount = $this->input->getOption('invalid-passwords-count');

        $result = it($errors)
            ->mapIndexed(function (int $invalidPassCount, int $userId) {
                return ["userId" => $userId, "invalidPassCount" => $invalidPassCount];
            })
            ->filter(function (array $row) use ($invalidPassCount) {
                return $row["invalidPassCount"] >= $invalidPassCount;
            })
            ->toArray();
        $this->logger->info("got " . count($result) . " users who got invalid password on account check more than {$invalidPassCount} times");

        return $result;
    }

    private function getStartingUserId(): int
    {
        $startingUserId = $this->connection->executeQuery("select min(UserID) from Usr where CreationDateTime >= adddate(now(), -?)",
            [$this->input->getOption('days')])->fetchColumn();

        if ($startingUserId === false || $startingUserId <= 0) {
            throw new \Exception("failed to get starting user id");
        }
        $this->logger->info("starting user id: {$startingUserId}");

        return $startingUserId;
    }

    private function displayResults(array $users): void
    {
        if (count($users) === 0) {
            $this->logger->info("no users found");

            return;
        }

        $table = new Table($this->output);
        $table->setHeaders(array_keys(reset($users)));
        $table->setRows($users);
        $table->render();
        $this->logger->info("total " . count($users) . " users found");
    }

    private function getFilterByUserId(): string
    {
        $userId = $this->input->getOption('user-id');

        if ($userId !== null) {
            return $userId;
        }

        return '>=' . $this->getStartingUserId();
    }
}
