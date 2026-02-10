<?php

namespace AwardWallet\MainBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MysqlBenchmarkCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \PDO
     */
    private $connection;
    /**
     * @var string
     */
    private $server;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('aw:mysql-benchmark')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'mysql host', 'mysql')
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'test mode: split or repeat', 'split')
            ->addOption('log-file', null, InputOption::VALUE_REQUIRED,
                'log file, from general_log_file mysqld options')
            ->addOption('log-table', null, InputOption::VALUE_REQUIRED,
                'log table, from mysql.general_log, dumped with mysql option log_output=table')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'load only N queries from log')
            ->addOption('unique', null, InputOption::VALUE_NONE, 'exclude duplicate queries')
            ->addOption('users', null, InputOption::VALUE_REQUIRED, 'simulate N users', 10)
            ->addOption('iterations', null, InputOption::VALUE_REQUIRED, 'how many times to repeat queries from general-log', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->server = $input->getOption('server');
        $this->openConnection();

        if (!empty($input->getOption('log-file')) && !empty($input->getOption('log-table'))) {
            throw new \InvalidArgumentException('log-file and log-table are mutual exclusive');
        }

        if (!empty($input->getOption('log-file'))) {
            $queryTemplates = $this->loadQueriesFromFile($input->getOption('log-file'), $input->getOption('limit'));
        } elseif (!empty($input->getOption('log-table'))) {
            $queryTemplates = $this->loadQueriesFromTable($input->getOption('log-table'), $input->getOption('limit'));
        } else {
            throw new \InvalidArgumentException('log-file or log-table required');
        }

        $queryTemplates = $this->filterQueries($queryTemplates);
        $this->logger->info("loaded " . count($queryTemplates) . " queries");

        if ($input->getOption('unique')) {
            $queryTemplates = array_unique($queryTemplates);
            $this->logger->info("unique queries: " . count($queryTemplates));
        }

        if ($input->getOption('mode') === 'repeat') {
            $queryTemplates = array_map([$this, 'addPlaceHolders'], $queryTemplates);
            $users = $this->loadUsers($input->getOption('users'));
            $queries = $this->prepareQueries($queryTemplates, $users);
        } else {
            $queries = $this->splitToChunks($queryTemplates, $input->getOption('users'));
        }

        $this->forkAndRun($queries, $input->getOption('iterations'));

        return 0;
    }

    private function openConnection()
    {
        $this->connection = new \PDO('mysql:dbname=awardwallet;host=' . $this->server, 'awardwallet', 'awardwallet', [
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function loadQueriesFromFile(string $file, ?int $limit): array
    {
        $this->logger->info("loading queries from $file");
        $result = [];
        $handle = fopen($file, "r");

        if ($handle === false) {
            throw new \Exception("Failed to open $file");
        }

        /**
         * format:
         * 180521  6:35:49        5 Query    SELECT t0.UserID AS UserID_1, t0.Login AS Login_2, t0.Pass AS Pass_3, t0.FirstName AS FirstName_4, t0.MidName AS MidName_5, t0.LastName AS LastName_6, t0.Email AS Email_7, t0.Age AS Age_8, t0.Address1 AS Address1_9, t0.Address2 AS Address2_10, t0.City AS City_11, t0.Zip AS Zip_12, t0.Country AS Country_13, t0.Title AS Title_14, t0.Company AS Company_15, t0.Phone1 AS Phone1_16, t0.IsNewsSubscriber AS IsNewsSubscriber_17, t0.CreationDateTime AS Creatio.
         */
        $addResult = function (string $line) use (&$result) {
            $this->logger->debug("loaded line: " . $line);
            $result[] = $line;
        };

        $sql = "";
        $inQuery = false;

        do {
            $line = fgets($handle);

            if (feof($handle)) {
                break;
            }

            if ($line === false) {
                throw new \Exception("Failed to read $file");
            }
            $isStartLine = preg_match("#^(\d+\s+\d+:\d+:\d+\s+)?(\s*\d+\s+)?\s*(Query|Connect|Quit)(\s+(.*))?$#ims", $line, $matches);

            if ($inQuery) {
                if ($isStartLine) {
                    $inQuery = false;
                    $addResult($sql);
                    $sql = "";
                } else {
                    $sql .= $line;
                }
            }

            if (!$inQuery) {
                if ($isStartLine && preg_match('#^select\s+#ims', $matches[5])) {
                    $sql = $matches[5];
                    $inQuery = true;
                }
            }
        } while ($limit === null || count($result) < $limit);

        if ($inQuery && ($limit === null || count($result) < $limit)) {
            $addResult($sql);
        }

        fclose($handle);

        return $result;
    }

    private function loadQueriesFromTable(string $tableName, ?int $limit): array
    {
        $result = [];

        $q = $this->connection->query("select * from $tableName" . ($limit ? " limit $limit" : ""));

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            if (($row['command_type'] === 'Query') && preg_match('#^select\s+#ims', $row['argument'])) {
                $result[] = $row['argument'];
            }
        }
        $q->closeCursor();

        return $result;
    }

    private function filterQueries(array $queries)
    {
        return array_filter($queries, function (string $sql) {
            return
                stripos($sql, 'information_schema') === false
                && stripos($sql, '@@aurora') === false
                && stripos($sql, 'mysql.') === false
            ;
        });
    }

    private function splitToChunks(array $rows, int $count): array
    {
        $this->logger->info("splitting " . count($rows) . " to $count chunks");
        $result = array_fill(0, $count, []);

        foreach (array_values($rows) as $index => $row) {
            $result[$index % $count][] = $row;
        }

        return $result;
    }

    private function addPlaceHolders(string $line): string
    {
        $line = preg_replace("#(\bUserID\s*=\s*)('?\d+'?)#ims", '${1}%UserID%', $line);
        $line = preg_replace("#(\bAccountID\s*=\s*)('?\d+'?)#ims", '${1}%AccountID%', $line);

        return $line;
    }

    private function loadUsers(int $count): array
    {
        $this->logger->info("loading $count users");
        $maxUserId = $this->connection->query("select max(UserID) from Usr")->fetch(\PDO::FETCH_COLUMN);
        $this->logger->info("max user id: $maxUserId");

        $result = [];
        $accounts = 0;

        for ($n = 0; $n < $count; $n++) {
            $startUserId = round(($maxUserId / $count) * $n);
            $userId = $this->connection
                ->query("select UserID from Account where UserID >= $startUserId order by UserID limit 1")
                ->fetch(\PDO::FETCH_COLUMN)
            ;

            if ($userId !== false) {
                $result[$userId] = $this->connection
                    ->query("select AccountID from Account where UserID = $userId limit 5")
                    ->fetchAll(\PDO::FETCH_COLUMN)
                ;
                $accounts += count($result[$userId]);
            }
        }

        $this->logger->info("loaded " . count($result) . " users, and $accounts accounts");

        return $result;
    }

    private function prepareQueries(array $queryTemplates, array $users)
    {
        $userQueries = count(array_filter($queryTemplates, function (string $sql) { return strpos($sql, '%UserID%') !== false; }));
        $accountQueries = count(array_filter($result, function (string $sql) { return strpos($sql, '%AccountID%') !== false; }));
        $this->logger->info("loaded " . count($result) . " queries, user queries: $userQueries, account queries: $accountQueries");
        $this->logger->info("preparing queries for " . count($users) . " users with " . count($queryTemplates) . " query templates");
        $queries = [];
        $total = 0;

        foreach ($users as $userId => $accounts) {
            $queries[$userId] = $this->replacePlaceHolders($queryTemplates, $userId, $accounts);

            // validate
            foreach ($queries[$userId] as $sql) {
                try {
                    $this->connection->query($sql);
                } catch (\PDOException $exception) {
                    throw new \Exception("failed to validate sql: " . $sql . ", error: " . $exception->getMessage());
                }
            }
            $total += count($queries[$userId]);
        }
        $this->logger->info("prepared $total queries");

        return $queries;
    }

    private function replacePlaceholders(array $queryTemplates, int $userId, array $accounts)
    {
        $result = [];

        foreach ($queryTemplates as $sql) {
            $sql = str_replace('%UserID%', $userId, $sql);

            if (strpos($sql, '%AccountID%') !== false) {
                foreach ($accounts as $accountId) {
                    $sql = str_replace('%AccountID%', $accountId, $sql);
                    $result[] = $sql;
                }
            } else {
                $result[] = $sql;
            }
        }

        return $result;
    }

    private function forkAndRun(array $userQueries, int $iterations)
    {
        $this->logger->info("running benchmark with " . count($userQueries) . " users, $iterations iterations");
        $startTime = microtime(true);
        $this->connection = null;
        $pids = [];

        foreach ($userQueries as $userId => $querySet) {
            $pid = pcntl_fork();

            if ($pid === 0) {
                // child
                $this->openConnection();
                $reportTime = time();

                for ($n = 0; $n < $iterations; $n++) {
                    foreach ($querySet as $index => $sql) {
                        if ((time() - $reportTime) >= 30) {
                            $reportTime = time();

                            if (random_int(1, count($userQueries)) === 1) {
                                $this->logger->info("pid: " . getmypid() . ", iteration $n, queries: $index");
                            }
                        }

                        try {
                            $q = $this->connection->query($sql);
                        } catch (\PDOException $exception) {
                            throw new \Exception($exception->getMessage() . " while running: " . $sql);
                        }
                        $rows = 0;

                        while ($q->fetch(\PDO::FETCH_ASSOC) && $rows < 500) {
                            $rows++;
                        }
                        $q->closeCursor();
                    }
                }
                $this->connection = null;

                exit;
            } elseif ($pid > 0) {
                $pids[] = $pid;
            } else {
                throw new \Exception("failed to fork");
            }
        }
        $this->logger->info("threads created and running");
        $errors = 0;

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $exitCode = pcntl_wexitstatus($status);

            if ($exitCode !== 0) {
                $errors++;
            }
        }
        $this->logger->info("done, errors: $errors. time: " . round(microtime(true) - $startTime, 3));
    }
}
