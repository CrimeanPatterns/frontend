<?php

namespace AwardWallet\MainBundle\Service\Quinstreet;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\QsTransaction;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\Charts\QsTransactionChart;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use AwardWallet\MainBundle\Service\Statistics;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateQsTransactionQmpCommand extends Command
{
    public const PROVIDER_FICO_CODES = [
        ['providerId' => Provider::AMEX_ID, 'code' => 'amexFICO'],
        ['providerId' => Provider::CHASE_ID, 'code' => 'chaseFICO'],
        ['providerId' => Provider::CITI_ID, 'code' => 'citybankFICO'],
        ['providerId' => Provider::CITI_ID, 'code' => 'thankyouCitybankFICO'],
        ['providerId' => Provider::DISCOVER_ID, 'code' => 'discoverFICO'],
        ['providerId' => Provider::BARCLAYCARD_ID, 'code' => 'barclaycardFICO'],
        ['providerId' => Provider::BANKOFAMERICA_ID, 'code' => 'bankofamericaBankamericardFICO'],
        ['providerId' => Provider::WELLSFARGO_ID, 'code' => 'wellsfargoFICO'],
        ['providerId' => Provider::NAVY_ID, 'code' => 'navyFICO'],
        ['providerId' => Provider::USBANK_ID, 'code' => 'usbankFICO'],
        ['providerId' => Provider::RBCBANK_ID, 'code' => 'rbcbankFICO'],
    ];

    private const REPORT_VERSION_MASTER = 30650; // master report
    private const REPORT_VERSION_DETAILED = 31230; // details

    private const REPORT_ACTUAL = self::REPORT_VERSION_DETAILED;

    private const API_URL_GET_TOKEN = 'https://reporting.qmp.ai/oauth/generatetoken?grant_type=client_credentials';
    private const API_URL_GET_REPORT = 'https://reporting.qmp.ai/api/pub/download/%d?startDate=%s&endDate=%s'; // Master Report

    private const NUMBER_WEEK_IN_PAST = 25;
    private const NUMBER_DAYS_INTERVAL = 3;

    private const VALIDATE_COLUMNS = [
        'click_date',
        'process_date',
        'source_name',
        'advertiser',
        'card_name',
        'var2',
        'impressions',
        'clicks',
        'applications',
        'approvals',
        'total_earnings',
        'click_id',
        'click_key',
        'marketplace_conversion_id',
        'device_type',
        'credit_card_type_name',
    ];
    private const VALIDATE_DETAILED_COLUMNS = [
        'advertiser',
        'applications',
        'approvals',
        'card_name',
        'category',
        'click_date',
        'click_hour',
        'click_id',
        'click_key',
        'click_time',
        'clicks',
        'marketplace_conversion_id',
        'country_code',
        'device_type',
        'total_earnings',
        'exchange_name',
        'impressions',
        'source_id',
        'source_name',
        'credit_card_type_name',
        'process_date',
        'session_ref_url',
        'search_id',
        'searches',
        'account',
        'state',
        'state_code',
        'sub_id',
        'transaction_id',
        'var2',
        'var3',
    ];
    private const CURRENCY_FIELDS = ['total_earnings'];

    private const EXTEND_FIELDS = [
        'MarketplaceConversionId' => 'marketplace_conversion_id',
        'Category' => 'category',
        'CountryCode' => 'country_code',
        'State' => 'state',
        'StateCode' => 'state_code',
        'Exchange' => 'exchange_name',
        'ClickTime' => 'click_time',
        'DeviceType' => 'device_type',
        'CreditCardTypeName' => 'credit_card_type_name',
    ];
    protected static $defaultName = 'aw:update-qs-transaction-qmp';

    private LoggerInterface $logger;
    private Connection $connection;
    private AppBot $appBot;
    private Statistics $statistics;
    private QsTransactionChart $qsTransactionChart;
    private \Memcached $memcached;

    private string $login;
    private string $password;
    private string $token;

    private bool $isDebug = false;
    private bool $isNotify = false;
    private ?\DateTime $setDate = null;
    private array $duplicates = [];

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        AppBot $appBot,
        Statistics $statistics,
        QsTransactionChart $qsTransactionChart,
        string $quinstreetLogin,
        string $quinstreetPassword,
        \Memcached $memcached
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
        $this->appBot = $appBot;
        $this->statistics = $statistics;
        $this->qsTransactionChart = $qsTransactionChart;
        $this->memcached = $memcached;

        $this->login = $quinstreetLogin;
        $this->password = $quinstreetPassword;
    }

    public function configure()
    {
        $this->setDescription('Export Quinstreet Applications Data from QMP AI');

        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Debug');
        $this->addOption('notify', null, InputOption::VALUE_NONE, 'Sending notifications after completion');
        $this->addOption('ficodate',
            null,
            InputOption::VALUE_REQUIRED,
            'Date of receipt of information in QsTransaction'
        );

        $this->addOption('date', null, InputOption::VALUE_OPTIONAL, '');
        $this->addOption('summary', null, InputOption::VALUE_NONE);
        $this->addOption('period', null, InputOption::VALUE_OPTIONAL, 'Format startDate_endDate: Y-m-d_Y-m-d');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('summary')) {
            return $this->summaryMonthlyReport();
        }

        if ($input->getOption('date')) {
            $this->setDate = new \DateTime($input->getOption('date'));
        }

        $this->isDebug = (bool) $input->getOption('debug');
        $this->isNotify = (bool) $input->getOption('notify');
        $datePeriod = $input->getOption('period');

        if (!$this->isNotify) {
            $datePeriod = '2024-03-20_2024-06-10'; // fetch old data
        }

        if (!$this->isDebug) {
            $this->token = $this->getToken();

            if (null === $this->token) {
                throw new \RuntimeException('Access token not defiend');
            }
        }

        $periods = [];
        $multiRecords = [];

        if (!empty($datePeriod)) {
            $datePeriod = explode('_', $datePeriod);
            $startDate = (new \DateTime($datePeriod[0]))->setTime(0, 0, 0);
            $endDate = (new \DateTime($datePeriod[1]))->setTime(23, 59, 59);

            $interval = $startDate->diff($endDate);

            if ($interval->days > 150) {
                // throw new \Exception('Limit day periods 100');
            }

            $start = clone $startDate;
            $iCount = self::NUMBER_DAYS_INTERVAL > 0 ? round($interval->days / self::NUMBER_DAYS_INTERVAL) : $interval->days;

            for ($i = -1; ++$i < $iCount;) {
                $end = clone $start;
                $end->add(new \DateInterval('P' . self::NUMBER_DAYS_INTERVAL . 'D'));

                if ((int) $end->format('Ymd') >= (int) $endDate->format('Ymd')) {
                    $end = $endDate;
                }

                $from = $start->format('Y-m-d');
                $to = $end->format('Y-m-d');

                if (!$this->isDebug) {
                    $records = $this->fetchData($from, $to);
                }

                $periods[] = $from . '__' . $to;
                $multiRecords[] = $records ?? [];

                if (0 === self::NUMBER_DAYS_INTERVAL) {
                    $end->add(new \DateInterval('P1D'));
                }
                $start = $end;

                $data = $this->prepareDate(array_merge(...array_values($multiRecords)));
                $this->dataProcess($data, $periods);
            }

            $this->resultProcess();

            return;
        }

        for ($dayInterval = self::NUMBER_WEEK_IN_PAST; --$dayInterval > 0;) {
            $daysAgo = $dayInterval * self::NUMBER_DAYS_INTERVAL;
            $startDate = new \DateTime();
            $startDate->sub(new \DateInterval('P' . $daysAgo . 'D'));

            $endDate = clone $startDate;
            $endDate->add(new \DateInterval('P' . self::NUMBER_DAYS_INTERVAL . 'D'));

            $from = $startDate->format('Y-m-d');
            $to = $endDate->format('Y-m-d');

            if (!$this->isDebug) {
                $records = $this->fetchData($from, $to);
            }

            $periods[] = $from . '__' . $to;
            $multiRecords[] = $records;

            $data = $this->prepareDate(array_merge(...array_values($multiRecords)));
            $this->dataProcess($data, $periods);
        }

        $this->resultProcess();
    }

    public static function parseVar2(string $var2): array
    {
        if (empty($var2)) {
            return [];
        }

        $rec = [];
        $vars = array_filter(explode('.', $var2));

        if (false === strpos($var2, '~') && 1 === count($vars)) {
            if (false !== strpos($vars[0], 'accountlist')) {
                $rec['Source'] = 'accountlist';
            } elseif (false !== strpos($vars[0], '101')) {
                $rec['Source'] = '101';
            } elseif (false !== strpos($vars[0], 'bya')) {
                $rec['Source'] = 'bya';
            } elseif (false !== strpos($vars[0], 'marketplace')) {
                $rec['Exit'] = 'marketplace';
            }
        } else {
            foreach ($vars as $key => $value) {
                $values = explode('~', $value);

                if (2 === count($values)) {
                    [$k, $v] = $values;
                    $vars[$k] = $v;
                }
            }

            foreach (
                [
                    'source' => 'Source',
                    'pid' => 'BlogPostID',
                    'mid' => 'MID',
                    'cid' => 'CID',
                    'rkbtyn' => 'RefCode',
                    'exit' => 'Exit',
                ] as $var => $column
            ) {
                array_key_exists($var, $vars) ? $rec[$column] = $vars[$var] : null;
            }
        }

        return $rec;
    }

    private function dataProcess(array $data, &$periods)
    {
        $users = $this->getUserIdByRefcodes($data);
        [$foundCards, $notFound] = $this->getAssignCards($data);

        if (!empty($notFound)) {
            echo '<pre>Not Found CARDS:';
            print_r($notFound);
            echo '</pre>';
        }

        $index = 0;
        $duplicatesCount = 0;
        $insertCount = 0;

        $accountSourcesCounterUpdate =
        $accountSourcesCounterInsert = [
            QsTransaction::ACCOUNT_DIRECT => 0,
            QsTransaction::ACCOUNT_AWARDTRAVEL101 => 0,
            QsTransaction::ACCOUNT_CARDRATINGS => 0,
        ];

        foreach ($data as $row) {
            $upd = [];

            if (array_key_exists($row['Card'], $foundCards)) {
                $upd[] = [
                    'field' => 'QsCreditCardID',
                    'value' => $foundCards[$row['Card']],
                    'type' => \PDO::PARAM_INT,
                ];
            }

            $upd[] = ['field' => 'ClickDate', 'value' => $row['ClickDate'], 'type' => \PDO::PARAM_STR];
            $upd[] = ['field' => 'ProcessDate', 'value' => $row['ProcessDate'], 'type' => \PDO::PARAM_STR];

            if (!empty($row['RefCode'])) {
                if (strlen($row['RefCode']) > 16) {
                    $row['RawVar1'] = str_replace($row['RefCode'], '', $row['RawVar1']);
                    $row['RefCode'] = '';
                }

                if (array_key_exists($row['RefCode'], $users)) {
                    $upd[] = ['field' => 'UserID', 'value' => $users[$row['RefCode']], 'type' => \PDO::PARAM_INT];
                }
            }

            // int fields
            foreach (
                [
                    'Account',
                    'BlogPostID',
                    'Approvals',
                    'PageViews',
                    'Clicks',
                    'Applications',
                    'Version',
                    'Impressions',
                ] as $field
            ) {
                if (array_key_exists($field, $row)) {
                    $upd[] = ['field' => $field, 'value' => (int) $row[$field], 'type' => \PDO::PARAM_INT];
                }
            }

            $upd[] = ['field' => 'Earnings', 'value' => (float) $row['Earnings'], 'type' => \PDO::PARAM_STR];

            // string fields
            foreach (
                [
                    'Card',
                    'Source',
                    'Exit',
                    'MID',
                    'CID',
                    'RefCode',
                    'RawAccount',
                    'RawVar1',
                    'Hash',
                    'Click_ID',
                    'Advertiser',
                    'ClickKey',
                ] as $field
            ) {
                if (array_key_exists($field, $row)) {
                    $upd[] = ['field' => $field, 'value' => $row[$field], 'type' => \PDO::PARAM_STR];
                }
            }

            $upd[] = ['field' => 'CreationDate', 'value' => date('Y-m-d h:i:s'), 'type' => \PDO::PARAM_STR];

            foreach (self::EXTEND_FIELDS as $field => $fieldValueKey) {
                if (!empty($row[$field])) {
                    if ('Unknown' === $row[$field]) {
                        $row[$field] = '';
                    }
                    $upd[] = ['field' => $field, 'value' => $row[$field], 'type' => \PDO::PARAM_STR];
                }
            }

            $sql = '
                INSERT INTO `QsTransaction` (`' . implode('`,`', array_column($upd, 'field')) . '`)
                    VALUES (' . rtrim(str_repeat('?,', count($upd)), ',') . ')
            ';

            try {
                $this->connection->executeQuery($sql, array_column($upd, 'value'), array_column($upd, 'type'));
                ++$insertCount;
                $accountSourcesCounterInsert[$row['Account']]++;
            } catch (UniqueConstraintViolationException $e) {
                $this->duplicates[] = $row;
                ++$duplicatesCount;
                $accountSourcesCounterUpdate[$row['Account']]++;

                $sql = 'UPDATE `QsTransaction` SET ';

                foreach ($upd as $field) {
                    $sql .= $this->connection->quoteIdentifier($field['field'])
                        . ' = '
                        . $this->connection->quote($field['value'], $field['type'])
                        . ',';
                }
                $sql = rtrim($sql, ',');
                $sql .= ' WHERE Hash = ' . $this->connection->quote($row['Hash']) . ' LIMIT 1';
                $affectedUpdate = $this->connection->executeStatement($sql);

                if ($this->isDebug) {
                    // echo ++$index . ') [' . $affectedUpdate . '] duplicate Hash = ' . $row['Hash'] . ' : (' . $row['ProcessDate'] . ' ; ' . $row['RawAccount'] . ' ; ' . $row['RawVar1'] . ' ; ' . $row['Card'] . ')' . "<br>\r\n";
                }
            } catch (\Exception $e) {
                throw new \Exception('UpdateQSTransactionCommand error: ' . $e->getMessage());
            }
        }

        echo "\r\n" . 'PERIODS: ' . PHP_EOL . implode(',' . PHP_EOL, $periods) . "\r\n";
        echo ' - insert rows count: ' . $insertCount . "\r\n";
        echo ' - duplicates hash rows: ' . $duplicatesCount . "\r\n";
        echo "source accounts: \r\n"
            . ' insert: ' . var_export($accountSourcesCounterInsert, true) . "\r\n"
            . ' update: ' . var_export($accountSourcesCounterUpdate, true);
        echo "\r\n";
        $periods = [];
    }

    private function resultProcess(): void
    {
        $this->updateUserIDbyRefCode();
        $this->updateStates();
        $this->updateQsCreditCard();

        if ($this->isNotify) {
            $this->sendStatistics();
            $this->sendQsTransactionCharts();

            $this->summaryMonthlyReport();
        }

        $this->updateUserCreditCardLaseenDate();

        echo PHP_EOL,
        '########', PHP_EOL,
        '# DONE #', PHP_EOL,
        '########', PHP_EOL;
    }

    private function prepareDate(array $records): array
    {
        /*
            Not filled fields for VERSION_QMP_API_AI = 3
                [PageViews, CTR, AvgEpc]
        */

        $filtered = [];

        foreach ($records as $row) {
            if ('Null' === $row['var2']) {
                $row['var2'] = '';
            }

            foreach (self::CURRENCY_FIELDS as $fieldName) {
                $row[$fieldName] = trim(str_replace(['t', '$', ' ', '-'], '', $row[$fieldName]));

                if (false !== strpos($row[$fieldName], '.')) {
                    $row[$fieldName] = str_replace(',', '', $row[$fieldName]);
                }
            }

            $hash = sha1($row['source_name'] . $row['click_id'] . $row['click_key'] . $row['card_name'] . $row['var2'] . ($row['marketplace_conversion_id'] ?? ''));

            $accountSource = $this->fetchAccountSource($row['source_name']);

            if (null === $accountSource) {
                // # TODO:: exception
                continue;
            }

            $rec = [
                'ClickDate' => $row['click_date'],
                'ProcessDate' => $row['process_date'],
                'Account' => $accountSource,
                'Card' => $row['card_name'],
                'Clicks' => str_replace(['-', ' '], '', $row['clicks']),
                'Click_ID' => $row['click_id'],
                'Applications' => $row['applications'],
                'Approvals' => (int) $row['approvals'],
                'Earnings' => (float) $row['total_earnings'],
                'RawAccount' => $row['source_name'],
                'RawVar1' => $row['var2'],
                // 'Hash' => sha1($row['click_date'] . $row['source_name'] . $row['var2'] . $row['card_name']),
                // 'Hash' => sha1($row['source_name'] . $row['click_id'] . $row['click_key'] . $row['card_name']),
                'Hash' => $hash,

                'Version' => QsTransaction::VERSION_QMP_API_AI,
                'Advertiser' => $row['advertiser'],
                'Impressions' => $row['impressions'],
                'ClickKey' => $row['click_key'],
            ];

            foreach (self::EXTEND_FIELDS as $fieldKey => $rowValueKey) {
                if (!empty($row[$rowValueKey])) {
                    $rec[$fieldKey] = $row[$rowValueKey];
                }
            }

            $var2 = self::parseVar2($row['var2']);

            if (!empty($var2)) {
                $rec = array_merge($rec, $var2);
            }

            if (array_key_exists($hash, $filtered)
                && (
                    empty($rec['Earnings'])
                    // $rec['Earnings'] < $filtered[$hash]['Earnings']
                    || 0 === $rec['Approvals']
                )
            ) {
                continue;
            }

            $filtered[$hash] = $rec;
        }

        return array_values($filtered);
    }

    private function getUserIdByRefcodes(array $data): array
    {
        $refCodes = array_unique(array_column($data, 'RefCode'));

        if (!empty($refCodes)) {
            return $this->connection->fetchAllKeyValue(
                'SELECT RefCode, UserID FROM Usr WHERE RefCode IN (?)',
                [$refCodes],
                [\Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
            );
        }

        return [];
    }

    private function getAssignCards(array $data): array
    {
        $qsCards = $this->connection->fetchAllAssociative('SELECT QsCreditCardID, CardName FROM QsCreditCard ORDER BY IsHidden ASC');
        $qsList = [];

        foreach ($qsCards as &$card) {
            $card['CardName'] = CreditCardMatcher::cleanSpecialSymbols($card['CardName']);
            $qsList[] = $card['CardName'];
        }

        $cardsHistory = $this->connection->fetchAllAssociative('SELECT DISTINCT CardName, QsCreditCardID FROM QsCreditCardHistory');

        foreach ($cardsHistory as $key => &$card) {
            $name = CreditCardMatcher::cleanSpecialSymbols($card['CardName']);

            if (in_array($name, $qsList, true)) {
                unset($cardsHistory[$key]);

                continue;
            }

            $card['CardName'] = $name;
        }
        $cardsHistory = array_values($cardsHistory);

        $foundCards = [];
        $notFound = [];
        $dataCards = array_unique(array_column($data, 'CardName'));

        foreach ($dataCards as $card) {
            $stripCardName = CreditCardMatcher::cleanSpecialSymbols($card);
            $qsCardId = $this->foundQsCard($stripCardName, $qsCards, $cardsHistory);

            if (null === $qsCardId) {
                $notFound[] = $card;

                continue;
            }

            $foundCards[$card] = $qsCardId;
        }

        return [$foundCards, $notFound];
    }

    private function updateUserIDbyRefCode()
    {
        return $this->connection->executeStatement('
            UPDATE QsTransaction qt
            JOIN Usr u ON (u.RefCode = qt.RefCode)
            SET qt.UserID = u.UserID
            WHERE
                    qt.RefCode IS NOT NULL
                AND qt.UserID IS NULL
                AND u.UserID IS NOT NULL
        ');
    }

    private function updateUserCreditCardLaseenDate(): void
    {
        $rows = $this->connection->fetchAllAssociative("
            SELECT qt.UserID, cc.CreditCardID, MAX(qt.ProcessDate) AS _date
            FROM QsTransaction qt
            JOIN CreditCard cc ON (qt.QsCreditCardID = cc.QsCreditCardID)
            WHERE
                    qt.UserID IS NOT NULL
                AND qt.ProcessDate IS NOT NULL
                AND qt.ProcessDate > '" . date('Y-m-d', strtotime('5 months ago')) . "' 
            GROUP BY cc.CreditCardID, qt.UserID
        ");

        foreach ($rows as $row) {
            $this->connection->executeQuery('
                UPDATE UserCreditCard
                SET LastSeenOnQsDate = :date
                WHERE UserID = :userId AND CreditCardID = :cardId AND (LastSeenOnQsDate IS NULL OR LastSeenOnQsDate < :date)',
                ['date' => $row['_date'], 'userId' => $row['UserID'], 'cardId' => $row['CreditCardID']],
                ['date' => \PDO::PARAM_STR, 'userId' => \PDO::PARAM_INT, 'cardId' => \PDO::PARAM_INT]
            );
        }
    }

    private function foundQsCard(string $cardName, array $cards = [], array $cardsHistory = []): ?int
    {
        foreach ($cards as $card) {
            if ($cardName === $card['CardName']) {
                return $card['QsCreditCardID'];
            }
        }

        foreach ($cardsHistory as $card) {
            if (is_string($card)) {
                $this->logger->info('UpdateQsTransactionQmpCommand: cardHistory str', ['card' => $card]);

                continue;
            }

            if (is_array($card) && !array_key_exists('CardName', $card)) {
                $this->logger->info('UpdateQsTransactionQmpCommand: cardHistory arr', $card);

                continue;
            }

            if ($cardName === $card['CardName']) {
                return $card['QsCreditCardID'];
            }
        }

        $cardName = str_replace('credit card', 'card', $cardName);

        foreach ($cards as $card) {
            if ($cardName === str_replace('credit card', 'card', $card['CardName'])) {
                return $card['QsCreditCardID'];
            }
        }

        foreach ($cardsHistory as $card) {
            if (is_string($card)) {
                $this->logger->info('UpdateQsTransactionQmpCommand: cardHistory2', ['card' => $card]);

                continue;
            }

            if ($cardName === str_replace('credit card', 'card', $card['CardName'])) {
                return $card['QsCreditCardID'];
            }
        }

        return null;
    }

    private function fetchAccountSource(string $accountName): ?int
    {
        if (false !== stripos($accountName, 'Travel 101')) {
            return QsTransaction::ACCOUNT_AWARDTRAVEL101;
        } elseif (false !== stripos($accountName, 'Direct Links')) {
            return QsTransaction::ACCOUNT_DIRECT;
        } elseif (false !== stripos($accountName, 'Referral Links') || false !== stripos($accountName, 'Referral Credit Cards')) {
            return QsTransaction::ACCOUNT_CARDRATINGS;
        } else {
            return null;
            // throw new \Exception('Unknown Account type: ' . $accountName);
        }
    }

    private function sendStatistics(): void
    {
        $affStatData = $this->statistics->fetchAffiliateQsTransaction($this->setDate);
        $this->appBot->send(Slack::CHANNEL_AW_STATS, $affStatData);
        $this->appBot->send(Slack::CHANNEL_AW_ALL, $affStatData);
        $this->appBot->send(Slack::CHANNEL_AW_AT101_MODS, $affStatData);
    }

    private function sendQsTransactionCharts(?\DateTime $setDate = null, ?string $onlyChannel = null): void
    {
        $isPassDate = null !== $setDate;

        if (null === $setDate) {
            if (!empty($this->setDate)) {
                $setDate = $this->setDate;
            } else {
                $setDate = 1 === (int) date('j') ? new \DateTime('-1 day') : null;
            }
        }
        $date = $this->qsTransactionChart->fetchDate($setDate);
        $prevMonth = new \DateTime('@' . strtotime('first day of last month'));

        // # Clicks
        $clickGraph = $this->qsTransactionChart->getClicksGraph($setDate);

        if (empty($clickGraph)) {
            $date = $prevMonth;
            $clickGraph = $this->qsTransactionChart->getClicksGraph($prevMonth);
        }

        if (!empty($clickGraph)) {
            $clicks_tempFile = tempnam(sys_get_temp_dir(), 'qsClickCharts');
            $clickGraph->Stroke($clicks_tempFile);
            $clicksUpload = $this->appBot->uploadFile($clicks_tempFile);
        }
        // Clicks

        // # Revenue
        $revenueGraph = $this->qsTransactionChart->getRevenueGraph($setDate);

        if (empty($revenueGraph)) {
            $revenueGraph = $this->qsTransactionChart->getRevenueGraph($prevMonth);
            $dateRevenue = $prevMonth;
        }

        if (!empty($revenueGraph)) {
            $revenue_tempFile = tempnam(sys_get_temp_dir(), 'qsRevenueCharts');
            $revenueGraph->Stroke($revenue_tempFile);
            $revenueUpload = $this->appBot->uploadFile($revenue_tempFile);
        }
        // Revenue

        // # Pie Cards
        $cardsGraph = $this->qsTransactionChart->getCardsGraph($setDate, $isPassDate);

        if (empty($cardsGraph)) {
            exit('empty---cardsgraph');
            $cardsGraph = $this->qsTransactionChart->getCardsGraph($prevMonth);
            $dateCards = $prevMonth;
        }

        if (!empty($cardsGraph)) {
            $cards_tempFile = tempnam(sys_get_temp_dir(), 'qsCardsCharts');
            $cardsGraph->Stroke($cards_tempFile);
            $cardsUpload = $this->appBot->uploadFile($cards_tempFile);
        }
        // Pie Cards

        $attachments = [];

        $linkQsReport = function (\DateTime $dateFrom, \DateTime $dateTo): string {
            return 'https://awardwallet.com/manager/list.php?' .
                http_build_query(
                    [
                        'Schema' => 'Qs_Transaction',
                        'preset' => 'QS',
                        'dfrom' => $dateFrom->format('Y-m-01'),
                        'dto' => $dateTo->format('Y-m-d'),
                    ]
                );
        };

        if (isset($revenueUpload['success']) && $revenueUpload['success']) {
            $attachments[] = [
                'color' => QsTransactionChart::COLORS[QsTransaction::ACCOUNT_AWARDTRAVEL101],
                'title' => (isset($dateRevenue) ? $dateRevenue->format('F Y') : $date->format('F Y')) . ' - Credit Card Revenue per Day',
                'title_link' => $linkQsReport($dateRevenue ?? $date, new \DateTime()),
                'image_url' => $revenueUpload['publicUrl'],
            ];
        }

        if (isset($clicksUpload['success']) && $clicksUpload['success']) {
            $attachments[] = [
                'color' => QsTransactionChart::COLORS[QsTransaction::ACCOUNT_DIRECT],
                'title' => $date->format('F Y') . ' - Credit Card Total Clicks per Day',
                'title_link' => $linkQsReport($date, new \DateTime()),
                'image_url' => $clicksUpload['publicUrl'],
            ];
        }

        if (isset($cardsUpload['success']) && $cardsUpload['success']) {
            $attachments[] = [
                'color' => QsTransactionChart::COLORS[QsTransaction::ACCOUNT_CARDRATINGS],
                'title' => (isset($dateCards) ? $dateCards->format('F Y') : $date->format('F Y')) . ' - Card Breakdown',
                'title_link' => $linkQsReport($dateCards ?? $date, new \DateTime()),
                'image_url' => $cardsUpload['publicUrl'],
            ];
        }

        if (!empty($attachments)) {
            if (!empty($onlyChannel)) {
                $this->appBot->send($onlyChannel, ['attachments' => $attachments]);
            } else {
                $this->appBot->send(Slack::CHANNEL_AW_STATS, ['attachments' => $attachments]);
                $this->appBot->send(Slack::CHANNEL_AW_ALL, ['attachments' => $attachments]);
                array_walk(
                    $attachments,
                    function (&$arr, $key) {
                        unset($arr['title_link']);
                    }
                );
                $this->appBot->send(Slack::CHANNEL_AW_AT101_MODS, ['attachments' => $attachments]);
            }
        }

        isset($clicks_tempFile) ? unlink($clicks_tempFile) : null;
        isset($revenue_tempFile) ? unlink($revenue_tempFile) : null;
        isset($cards_tempFile) ? unlink($cards_tempFile) : null;
    }

    private function summaryMonthlyReport(): void
    {
        if (1 !== (int) date('j')) {
            return;
        }

        $date = (new \DateTime())->modify('last day of previous month');
        $date->modify('last day of previous month');

        $affStatData = $this->statistics->fetchAffiliateQsTransaction($date);
        array_unshift(
            $affStatData['blocks'],
            [
                'type' => 'divider',
            ],
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'Summary ' . $date->format('F Y'),
                ],
            ],
            [
                'type' => 'divider',
            ]);

        $this->appBot->send(Slack::CHANNEL_AW_STATS, $affStatData);
        $this->appBot->send(Slack::CHANNEL_AW_ALL, $affStatData);
        $this->appBot->send(Slack::CHANNEL_AW_AT101_MODS, $affStatData);

        $this->sendQsTransactionCharts($date);
    }

    private function updateStates(): void
    {
        $dateFrom = (new \DateTime())->sub(new \DateInterval('P14D'));
        $dateTo = new \DateTime();
        $between = ' BETWEEN ' . $this->connection->quote($dateFrom->format('Y-m-d 00:00:00')) . ' AND ' . $this->connection->quote($dateTo->format('Y-m-d 23:59:59'));

        $qsTransactions = $this->connection->executeQuery('
            SELECT qt.QsTransactionID, qt.UserID, qt.ClickDate, qt.SubAccountFicoState, qt.CreditCardState
            FROM QsTransaction qt
            WHERE
                    qt.UserID IS NOT NULL
                AND (qt.ClickDate ' . $between . ' OR qt.ProcessDate ' . $between . ')
                AND (qt.SubAccountFicoState IS NULL OR qt.CreditCardState IS NULL)
            ORDER BY qt.ClickDate DESC'
        )->fetchAllAssociative();

        $providersid = array_column(self::PROVIDER_FICO_CODES, 'providerId');
        $codes = array_column(self::PROVIDER_FICO_CODES, 'code');

        foreach ($qsTransactions as $qsTransaction) {
            $subAccountState = [];
            $creditCardState = [];

            if (empty($qsTransaction['SubAccountFicoState'])) {
                $subAccounts = $this->connection->fetchAllAssociative('
                    SELECT
                            sa.SubAccountID, sa.AccountID, sa.Balance, sa.Code,
                            a.SuccessCheckDate, a.UserID
                    FROM SubAccount sa
                    JOIN Account a ON (a.AccountID = sa.AccountID)
                    WHERE
                            a.ProviderID IN (?)
                        AND a.UserID = ?
                        AND a.UserAgentID IS NULL
                        AND sa.Code IN (?)
                        AND a.CreationDate < ?',
                    [$providersid, $qsTransaction['UserID'], $codes, $qsTransaction['ClickDate']],
                    [
                        \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                        \PDO::PARAM_INT,
                        \Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
                        \PDO::PARAM_STR,
                    ]
                );

                if (!empty($subAccounts)) {
                    foreach ($subAccounts as $subAccount) {
                        $subAccountState[] = [
                            'accountId' => $subAccount['AccountID'],
                            'subAccountId' => $subAccount['SubAccountID'],
                            'balance' => $subAccount['Balance'],
                            'code' => $subAccount['Code'],
                            'successCheckDate' => $subAccount['SuccessCheckDate'],
                        ];
                    }
                }
            }

            if (empty($qsTransaction['CreditCardState'])) {
                $creditCards = $this->connection->fetchAllAssociative('
                    SELECT UserID, CreditCardID, EarliestSeenDate, LastSeenDate 
                    FROM UserCreditCard
                    WHERE
                            UserID = ?
                        AND EarliestSeenDate < ?',
                    [$qsTransaction['UserID'], $qsTransaction['ClickDate']],
                    [\PDO::PARAM_INT, \PDO::PARAM_STR]
                );

                if (!empty($creditCards)) {
                    foreach ($creditCards as $creditCard) {
                        $creditCardState[] = [
                            'creditCardId' => $creditCard['CreditCardID'],
                            'earliestSeenDate' => $creditCard['EarliestSeenDate'],
                            'lastSeenDate' => $creditCard['LastSeenDate'],
                        ];
                    }
                }
            }

            if (!empty($subAccountState) || !empty($creditCardState)) {
                $this->connection->update(
                    'QsTransaction',
                    [
                        'SubAccountFicoState' => json_encode($subAccountState),
                        'CreditCardState' => json_encode($creditCardState),
                    ],
                    ['QsTransactionID' => $qsTransaction['QsTransactionID']]
                );
            }
        }
    }

    private function updateQsCreditCard(): void
    {
        $datePeriod = new \DateTime();
        3 == date('j')
            ? $datePeriod->sub(new \DateInterval('P1Y'))
            : $datePeriod->sub(new \DateInterval('P' . (self::NUMBER_WEEK_IN_PAST * 4) . 'D'));

        $transactions = $this->connection->fetchAllAssociative("
            SELECT QsTransactionID, Card as CardName
            FROM QsTransaction
            WHERE
                    QsCreditCardID IS NULL
                AND ClickDate >= '" . $datePeriod->format('Y-m-d') . "'
        ");

        [$foundCards, $notFound] = $this->getAssignCards($transactions);

        foreach ($transactions as $transaction) {
            $cardName = $transaction['CardName'];

            if (!array_key_exists($cardName, $foundCards)) {
                continue;
            }
            $qsTransactionId = (int) $transaction['QsTransactionID'];
            $qsCardId = (int) $foundCards[$cardName];

            $this->connection->update(
                'QsTransaction',
                ['QsCreditCardID' => $qsCardId],
                ['QsTransactionID' => $qsTransactionId]
            );
        }

        $qsCreditCards = $this->connection->fetchAllAssociative('
            SELECT QsCreditCardID, CardName FROM QsCreditCard WHERE IsHidden = 0 ORDER BY QsCreditCardID DESC
        ');
        $qsHistoryCreditCards = $this->connection->fetchAllAssociative('
            SELECT DISTINCT CardName, QsCreditCardID FROM QsCreditCardHistory
        ');

        $transactions = $this->connection->fetchAllAssociative("
            SELECT
                    qt.QsTransactionID, qt.QsCreditCardID, Card as CardName,
                    qcc.AwCreditCardID
            FROM QsTransaction qt
            LEFT JOIN QsCreditCard qcc ON qt.QsCreditCardID = qcc.QsCreditCardID
            WHERE
                    qt.QsCreditCardID IS NULL
                AND qt.ClickDate >= '" . $datePeriod->format('Y-m-d') . "'
        ");

        $permanent = [
            'marriott bonvoy business card from american express' => 93,
            'hilton honors card from american express' => 81,
            'td cash visa credit card' => 433,
            'bank of america premium rewards visa credit card' => 291,
            'bank of america travel rewards visa credit card' => 288,
            'disney premier visa' => 322,
            'chime credit builder secured credit builder visa card' => 411,
            'first progress platinum prestige mastercard secured' => 406,
        ];

        foreach ($transactions as $transaction) {
            $qtID = (int) $transaction['QsTransactionID'];

            $tCardName = $this->cleanCardName($transaction['CardName']);

            $founded = [];

            foreach ($qsCreditCards as $qsCard) {
                $qsCardName = $this->cleanCardName($qsCard['CardName']);

                if ($tCardName === $qsCardName) {
                    $founded[] = $qsCard['QsCreditCardID'];
                }
            }

            if (empty($founded)) {
                foreach ($qsHistoryCreditCards as $qsCard) {
                    $qsCardName = $this->cleanCardName($qsCard['CardName']);

                    if ($tCardName === $qsCardName) {
                        $founded[] = $qsCard['QsCreditCardID'];
                    }
                }
            }

            if (empty($founded) && array_key_exists($tCardName, $permanent)) {
                $founded[] = $permanent[$tCardName];
            }

            if (!empty($founded)) {
                $founded = array_unique($founded);

                if (1 === count($founded)) {
                    $this->connection->executeQuery('
                        UPDATE QsTransaction SET QsCreditCardID = ' . ((int) $founded[0]) . ' WHERE QsTransactionID = ' . $qtID . ' LIMIT 1
                    ');
                } else {
                    echo PHP_EOL . 'Multiple CardName found (' . $qtID . ') ' . var_export($founded, true) . PHP_EOL;
                }
            } else {
                echo PHP_EOL . ' not found(' . $qtID . '): ' . $tCardName . PHP_EOL;
            }
        }
    }

    private function cleanCardName(string $cardName): string
    {
        $cardName = trim(CreditCardMatcher::cleanSpecialSymbols($cardName));
        $cardName = trim(str_ireplace(
            ['(R)', '(C)', '(SM)', '(TM)', '(OMAAT)'],
            '',
            $cardName
        ));
        $cardName = preg_replace('/\s+/', ' ', $cardName);

        return strtolower($cardName);
    }

    private function fetchData(string $startDate, string $endDate)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_URL, sprintf(self::API_URL_GET_REPORT, self::REPORT_ACTUAL, $startDate, $endDate));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token,
        ]);

        $response = curl_exec($curl);
        $data = json_decode($response, true);

        if (!isset($data['data'])) {
            echo 'fetch period: ' . $startDate . ' / ' . $endDate;

            throw new \Exception($response);
        }

        $validateColumns = self::REPORT_ACTUAL === self::REPORT_VERSION_MASTER
            ? self::VALIDATE_COLUMNS
            : self::VALIDATE_DETAILED_COLUMNS;

        if (!empty(array_diff($data['data']['columns'], $validateColumns))
            || !empty(array_diff($validateColumns, $data['data']['columns']))
        ) {
            throw new \RuntimeException('Data columns do not match');
        }

        $records = $data['data']['records'];
        array_walk(
            $records,
            static function (&$row) use ($validateColumns) { $row = array_combine($validateColumns, $row); }
        );

        return $records;
    }

    private function getToken(): ?string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, self::API_URL_GET_TOKEN);
        curl_setopt($curl, CURLOPT_POSTFIELDS, []);
        curl_setopt($curl, CURLOPT_USERNAME, $this->login);
        curl_setopt($curl, CURLOPT_PASSWORD, $this->password);

        $response = curl_exec($curl);
        $result = json_decode($response);

        if (!empty($result->error)) {
            throw new \Exception('getToken error: ' . $result->error);
        }

        return $result->access_token ?? null;
    }
}
