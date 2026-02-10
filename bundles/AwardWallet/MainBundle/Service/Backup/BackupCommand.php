<?php

namespace AwardWallet\MainBundle\Service\Backup;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\SiegeModeDetector;
use AwardWallet\MainBundle\Service\Backup\Model\ProcessorInterest;
use AwardWallet\MainBundle\Service\Backup\Model\ProcessorOptions;
use Doctrine\DBAL\Connection;
use Ifsnop\Mysqldump\CompressorInterface;
use Ifsnop\Mysqldump\Mysqldump;
use Lifo\IPC\ProcessPool;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCommand extends Command
{
    public const KEEP_PASSWORD_USERS = [7, 49290, 246369];
    public const DEVELOPER_PASSWORD = 'Awdeveloper12';

    public const NULLABLE_FIELDS = [['Provider', 'Assignee']];
    public static $defaultName = 'aw:backup';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Connection
     */
    private $mainConnection;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $keepUsers;

    /**
     * @var int
     */
    private $maxUserId;

    /**
     * @var array
     */
    private $tables;
    /**
     * @var array
     */
    private $csvFiles = [];

    private $backupFilters = [
        'AdStat' => 'AdStat.StatDate >= adddate(now(), interval -3 month)',
        'Coupon' => '(Coupon.UserID is not null or (Coupon.UserID is null and Coupon.Code not like "Invite-%" and Coupon.CreationDate <> "0000-00-00" and (Coupon.EndDate is null or Coupon.EndDate > now())))',
        'Deal' => '(Deal.EndDate >= adddate(now(), interval -3 month) or Deal.EndDate is null)',
        'EmailStat' => 'EmailStat.StatDate >= adddate(now(), interval -3 month)',
        'InviteCode' => 'InviteCode.CreationDate >= adddate(now(), interval -3 month)',
        'Prospect' => 'Prospect.LastUseDate >= adddate(now(), interval -3 month)',
        'AAMembership' => 'AAMembership.SnapDate >= adddate(now(), -2)',
        'OfferLog' => 'OfferLog.ActionDate >= adddate(now(), -7)',
        'ContactUs' => 'ContactUs.DateSubmitted >= adddate(now(), -7)',
        'ExtensionStat' => 'ExtensionStat.ErrorDate >= adddate(now(), -7)',
        'AbRequest' => 'AbRequest.LastUpdateDate >= adddate(now(), -14) AND AbRequest.UserID is not null',
        'Visit' => 'Visit.VisitDate >= adddate(now(), -7)',
        'Cart' => 'Cart.UserID is not null',
        'RedirectHit' => 'RedirectHit.HitDate >= adddate(now(), -7)',
        'RAFlight' => 'RAFlight.SearchDate >= adddate(now(), -1)',
        'RAFlightStat' => 'RAFlightStat.LastSeen >= adddate(now(), -1)',
        'RAFlightSegment' => 'RAFlightSegment.LastParsedDate >= adddate(now(), -1)',
        'RAFlightRouteSearchVolume' => 'RAFlightRouteSearchVolume.LastSearch >= adddate(now(), -1)',
    ];

    private $refIgnores = [
        'Trip' => ['UserAgentID', 'TravelPlanID', 'AccountID'],
        'Reservation' => ['UserAgentID', 'TravelPlanID', 'AccountID'],
        'Rental' => ['UserAgentID', 'TravelPlanID', 'AccountID'],
        'Restaurant' => ['UserAgentID', 'TravelPlanID', 'AccountID'],
        'TripSegment' => ['TravelPlanID', 'TripInfoID'],
        'Account' => ['UserAgentID'],
        'AccountBalance' => ['SubAccountID'],
        'AccountHistory' => ['SubAccountID'],
        'AccountProperty' => ['SubAccountID'],
    ];

    private $refGroups = [
        'DiffChange' => 'OR',
    ];

    /**
     * @var InputInterface
     */
    private $input;

    private $lastUpdate;

    private $strictJoin = false;

    private $useJoins = true;

    private $currentTable;

    private $appendQueries = [];
    /**
     * @var iterable|BackupProcessorInterface[]
     */
    private iterable $backupProcessors;
    private ProcessorInterest $processorInterest;

    private Users $users;
    private Connection $replicaConnection;
    private AppProcessor $appProcessor;
    private $databaseNameParameter;
    private $databaseUserParameter;
    private $lastRowCount = 0;

    public function __construct(
        iterable $backupProcessors,
        Users $users,
        LoggerInterface $logger,
        Connection $connection,
        Connection $replicaConnection,
        AppProcessor $appProcessor,
        $databaseNameParameter,
        $databaseUserParameter
    ) {
        $this->backupProcessors = $backupProcessors;
        $this->users = $users;
        $this->logger = $logger;
        $this->mainConnection = $connection;
        $this->replicaConnection = $replicaConnection;
        $this->appProcessor = $appProcessor;
        $this->databaseNameParameter = $databaseNameParameter;
        $this->databaseUserParameter = $databaseUserParameter;

        parent::__construct();
    }

    public function onProgress($event, $extra)
    {
        static $tableStart;

        $time = time();

        switch ($event) {
            case 'table-start':
                $this->logger->info($extra['sql']);
                $this->currentTable = $extra['table'];
                $this->lastRowCount = 0;
                $this->lastUpdate = $time;
                $tableStart = time();

                break;

            case 'export-row':
                if (($time - $this->lastUpdate) > 60) {
                    $duration = $time - $this->lastUpdate;
                    $speed = ($extra['row-count'] - $this->lastRowCount) / $duration;
                    $this->logger->info("processed {$extra['row-count']} rows in {$extra['table']}, speed " . number_format(round($speed), 0, '.', ' ') . " rows/sec");
                    $this->lastUpdate = $time;
                    $this->lastRowCount = $extra['row-count'];
                }

                break;

            case 'table-end':
                $this->logger->info("exported {$extra['row-count']} rows from {$extra['table']} in " . (time() - $tableStart) . " seconds");

                break;
        }
    }

    protected function configure()
    {
        $this
            ->setDescription('Backup utilities')
            ->addArgument('action', InputArgument::REQUIRED, 'backup-clean | backup-1000 | backup-users | backup-user | copy-profiles')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'backup file')
            ->addOption('infile', null, InputOption::VALUE_NONE, 'use LOAD DATA INFILE, csv files will be saved to same dir as --file')
            ->addOption('threads', null, InputOption::VALUE_REQUIRED, 'dump in N threads, targetHost required')
            ->addOption('tables', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'tables')
            ->addOption('targetHost', null, InputOption::VALUE_REQUIRED, 'target server host, required for threads')
            ->addOption('targetPort', null, InputOption::VALUE_REQUIRED, 'target server port, required for threads', '3306')
            ->addOption('startDate', null, InputOption::VALUE_REQUIRED)
            ->addOption('sourceHost', null, InputOption::VALUE_REQUIRED)
            ->addOption('sourcePassword', null, InputOption::VALUE_REQUIRED)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('table', null, InputOption::VALUE_REQUIRED)
            ->addOption('thread-buffer', null, InputOption::VALUE_REQUIRED, 'how much memory will be given to each thread, Mb', 'auto')
        ;

        $processorOptions = new ProcessorOptions($this);

        foreach ($this->backupProcessors as $processor) {
            if ($processor instanceof ProcessorOptionsInterface) {
                $processor->registerOptions($processorOptions);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->logger = new Logger('backup', [new PsrHandler($this->logger)], [function (array $record) {
            $record['context']['mem'] = (int) round(memory_get_usage() / 1024 / 1024);
            $record['context']['pid'] = getmypid();
            $record['extra']['worker'] = 'backup';

            return $record;
        }]);

        $this->logger->info("Updated!");
        $this->lastUpdate = time();
        $this->mainConnection->exec("SET wait_timeout=28800; SET interactive_timeout=28800;");
        $this->processorInterest = new ProcessorInterest($input, $input->getArgument('action') === 'backup-clean');

        if (empty($input->getOption('sourceHost'))) {
            $this->connection = $this->replicaConnection;
        } else {
            $params = $this->mainConnection->getParams();
            $params['host'] = $input->getOption('sourceHost');

            if (!empty($input->getOption('sourcePassword'))) {
                $params['password'] = $input->getOption('sourcePassword');
            }
            $this->connection = new Connection($params, $this->mainConnection->getDriver());
        }
        $this->exploreTables();

        $this->file = $input->getOption("file");

        switch ($input->getArgument('action')) {
            case 'backup-1000':
                $this->backup1000();

                break;

            case 'backup-users':
                $this->backupUsers($input->getOption("startDate"), $input->getOption('tables'));

                break;

            case 'backup-user':
                $this->backupUser($input->getOption("userId"));

                break;

            case 'copy-profiles':
                $this->copyProfiles($input->getOption("startDate"));

                break;

            case 'backup-clean':
                $this->backupClean($input->getOption('tables'));

                break;

            default:
                throw new \Exception("Invalid action: " . $input->getArgument('action'));
        }

        $this->logger->info("done");
    }

    private function backupClean($tables)
    {
        $this->logger->info("making clean backup");

        $settings = $this->getCleanDumpSettings();

        if (is_array($tables) && !empty($tables)) {
            $settings['include-tables'] = $tables;
        }

        $this->dump($settings);

        //        foreach ($this->processorInterest->getPostProcessors() as $postProcessor) {
        //            $postProcessor();
        //        }
    }

    private function backup1000()
    {
        $this->logger->info("backing up last 1000 users");
        $this->scanUsers();
        $dumpSettings = $this->getCleanDumpSettings();
        $dumpSettings['no-data'] = array_merge($dumpSettings['no-data'], [
            'AirHelpCompensation',
            'AppleUserInfo',
            'BlogLinkClick',
            'CardImage',
            'CardMatcherReport',
            'DoNotSend',
            'EmailNDR',
            'EmailNDRContent',
            'FlightInfoLog',
            'ImpersonateLog',
            'ItineraryCheckError',
            'LoungeSource',
            'MerchantCacheByCardStatsTemp',
            'MerchantCacheByMultiplierStatsTemp',
            'MerchantPatternPeriodlyStatTemp',
            'MerchantPeriodlyStatTemp',
            'MerchantPopularShoppingCategoryStatsTemp',
            'MerchantReportExpectedTransactionsStatsTemp',
            'MerchantReportTransactionsStatsTemp',
            'MerchantTransactionsLast3MonthsStatsTemp',
            'MerchantTransactionsStatsTemp',
            'Outbox',
            'QsCreditCardHistory',
            'QsTransaction',
            'RACalendar',
            'RAFlight',
            'SkyScannerDeals',
            'TpoHotel',
            'UserIPPoint',
            'UserTripTargeting',
            'UsrLastLogonPoint',
        ]);

        $tables = $this->input->getOption('tables');
        $dumpSettings = $this->addLiteDumpConditions($dumpSettings, $tables);
        $this->dump($dumpSettings);
    }

    private function backupUser($userId)
    {
        $this->logger->info("backing up user $userId");
        $this->useJoins = false;

        $schemaManager = new \TSchemaManager();
        $fields = $this->connection->executeQuery("select * from Usr where UserID = ?", [$userId])->fetch(\PDO::FETCH_ASSOC);
        $excludeRows = [];
        $rows = $schemaManager->ChildRows("Usr", $fields, $excludeRows, null, true);
        array_unshift($rows, $schemaManager->SingleRow("Usr", $fields));

        $ids = [];

        foreach ($rows as $row) {
            if (!isset($ids[$row['Table']])) {
                $ids[$row['Table']] = [];
            }
            $ids[$row['Table']][] = $row['ID'];
        }
        $this->logger->info("loaded " . count($rows) . " rows from " . count(array_keys($ids)) . " tables");

        $dumpSettings = $this->getCleanDumpSettings();

        foreach ($ids as $table => $tableIds) {
            $this->logger->info($table . ": " . count($tableIds));
            $this->backupFilters[$table] = "{$table}.{$schemaManager->Tables[$table]["PrimaryKey"]} in (" . implode(", ", array_map(function ($val) { return "'" . addslashes($val) . "'"; }, $tableIds)) . ")";
        }
        $dumpSettings['no-create-info'] = true;
        $dumpSettings['lock-tables'] = false;
        $dumpSettings['add-locks'] = false;
        $dumpSettings['disable-keys'] = false;
        $dumpSettings['no-autocommit'] = false;
        $dumpSettings['skip-comments'] = true;
        $dumpSettings['extended-insert'] = false;
        $dumpSettings['complete-insert'] = true;

        $dumpSettings = $this->addLiteDumpConditions($dumpSettings, array_keys($ids));
        $this->dump($dumpSettings);

        $deletes = "";

        foreach ($ids as $table => $tableIds) {
            $deletes .= "delete from `$table` where {$schemaManager->Tables[$table]['PrimaryKey']} in (" . implode(", ", array_map(function ($val) { return "'" . addslashes($val) . "'"; }, $tableIds)) . ");\n";
        }

        file_put_contents(
            $this->file,
            "SET FOREIGN_KEY_CHECKS = 0;\n"
            .
            $deletes
            .
            file_get_contents($this->file)
            .
            "SET FOREIGN_KEY_CHECKS = 1;\n"
        );
    }

    private function getTablesWithoutReferencesTo(string $tableName): array
    {
        $result = [];

        foreach ($this->tables as $aTableName => $table) {
            $links = $this->getLinksTo($aTableName, $tableName, [], []);

            if (count($links) > 0) {
                continue;
            }

            $this->logger->info("skipping $aTableName, has no links to $tableName");
            $result[] = $aTableName;
        }

        return $result;
    }

    private function addLiteDumpConditions($dumpSettings, $tables)
    {
        if (!empty($tables)) {
            $dumpSettings['include-tables'] = $tables;
        }

        foreach (empty($tables) ? array_map(function ($table) { return $table['name']; }, $this->tables) : $tables as $table) {
            if (preg_match('#(DELETE|TEST)$#', $table)) {
                continue;
            }
            $conditions = $this->getDumpConditions($table);

            if (!empty($conditions)) {
                if (!isset($dumpSettings['table-settings'][$table])) {
                    $dumpSettings['table-settings'][$table] = [];
                }

                if (isset($conditions['where'])) {
                    if (!empty($dumpSettings['table-settings'][$table]['where'])) {
                        $dumpSettings['table-settings'][$table]['where'] .= " AND " . $conditions['where'];
                    } else {
                        $dumpSettings['table-settings'][$table]['where'] = $conditions['where'];
                    }
                }

                if (isset($conditions['joins'])) {
                    if (!empty($dumpSettings['table-settings'][$table]['joins'])) {
                        $dumpSettings['table-settings'][$table]['joins'] .= "\n" . $conditions['joins'];
                    } else {
                        $dumpSettings['table-settings'][$table]['joins'] = $conditions['joins'];
                    }
                }
            }
        }

        if (StringUtils::isNotEmpty($dumpSettings['table-settings']['GroupUserLink']['where'] ?? '')) {
            $dumpSettings['table-settings']['GroupUserLink']['where'] .= ' AND (GroupUserLink.SiteGroupID <> 71)';
        } else {
            $dumpSettings['table-settings']['GroupUserLink']['where'] = '(GroupUserLink.SiteGroupID <> 71)';
        }

        $dumpSettings['table-settings']['Param']['where'] = "Name = 'merchant_report_version' OR Name = 'clickhouse_db_version' OR Name = 'unitedAnswerJson'";
        $dumpSettings['table-settings']['MileValue']['where'] = "MileValue.CreateDate > adddate(now(), -7)";
        $dumpSettings['table-settings']['Fingerprint']['where'] = "LastSeen > adddate(now(), -4)";

        return $dumpSettings;
    }

    private function copyProfiles($startDate)
    {
        $users = $this->connection->executeQuery("select 
            u.UserID,
            u.Login,
            u.AccountLevel,
            u.PaypalRecurringProfileID,
            u.Subscription,
            u.PlusExpirationDate
        from
            Usr u
            join Cart c on u.UserID = c.UserID
        where
            c.PayDate >= '" . addslashes($startDate) . "'")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $existing = $this->mainConnection->executeQuery("select 
                u.UserID,
                u.Login,
                u.AccountLevel,
                u.PaypalRecurringProfileID,
                u.Subscription,
                u.PlusExpirationDate
            from 
                Usr u 
            where 
                u.Login = :login", ["login" => $user['Login']])->fetch(\PDO::FETCH_ASSOC);

            if (empty($existing)) {
                $this->logger->warning("user not found: {$user['Login']}");

                continue;
            }
            $update = [];

            if ($existing['AccountLevel'] != $user['AccountLevel']) {
                $update["AccountLevel"] = $user['AccountLevel'];
            }

            if (!empty($user['PaypalRecurringProfileID']) && $existing['PaypalRecurringProfileID'] != $user['PaypalRecurringProfileID']) {
                $update["PaypalRecurringProfileID"] = "'" . addslashes($user['PaypalRecurringProfileID']) . "'";
            }

            if ($existing['Subscription'] != $user['Subscription']) {
                $update["Subscription"] = $user['Subscription'];
            }

            if ($existing['PlusExpirationDate'] < $user['PlusExpirationDate']) {
                $update["PlusExpirationDate"] = "'" . addslashes($user['PlusExpirationDate']) . "'";
            }

            if (!empty($update)) {
                $this->logger->info("update Usr set " . ImplodeAssoc(" = ", ", ", $update) . " where UserID = {$existing['UserID']}");
            } else {
                $this->logger->info("no changes for {$user['Login']}");
            }
        }
    }

    private function backupUsers($startDate, $tables)
    {
        $this->logger->info("backing up users");

        if (empty($startDate)) {
            throw new \Exception("startDate required");
        }

        $dumpSettings = $this->getBasicDumpSettings();
        $dumpSettings['extended-insert'] = false;
        $dumpSettings['complete-insert'] = true;
        $dumpSettings['table-settings'] = [
            'Usr' => [
                'where' => "CreationDateTime >= '" . addslashes($startDate) . "'",
                'on-export-row' => function (array $row) {
                    $existing = $this->mainConnection->executeQuery("select UserID from Usr where Login = :login", ["login" => $row['Login']])->fetchColumn();

                    if (!empty($existing)) {
                        $this->logger->info("user {$row['Login']} already exists: $existing");

                        return false;
                    }

                    $row['DefaultTab'] = 'All';

                    return $row;
                },
            ],
            'Account' => [
                'where' => "Account.UserID in (select UserID from Usr where CreationDateTime >= '" . addslashes($startDate) . "')",
                'on-export-row' => function (array $row) {
                    $login = $this->connection->executeQuery("select Login from Usr where UserID = :userId", ["userId" => $row['UserID']])->fetchColumn();
                    $userId = $this->mainConnection->executeQuery("select UserID from Usr where Login = :login", ["login" => $login])->fetchColumn();
                    $existing = $this->mainConnection->executeQuery("select AccountID from Account where UserID = :userId and ProviderID = :providerId and Login = :login", ["userId" => $userId, "providerId" => $row['ProviderID'], "login" => $row['Login']])->fetchColumn();

                    if (!empty($existing)) {
                        $this->logger->info("Account {$row['Login']} already exists: $existing");

                        return false;
                    }
                    $row['UserID'] = $userId;

                    return $row;
                },
            ],
            'Cart' => [
                'where' => "PayDate >= '" . addslashes($startDate) . "'",
                'on-export-row' => function (array $row) {
                    $login = $this->connection->executeQuery("select Login from Usr where UserID = :userId", ["userId" => $row['UserID']])->fetchColumn();
                    $userId = $this->mainConnection->executeQuery("select UserID from Usr where Login = :login", ["login" => $login])->fetchColumn();
                    $existing = $this->mainConnection->executeQuery("select CartID from Cart where UserID = :userId and PayDate = :payDate", ["userId" => $userId, "payDate" => $row['PayDate']])->fetchColumn();

                    if (!empty($existing)) {
                        $this->logger->info("Cart {$row['CartID']} already exists: $existing");

                        return false;
                    }
                    $row['UserID'] = $userId;

                    return $row;
                },
            ],
            'CartItem' => [
                'where' => "CartItem.CartID in (select CartID from Cart where PayDate >= '" . addslashes($startDate) . "')",
                'on-export-row' => function (array $row) {
                    $userId = $this->connection->executeQuery("select UserID from Cart where CartID = :cartId", ["cartId" => $row['CartID']])->fetchColumn();
                    $payDate = $this->connection->executeQuery("select PayDate from Cart where CartID = :cartId", ["cartId" => $row['CartID']])->fetchColumn();
                    $login = $this->connection->executeQuery("select Login from Usr where UserID = :userId", ["userId" => $userId])->fetchColumn();
                    $userId = $this->mainConnection->executeQuery("select UserID from Usr where Login = :login", ["login" => $login])->fetchColumn();
                    $cartId = $this->mainConnection->executeQuery("select CartID from Cart where UserID = :userId and PayDate = :payDate", ["userId" => $userId, "payDate" => $payDate])->fetchColumn();
                    $existing = $this->mainConnection->executeQuery("select CartItemID from CartItem where CartID = :cartId and Name = :name", ["cartId" => $cartId, "name" => $row['Name']])->fetchColumn();

                    if (!empty($existing)) {
                        $this->logger->info("CartItem {$row['CartItemID']} already exists: $existing");

                        return false;
                    }
                    $row['CartID'] = $cartId;

                    return $row;
                },
            ],
            'BusinessTransaction' => [
                'where' => "CreateDate >= '" . addslashes($startDate) . "'",
                'on-export-row' => function (array $row) {
                    $existing = $this->mainConnection->executeQuery("select BusinessTransactionID from BusinessTransaction where UserID = :userId and CreateDate = :createDate", ["userId" => $row['UserID'], "createDate" => $row['CreateDate']])->fetchColumn();

                    if (!empty($existing)) {
                        $this->logger->info("transaction {$row['BusinessTransactionID']} already exists: $existing");

                        return false;
                    }

                    return $row;
                },
            ],
        ];
        $dumpSettings = $this->addLiteDumpConditions($dumpSettings, $tables);
        $this->dump($dumpSettings);
    }

    private function getBasicDumpSettings()
    {
        $result = [
            'no-data' => [
                'SentEmail',
                'AirHelpCompensation',
            ],
            'init_commands' => [
                'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ',
                'START TRANSACTION',
                'SET wait_timeout=28800',
                'SET interactive_timeout=28800',
            ],
            'lock-tables' => false,
            'single-transaction' => false,
            'add-locks' => false,
            'exclude-tables' => [
                'DELETE$',
                'ExtProperty',
                'MerchantTransactionsStatsTemp',
                'MerchantReportTransactionsStatsTemp',
                'MerchantReportExpectedTransactionsStatsTemp',
                'MerchantTransactionsLast3MonthsStatsTemp',
                'MerchantPopularShoppingCategoryStatsTemp',
                'MerchantCacheByCardStatsTemp',
                'MerchantCacheByMultiplierStatsTemp',
                'MerchantPeriodlyStatTemp',
                'MerchantPatternPeriodlyStatTemp',
                '/^LastTransactionsExamples.+$/',
                '/^MerchantRematchTransactionsExamples\d+$/',
            ],
            'add-drop-table' => true,
            'on-progress' => [$this, "onProgress"],
            'table-settings' => [],
        ];

        if (!empty($this->input->getOption('table'))) {
            $result['include-tables'] = [$this->input->getOption('table')];
        }

        return $result;
    }

    private function getCleanDumpSettings()
    {
        $settings = $this->getBasicDumpSettings();

        foreach ($this->backupProcessors as $processor) {
            $processor->register($this->processorInterest);
        }

        foreach ($this->processorInterest->getOnExportRow() as $table => $callbacks) {
            if (isset($settings['table-settings'][$table]['on-export-row'])) {
                throw new \Exception("overwriting on-export-row");
            }

            $settings['table-settings'][$table]['on-export-row'] = function (array $row) use ($callbacks) {
                foreach ($callbacks as $callback) {
                    $row = $callback($row);
                }

                return $row;
            };
        }

        foreach ($this->processorInterest->getJoins() as $table => $joins) {
            if (isset($settings['table-settings'][$table]['joins'])) {
                throw new \Exception("overwriting joins");
            }

            $settings['table-settings'][$table]['joins'] = implode("\n", $joins);
        }

        foreach ($this->processorInterest->getExtraColumns() as $table => $columns) {
            if (isset($settings['table-settings'][$table]['extra-columns'])) {
                throw new \Exception("overwriting extra-columns");
            }

            $settings['table-settings'][$table]['extra-columns'] = implode(", ", $columns);
        }

        if ($this->input->getOption('infile')) {
            $settings = $this->addLoadDataSettings($settings);
        }

        $this->appendQueries[] = "insert into Param(Name, Val) values ('" . SiegeModeDetector::SIEGE_MODE_PARAM_NAME . "', '0') on duplicate key update `Val` = '0'";

        return $settings;
    }

    private function addLoadDataSettings(array $settings): array
    {
        $this->logger->info("using LOAD DATA INFILE");

        foreach (array_keys($this->tables) as $table) {
            $file = dirname($this->file) . "/$table.csv";
            $this->csvFiles[$table] = fopen($file, "wb");

            if ($this->csvFiles[$table] === false) {
                throw new \Exception("failed to open $file for writing");
            }
            $hook = null;

            if (isset($settings['table-settings'][$table]['on-export-row'])) {
                $hook = $settings['table-settings'][$table]['on-export-row'];
            }
            $settings['table-settings'][$table]['on-export-row'] = function (array $row) use ($hook, $table) {
                if ($hook !== null) {
                    $row = call_user_func($hook, $row);
                }

                if ($row !== false) {
                    if (Csv::fputcsv($this->csvFiles[$table], $row, "\t") === false) {
                        throw new \Exception("failed to write csv row for $table");
                    }
                }

                return false;
            };
        }

        return $settings;
    }

    private function dump(array $dumpSettings)
    {
        if ($this->input->getOption('threads')) {
            $this->multiThreadedDump($this->input->getOption('threads'), $dumpSettings);

            return;
        }

        $this->startDump($dumpSettings);

        foreach ($this->csvFiles as $tableName => $handle) {
            if (!file_put_contents($this->file, "
ALTER TABLE $tableName DISABLE KEYS;
SET autocommit=0;
SET foreign_key_checks = 0;
LOAD DATA 
    INFILE '/var/lib/mysql-files/infile/{$tableName}.csv' 
    REPLACE
    INTO TABLE $tableName
    FIELDS TERMINATED BY '\\t' ENCLOSED BY '' ESCAPED BY '\\\\'
    LINES TERMINATED BY '\\n' STARTING BY '';
ALTER TABLE $tableName ENABLE KEYS;
SET foreign_key_checks = 1;
COMMIT;
            
", FILE_APPEND)) {
                throw new \Exception("Failed to write LOAD DATA into $this->file");
            }

            if (!fclose($handle)) {
                throw new \Exception("Failed to close $tableName csv file");
            }
        }

        foreach ($this->appendQueries as $query) {
            if (!file_put_contents($this->file, "\n" . $query . "\n", FILE_APPEND)) {
                throw new \Exception("Failed to append query to {$this->file}");
            }
        }
    }

    private function startDump(array $dumpSettings)
    {
        $params = $this->replicaConnection->getParams();
        $dump = new Mysqldump(
            'mysql:host=' . $params['host'] . ';port=' . $params['port'] . ';dbname=' . $this->databaseNameParameter,
            $this->databaseUserParameter,
            $params['password'],
            $dumpSettings,
            [
                \PDO::ATTR_PERSISTENT => false,
            ]
        );
        $dump->start($this->file);
    }

    private function multiThreadedDump(int $threads, array $dumpSettings)
    {
        if (empty($this->input->getOption('targetHost'))) {
            throw new \Exception("targetHost required for threads mode");
        }

        if (!empty($this->file)) {
            throw new \Exception("Could not dump to file when targetHost/threads are specified");
        }

        $this->logger->info("creating structure");
        $this->dumpToTarget(array_merge($dumpSettings, ['no-data' => true]));
        $this->tuneTargetDatabase();

        $this->logger->info("dumping data in $threads threads to {$this->input->getOption('targetHost')}:{$this->input->getOption('targetPort')}");
        $pool = new ProcessPool($threads);
        $buffer = [];
        $bufferLength = 0;
        $sent = 0;
        $acked = 0;
        $poolUpdateDate = microtime(true);
        $sendToTarget = function () use ($pool, &$bufferLength, &$buffer, $threads, &$sent, &$acked) {
            $acked += $this->waitPendingThreads($pool, $threads);
            $table = $this->currentTable; // copy table name to closure, $this->currentTable could be changed
            $pool->apply(function () use ($buffer, $bufferLength, $table) {
                $this->appProcessor->setMyPid(getmypid());
                $this->logger->info("sending " . \count($buffer) . " queries (" . round($bufferLength / 1024 / 1024) . "M) to {$table}", ["mem" => round(memory_get_usage() / 1024)]);
                // avoid the closing of resources in child process
                register_shutdown_function(function () {
                    posix_kill(getmypid(), SIGKILL);
                });
                $connection = $this->getTargetConnection();
                $connection->exec("
                    SET UNIQUE_CHECKS=0;
                    SET FOREIGN_KEY_CHECKS=0;
                    SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
                    SET SQL_NOTES=0;
                    SET autocommit=0;
                ");

                foreach ($buffer as $sql) {
                    $connection->exec($sql);
                }
                $connection->exec("COMMIT");
                $connection = null;
                $this->logger->info("sent " . \count($buffer) . " queries to {$table}", ["mem" => round(memory_get_usage() / 1024)]);

                return true;
            });
            $sent++;
            $buffer = [];
            $bufferLength = 0;
        };

        $connection = $this->getTargetConnection();
        $tables = $connection->query("show tables")->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $connection->exec("ALTER TABLE `$table` DISABLE KEYS");
        }

        $threadBuffer = $this->input->getOption('thread-buffer');

        if ($threadBuffer === 'auto') {
            $this->logger->info("calculating thread buffer size");
            // we would like to consume max 512mb for buffers
            $threadBuffer = (512 * 1024 * 1024) / $threads;
            // no benefit from larger than 50mb buffer
            $threadBuffer = min($threadBuffer, 50 * 1024 * 1024);
        } else {
            $threadBuffer = $threadBuffer * 1024 * 1024;
        }
        $this->logger->info("thread buffer size: " . (int) round($threadBuffer / 1024 / 1024));
        $startTime = microtime(true);
        $writeTime = 0;
        $reportTime = 0;

        $this->startDump(array_merge(
            $dumpSettings,
            [
                'no-create-info' => true,
                'disable-keys' => false,
            ],
            $this->addCallbackCompressor(
                function ($sql) use ($sendToTarget, &$buffer, &$bufferLength, &$poolUpdateDate, $pool, $threadBuffer, &$startTime, &$writeTime, &$reportTime) {
                    $writeStart = microtime(true);

                    if ((microtime(true) - $poolUpdateDate) > 3) {
                        $poolUpdateDate = microtime(true);
                        $pool->apply();
                    }
                    $buffer[] = $sql;
                    $bufferLength += \strlen($sql);

                    if ($bufferLength > $threadBuffer) {
                        $sendToTarget();
                    }

                    $time = microtime(true);
                    $writeTime += $time - $writeStart;
                    $totalTime = $time - $startTime;
                    $readTime = $totalTime - $writeTime;

                    if ($writeTime > 0 && ($time - $reportTime) > 60) {
                        $rwRatio = $readTime / $writeTime;
                        $this->logger->info("read/write ratio: " . number_format($rwRatio, 3));
                        $reportTime = $time;
                    }
                },
                function () {}
            )
        ));

        if (!empty($buffer)) {
            $sendToTarget();
        }

        $acked += $this->waitPendingThreads($pool, 0);

        // reconnect to prevent gone away
        $connection = $this->getTargetConnection();

        foreach ($tables as $table) {
            $connection->exec("ALTER TABLE `$table` ENABLE KEYS");
        }

        foreach ($this->appendQueries as $query) {
            $this->output->writeln("append query: $query");
            $connection->exec($query);
        }

        //        $acked += $this->checkThreadResults($pool);
        //        if ($acked != $sent) {
        //            throw new \Exception("some threads failed, sent: $sent, acked: $acked");
        //        }
    }

    private function waitPendingThreads(ProcessPool $pool, int $count)
    {
        $acked = 0;

        while (($pending = $pool->getPending()) > $count) {
            $acked += $this->checkThreadResults($pool);
        }

        return $acked;
    }

    private function checkThreadResults(ProcessPool $pool)
    {
        $acked = 0;

        while ($pool->getPending() && $pool->get(1, true)) {
            $acked++;
        }

        if ($acked > 0) {
            $this->logger->debug("$acked threads completed");
        }

        return $acked;
    }

    private function dumpToTarget(array $dumpSettings)
    {
        $connection = $this->getTargetConnection();

        $dumpSettings = array_merge($dumpSettings, $this->addCallbackCompressor(
            function ($sql) use ($connection) {
                if (!empty($sql)) {
                    $connection->exec($sql);
                }
            },
            function () {}
        ));

        $this->startDump($dumpSettings);
    }

    private function getTargetConnection()
    {
        $connection = new \PDO(
            "mysql:host=" . $this->input->getOption("targetHost") . ';port=' . $this->input->getOption("targetPort") . ';dbname=awardwallet',
            "awardwallet",
            "awardwallet",
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                \PDO::ATTR_PERSISTENT => false,
            ]
        );
        $connection->exec("
            SET wait_timeout=86400;
            SET interactive_timeout=86400;
        ");

        return $connection;
    }

    private function addCallbackCompressor(callable $onFlush, callable $onTableEnd): array
    {
        $compressor = new class($onFlush) implements CompressorInterface {
            private $buffer = '';
            /**
             * @var callable
             */
            private $onFlush;

            public function __construct(callable $onFlush)
            {
                $this->onFlush = $onFlush;
            }

            public function open($filename)
            {
            }

            public function write($str)
            {
                $this->buffer .= $str;

                return strlen($str);
            }

            public function close()
            {
                $this->flush();
            }

            public function flush()
            {
                if ($this->buffer !== '') {
                    call_user_func($this->onFlush, $this->buffer);
                    $this->buffer = '';
                }
            }
        };

        return [
            'compress' => $compressor,
            'on-progress' => function ($event, $extra) use ($compressor, $onTableEnd) {
                switch ($event) {
                    case 'flush':
                        $compressor->flush();

                        break;

                    case 'table-end':
                        call_user_func($onTableEnd);

                        break;
                }
                $this->onProgress($event, $extra);
            },
        ];
    }

    private function scanUsers()
    {
        $users = array_merge($this->getStaff(), $this->getBookers(), $this->users->getExcludeUsers());

        if (!empty($this->input->getOption('userId'))) {
            $users[] = $this->input->getOption('userId');
        }
        $this->keepUsers = implode(", ", $this->getDependentUsers($users));

        if (empty($this->input->getOption('userId'))) {
            $maxUserId = $this->connection->executeQuery("select max(UserID) as MaxUserID from Usr")->fetchColumn();
            $this->logger->info("max user id: {$maxUserId}");
            $this->maxUserId = $maxUserId - 1000;
        }
        $this->backupFilters["Usr"] = $this->getUserFilter("Usr.UserID");
    }

    private function getDependentUsers(array $users): array
    {
        $refColumns = $this->connection->executeQuery('SELECT `COLUMN_NAME`  
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA=\'' . $this->databaseNameParameter . '\' 
        AND REFERENCED_TABLE_SCHEMA IS NOT NULL
        AND REFERENCED_TABLE_NAME = \'Usr\'
        AND TABLE_NAME = \'Usr\'')->fetchAll(\PDO::FETCH_COLUMN);

        do {
            $added = false;

            foreach ($this->connection->executeQuery("select UserID, " . implode(
                ", ",
                $refColumns
            ) . " from Usr where UserID in (" . implode(
                ", ",
                $users
            ) . ")" . (isset($this->maxUserId) ? " or UserID >= {$this->maxUserId}" : ""))->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                foreach ($row as $column => $userId) {
                    if (!empty($userId) && !in_array($userId, $users) && $userId < $this->maxUserId) {
                        $this->logger->info("adding user {$userId} by reference in {$column} of {$row['UserID']}");
                        $added = true;
                        $users[] = $userId;
                    }
                }
            }
        } while ($added);

        return $users;
    }

    private function getDumpConditions($table)
    {
        global $arDetailTable;

        if ($table == "GeoTag" && $this->useJoins) {
            return ["where" => implode(
                " OR ",
                [
                    "GeoTagID in (select ts.DepGeoTagID from TripSegment ts join Trip t on ts.TripID = t.TripID where " . $this->getUserFilter("t.UserID") . ")",
                    "GeoTagID in (select ts.ArrGeoTagID from TripSegment ts join Trip t on ts.TripID = t.TripID where " . $this->getUserFilter("t.UserID") . ")",
                    "GeoTagID in (select r.GeoTagID from Reservation r where " . $this->getUserFilter("r.UserID") . ")",
                    "GeoTagID in (select r.GeoTagID from Restaurant r where " . $this->getUserFilter("r.UserID") . ")",
                    "GeoTagID in (select l.PickupGeoTagID from Rental l where " . $this->getUserFilter("l.UserID") . ")",
                    "GeoTagID in (select l.DropoffGeoTagID from Rental l where " . $this->getUserFilter("l.UserID") . ")",
                    "GeoTagID in (select d.StartGeoTagID from Direction d where " . $this->getUserFilter("d.UserID") . ")",
                    "GeoTagID in (select d.EndGeoTagID from Direction d where " . $this->getUserFilter("d.UserID") . ")",
                ]
            )];
        }

        if ($table == "PageVisit") {
            return ["where" => $this->getUserFilter("UserID")];
        }

        if ($table == "DiffChange") {
            return ["where" => implode(
                " OR ",
                array_map(function ($kind) {
                    global $arDetailTable;
                    $table = $arDetailTable[$kind];
                    $conditions = $this->getDumpConditions($table);

                    if (!isset($conditions['joins'])) {
                        $conditions['joins'] = '';
                    }

                    return "(DiffChange.SourceID like '{$kind}.%' and substr(DiffChange.SourceID, 3) in (select {$table}ID from {$table} {$conditions['joins']}" . (isset($conditions['where']) ? ' where ' . $conditions['where'] : '') . "))";
                }, array_keys($arDetailTable))
            )];
        }

        if ($table == "FlightInfo" && $this->useJoins) {
            $conditions = $this->getDumpConditions("TripSegment");

            if (!isset($conditions['joins'])) {
                $conditions['joins'] = '';
            }

            return ["where" => "FlightInfo.FlightInfoID in (select FlightInfoID from TripSegment {$conditions['joins']}" . (isset($conditions['where']) ? ' where ' . $conditions['where'] : '') . ")"];
        }

        if (in_array($table, ["Merchant", "EarningPotential", "MerchantReport", "MerchantGroupMerchant"]) && $this->useJoins) {
            $conditions = $this->getDumpConditions("AccountHistory");

            if (!isset($conditions['joins'])) {
                $conditions['joins'] = '';
            }

            return ["where" => "{$table}.MerchantID in (select AccountHistory.MerchantID from AccountHistory {$conditions['joins']}" . (isset($conditions['where']) ? ' where ' . $conditions['where'] : '') . " and AccountHistory.MerchantID is not null)"];
        }

        $links = [];

        foreach ($this->backupFilters as $targetTable => $filter) {
            $links = array_merge($links, $this->getLinksTo($table, $targetTable, [], [$table]));
        }

        $aliases = ["" => $table];
        $keyColumns = [];
        $linkedTables = [];
        $joins = [];
        $joinRequired = ["" => true];

        if ($this->useJoins) {
            foreach ($links as $linkIndex => $link) {
                $path = "";

                foreach ($link as $refIndex => $ref) {
                    $lastPath = $path;
                    $alias = "l{$linkIndex}r{$refIndex}";
                    $path .= " join {$ref['REFERENCED_TABLE_NAME']} on ";

                    if (isset($ref['JOIN_CONDITION'])) {
                        $path .= $ref['JOIN_CONDITION'];
                    } else {
                        $path .= "{$ref['TABLE_NAME']}.{$ref['COLUMN_NAME']} = {$ref['REFERENCED_TABLE_NAME']}.{$ref['REFERENCED_COLUMN_NAME']}";
                    }

                    if (isset($joins[$path])) {
                        continue;
                    }
                    $joins[$path] = "join {$ref['REFERENCED_TABLE_NAME']} as {$alias} on ";

                    if (isset($ref['JOIN_CONDITION'])) {
                        $joins[$path] .= str_replace(
                            $ref['TABLE_NAME'] . ".",
                            "{$aliases[$lastPath]}.",
                            str_replace($ref['REFERENCED_TABLE_NAME'] . ".", "{$alias}.", $ref['JOIN_CONDITION'])
                        );
                        $required = !empty($ref['REQUIRED']);
                    } else {
                        $joins[$path] .= "{$aliases[$lastPath]}.{$ref['COLUMN_NAME']} = {$alias}.{$ref['REFERENCED_COLUMN_NAME']}";
                        $required = !$this->tables[$ref['TABLE_NAME']]['fields'][$ref['COLUMN_NAME']]['null'];
                    }
                    $joinRequired[$path] = $joinRequired[$lastPath] && $required;

                    if (!$joinRequired[$path]) {
                        $joins[$path] = "left " . $joins[$path];
                    }
                    $aliases[$path] = $alias;
                    $linkedTables[$alias] = $ref['REFERENCED_TABLE_NAME'];
                    $keyColumns[$alias] = $ref['REFERENCED_COLUMN_NAME'];
                }
            }
        }
        $filters = [];

        foreach ($linkedTables as $alias => $linkedTable) {
            if (isset($this->backupFilters[$linkedTable])) {
                $filters[] = str_replace("{$linkedTable}.", "{$alias}.", $this->backupFilters[$linkedTable]) . (isset($this->refGroups[$table]) || $joinRequired[array_search($alias, $aliases)] || $this->strictJoin ? "" : " OR {$alias}.{$keyColumns[$alias]} is null");
            }
        }

        if (isset($this->backupFilters[$table])) {
            $filters[] = $this->backupFilters[$table];
        }

        $result = [];

        if (!empty($filters)) {
            if (isset($this->refGroups[$table])) {
                $operator = 'OR';
            } else {
                $operator = 'AND';
            }
            $result['where'] = "\n\n(" . implode("\n\n) {$operator} (\n\n", $filters) . ")";
        }

        if (!empty($joins)) {
            $result['joins'] = "\n" . implode("\n", $joins);
        }

        return $result;
    }

    private function getUserFilter($colName)
    {
        $result = "{$colName} in ({$this->keepUsers})";

        if (isset($this->maxUserId)) {
            $result .= " OR {$colName} >= {$this->maxUserId}";
        }

        return "($result)";
    }

    private function getLinksTo($sourceTable, $targetTable, array $path, array $visited)
    {
        $result = [];

        foreach ($this->tables[$sourceTable]['references'] as $ref) {
            foreach (self::NULLABLE_FIELDS as $field) {
                if ($field[0] == $sourceTable && $field[1] == $ref['COLUMN_NAME']) {
                    continue 2;
                }
            }

            if (in_array($ref['REFERENCED_TABLE_NAME'], $visited)) {
                continue;
            }

            if (isset($this->refIgnores[$sourceTable]) && in_array($ref['REFERENCED_COLUMN_NAME'], $this->refIgnores[$sourceTable])) {
                continue;
            }

            if ($ref['REFERENCED_TABLE_NAME'] == $targetTable) {
                $result[] = array_merge($path, [$ref]);
            } else {
                $links = $this->getLinksTo($ref['REFERENCED_TABLE_NAME'], $targetTable, array_merge($path, [$ref]), array_merge($visited, [$sourceTable]));

                if (!empty($links)) {
                    $result = array_merge($result, $links);
                }
            }
        }

        return $result;
    }

    private function exploreTables()
    {
        $tables = [];

        foreach ($this->connection->executeQuery("show tables")->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $tables[$table] = [
                'name' => $table,
                'fields' => $this->exploreTableFields($table),
                'references' => $this->exploreTableReferences($table),
            ];
        }
        $this->tables = $tables;
    }

    private function exploreTableFields($table)
    {
        $result = [];
        $fields = $this->connection->executeQuery("describe $table")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($fields as $field) {
            $result[$field["Field"]] = [
                "name" => $field["Field"],
                "type" => $field["Type"],
                "null" => $field["Null"] == "YES",
            ];
        }

        return $result;
    }

    private function exploreTableReferences($table)
    {
        $result = $this->connection->executeQuery("SELECT
            `TABLE_SCHEMA`,
            `TABLE_NAME`,
            `COLUMN_NAME`,
            `CONSTRAINT_NAME`,
            `REFERENCED_TABLE_SCHEMA`,
            `REFERENCED_TABLE_NAME`,
            `REFERENCED_COLUMN_NAME`
        FROM 
            information_schema.KEY_COLUMN_USAGE 
        WHERE 
            `CONSTRAINT_SCHEMA` = 'awardwallet' AND
            `TABLE_NAME` = :table AND
            `REFERENCED_TABLE_SCHEMA` IS NOT NULL", ['table' => $table])->fetchAll(\PDO::FETCH_ASSOC);

        switch ($table) {
            case 'AAMembership':
                $result[] = [
                    'TABLE_NAME' => 'AAMembership',
                    'COLUMN_NAME' => 'UserID',
                    'REFERENCED_TABLE_NAME' => 'Usr',
                    'REFERENCED_COLUMN_NAME' => 'UserID',
                ];

                break;
        }

        return $result;
    }

    private function getStaff()
    {
        $result = $this->connection->executeQuery("select u.UserID
        from Usr u
        join GroupUserLink gl on u.UserID = gl.UserID
        join SiteGroup g on gl.SiteGroupID = g.SiteGroupID
        where g.GroupName = 'staff'")->fetchAll(\PDO::FETCH_COLUMN);

        // add businesses
        $result = array_merge($result, $this->connection->executeQuery("select ua.ClientID
        from UserAgent ua where ua.AgentID in (" . implode(", ", $result) . ") and ua.AccessLevel = " . ACCESS_ADMIN . " and ua.IsApproved = 1")->fetchAll(\PDO::FETCH_COLUMN));

        $this->logger->info("staff", ["users" => $result]);

        return $result;
    }

    private function getBookers()
    {
        $result = $this->connection->executeQuery("select UserID from AbBookerInfo")->fetchAll(\PDO::FETCH_COLUMN);
        // add admins
        $result = array_merge($result, $this->connection->executeQuery("select ua.AgentID
        from UserAgent ua where ua.ClientID in (" . implode(", ", $result) . ") and ua.AccessLevel = " . ACCESS_ADMIN . " and ua.IsApproved = 1")->fetchAll(\PDO::FETCH_COLUMN));

        $this->logger->info("businesses", ["users" => $result]);

        return $result;
    }

    private function tuneTargetDatabase()
    {
        $connection = $this->getTargetConnection();
        $connection->exec("alter table AccountHistory row_format=COMPRESSED");
    }
}
