<?php

namespace AwardWallet\MainBundle\Command\Update;

use AwardWallet\MainBundle\Entity\QsTransaction;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\Charts\QsTransactionChart;
use AwardWallet\MainBundle\Service\Statistics;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateQsTransactionCommand extends Command
{
    protected static $defaultName = 'aw:update-qs-transaction';

    /* @var LoggerInterface $logger */
    private $logger;

    /* @var Connection $connection */
    private $connection;

    /** @var string */
    private $dsn;

    /** @var bool */
    private $isDebug;

    /** @var bool */
    private $isParseSite;

    /** @var array */
    private $duplicates = [];

    /** @var AppBot */
    private $appBot;

    /** @var Statistics */
    private $statistics;

    /** @var QsTransactionChart */
    private $qsTransactionChart;

    /** @var array */
    private $quinstreetCredential;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        AppBot $appBot,
        Statistics $statistics,
        QsTransactionChart $qsTransactionChart,
        string $dsn,
        array $quinstreetCredential
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->appBot = $appBot;
        $this->statistics = $statistics;
        $this->qsTransactionChart = $qsTransactionChart;
        $this->dsn = $dsn;
        $this->quinstreetCredential = $quinstreetCredential;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Update QuinStreet Transaction from FTP')
            // ->addOption('last', null, InputOption::VALUE_NONE, 'Process last day data')
            ->addOption('csv', null, InputOption::VALUE_OPTIONAL, 'CSV file processing')
            ->addOption('csvftp', null, InputOption::VALUE_OPTIONAL, 'CSV file processing (field structure ftp quinstreet)')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Debug')
            ->addOption('parse-site', null, InputOption::VALUE_OPTIONAL, 'Parse quinstreet.com site');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->isDebug = $input->getOption('debug');
        $this->isParseSite = $input->getOption('parse-site');

        $beforeLastTransaction = $this->connection->fetchColumn('SELECT ClickDate FROM QsTransaction ORDER BY ClickDate DESC LIMIT 1');

        if (null === $this->isParseSite) {
            $this->isParseSite = true;
            $this->quinstreetSiteParse();

            $this->diffLastTransactions($beforeLastTransaction);

            return 0;
        }

        if (!empty($csvFile = $input->getOption('csv'))) {
            if (!file_exists($csvFile)) {
                throw new \Exception('CSV file not found');
            }

            $data = $this->csvFromXlsData($csvFile);

            if (!empty($data)) {
                $this->updateTransactions($data);
            }

            return 0;
        }

        if (!empty($csvFile = $input->getOption('csvftp'))) {
            if (!file_exists($csvFile)) {
                throw new \Exception('CSV file not found');
            }

            $data = $this->parseCsv(file_get_contents($csvFile));

            if (!empty($data)) {
                $this->updateTransactions($data);
            }

            return 0;
        }

        /*
        if ($input->getOption('last')) {
            !$this->isDebug ?: print("checkLast()\r\n");

            return $this->checkLast();
        }
        */

        !$this->isDebug ?: print 'fetchContent()';
        $list = $this->fetchContent($this->dsn);

        if ($this->isDebug) {
            echo "fetchContent result:\r\n";
            print_r($list);
        }
        $files = [];
        $skip = [];

        foreach (explode("\n", $list) as $fileName) {
            if (1 === preg_match('#^.*(\d{4}-\d{2}-\d{2})\.csv$#', $fileName, $matches)) {
                /*
                $isExists = $this->connection->fetchAll('SELECT QsTransactionID FROM QsTransaction WHERE ClickDate =' . $this->connection->quote($matches[1]) . ' LIMIT 1');
                if (!empty($isExists)) {
                    $skip[] = $fileName;
                    continue;
                }
                */
                $files[] = $fileName;
            }
        }

        if ($this->isDebug) {
            echo 'files: ';
            print_r($files);
        }

        if (empty($files)) {
            if (!empty($skip)) {
                echo "All files are already processed. (skipped: " . \count($skip) . ")\r\n";
                !$this->isDebug ?: print_r($skip);

                return 0;
            }

            throw new \Exception('Nothing to process.');
        }

        foreach ($files as $file) {
            $content = $this->fetchContent($this->dsn, '/', $file);
            echo $file . "\r\n";
            $this->processContent($content);
        }

        $this->diffLastTransactions($beforeLastTransaction);

        return 0;
    }

    private function diffLastTransactions(?string $beforeLastTransaction = null)
    {
        // $afterLastTransaction = $this->connection->fetchColumn('SELECT ClickDate FROM QsTransaction ORDER BY ClickDate DESC LIMIT 1');

        // if ($beforeLastTransaction !== $afterLastTransaction) {
        $affStatData = $this->statistics->fetchAffiliateQsTransaction();
        $this->appBot->send(Slack::CHANNEL_AW_STATS, $affStatData);
        $this->appBot->send(Slack::CHANNEL_AW_ALL, $affStatData);
        $this->appBot->send(Slack::CHANNEL_AW_AT101_MODS, $affStatData);

        $this->sendQsTransactionCharts();
        // }
    }

    /*
    private function checkLast() : void
    {
        $list = $this->fetchContent($this->dsn);
        $fileMatch1 = '.*' . date('Y-m-d', strtotime('-1 day')) . '\.csv';
        $fileMatch2 = '.*' . date('Y-m-d', strtotime('-2 day')) . '\.csv';

        foreach (explode("\n", $list) as $fileName) {
            1 !== preg_match('#^' . $fileMatch1 . '#', $fileName) ?: $found1 = $fileName;
            1 !== preg_match('#^' . $fileMatch2 . '#', $fileName) ?: $found2 = $fileName;
        }

        $found = $found1 ?? $found2 ?? null;
        if (empty($found)) {
            throw new \Exception('File not found');
        }

        $content = $this->fetchContent($this->dsn, '/', $found);
        $this->processContent($content);
    }
    */

    /**
     * @return bool|string
     * @throws \Exception
     */
    private function fetchContent(string $dsn, string $dir = '', string $file = '')
    {
        $connectParams = ['scheme' => 'ftp', 'host' => '', 'user' => '', 'pass' => ''];

        if ('ftp://' !== substr($dsn, 0, 6)) {
            throw new \Exception('Unsupported scheme');
        }

        if (substr_count($dsn, '@') > 1 || false !== strpos($dsn, '#')) {
            $parts = explode('@', strrev($dsn), 2);
            $connectParams['host'] = strrev($parts[0]);
            [$connectParams['user'], $connectParams['pass']] = explode(':', substr(strrev($parts[1]), 6));
        } else {
            $connectParams = parse_url($dsn);
        }

        if (empty($connectParams['user']) || empty($connectParams['pass'])) {
            throw new \Exception('Login or password are undefined');
        }

        if ('ftp.' !== substr($connectParams['host'], 0, 4)) {
            $connectParams['host'] = 'ftp.' . $connectParams['host'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $connectParams['scheme'] . '://' . $connectParams['host'] . $dir . $file);
        curl_setopt($ch, CURLOPT_PORT, 21);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $connectParams['user'] . ':' . $connectParams['pass']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_DIRLISTONLY, empty($file));

        $content = curl_exec($ch);
        !$this->isDebug ?: print "\r\n" . 'fetchContent(dir[' . $dir . '], file[' . $file . ']) => ' . substr($content, 0, 500) . "...\r\n";

        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);

        return $content;
    }

    /**
     * @throws \Exception
     */
    private function processContent(string $content): bool
    {
        if (empty($content)) {
            throw new \Exception('Empty contents of csv file, possibly error');
        }

        $data = $this->parseCsv($content);
        echo ' - rows count ' . \count($data) . "\r\n";

        if (!empty($data)) {
            $this->updateTransactions($data);
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    private function parseCsv(string $text): ?array
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        $file = explode("\n", $text);

        $csv = array_map('str_getcsv', $file);

        if (1 === count($csv[count($csv) - 1])) {
            unset($csv[count($csv) - 1]);
        }
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
            unset($a['']);
        });
        array_shift($csv);

        // old format
        if (array_key_exists('Var1', $csv[0])) {
            echo "skip old file\r\n";

            return null;
        }

        $requiredFields = ['ClickDate', 'Account', 'Var2', 'CardName', 'Clicks', 'Earnings', 'Approvals'];

        if ((bool) array_diff_key(array_flip($requiredFields), $csv[0])) {
            throw new \InvalidArgumentException('Incorrect data, check availability: ' . implode(',', $requiredFields));
        }

        $data = [];

        foreach ($csv as $row) {
            $rec = [];

            $rec['RawAccount'] = $row['Account'];

            if (false !== strpos($row['Account'], 'Referral Links to CardRatings')) {
                $rec['Account'] = QsTransaction::ACCOUNT_CARDRATINGS;
            } elseif (false !== strpos($row['Account'], 'Direct')) {
                $rec['Account'] = QsTransaction::ACCOUNT_DIRECT;
            } elseif (false !== strpos($row['Account'], 'Award Travel 101')) {
                $rec['Account'] = QsTransaction::ACCOUNT_AWARDTRAVEL101;
            }

            if (!empty($row['Var2'])) {
                $vars = array_filter(explode('.', $row['Var2']));

                if (false === strpos($row['Var2'], '~') && 1 === count($vars)) {
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

                    foreach ([
                        'source' => 'Source',
                        'pid' => 'BlogPostID',
                        'mid' => 'MID',
                        'cid' => 'CID',
                        'rkbtyn' => 'RefCode',
                        'exit' => 'Exit',
                    ] as $var => $column) {
                        array_key_exists($var, $vars) ? $rec[$column] = $vars[$var] : null;
                    }
                }
            }
            $rec['RawVar1'] = $row['Var2'];

            $rec['CardName'] = $row['CardName'];
            $rec['Clicks'] = $row['Clicks'];
            $rec['Earnings'] = ltrim($row['Earnings'], '$ ');
            $rec['Earnings'] = str_replace(',', '.', $rec['Earnings']);
            $rec['Approvals'] = $row['Approvals'];
            $rec['Applications'] = $row['Applications'];
            $rec['Click_ID'] = $row['Click_ID'];

            $rec['ClickDate'] = $this->fetchDate($row['ClickDate']);

            if (!empty($row['SearchDate'])) {
                $rec['SearchDate'] = $this->fetchDate($row['SearchDate']);
            }

            if ('null' !== strtolower($row['ProcessDate']) && !empty($row['ProcessDate'])) {
                $rec['ProcessDate'] = $this->fetchDate($row['ProcessDate']);
            }

            $data[] = $rec;
        }

        return $data;
    }

    private function fetchDate(string $strDate): \DateTime
    {
        if (false !== strpos($strDate, ' ')) {
            $date = explode(' ', $strDate)[0];
            $date = explode('/', $date);
        } else {
            $date = explode('/', $strDate);
        }

        if (2 === strlen($date[2])) {
            $date[2] = substr(date('Y'), 0, 2) . $date[2];
        }

        return new \DateTime('@' . strtotime($date[2] . '-' . $date[0] . '-' . $date[1]));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateTransactions(array $data): bool
    {
        $users = [];
        $refCodes = array_unique(array_column($data, 'RefCode'));

        if (!empty($refCodes)) {
            $users = $this->connection->fetchAll(
                'SELECT UserID, RefCode FROM Usr WHERE RefCode IN (?)',
                [$refCodes], [Connection::PARAM_STR_ARRAY]
            );

            if (!empty($users)) {
                $users = array_combine(array_column($users, 'RefCode'), array_column($users, 'UserID'));
            }
        }

        $symbols = ['®', '℠', '™', '(R)', '(SM)', '(TM)'];
        $cards = $this->connection->fetchAll('SELECT QsCreditCardID, CardName FROM QsCreditCard');

        foreach ($cards as $i => $card) {
            $cards[$i]['CardName'] = strtolower(str_replace($symbols, '', $cards[$i]['CardName']));
        }
        $cardsHistory = $this->connection->fetchAll('SELECT DISTINCT CardName, QsCreditCardID FROM QsCreditCardHistory');

        foreach ($cardsHistory as $i => $card) {
            $cardsHistory[$i]['CardName'] = strtolower(str_replace($symbols, '', $cardsHistory[$i]['CardName']));
        }

        $notFound = [];
        $foundCards = [];
        $dataCards = array_column($data, 'CardName');
        $dataCards = array_unique($dataCards);

        foreach ($dataCards as $card) {
            $qsCardId = $this->foundQsCard(strtolower(str_replace($symbols, '', $card)), $cards, $cardsHistory);

            if (empty($qsCardId)) {
                $notFound[] = $card;

                continue;
            }

            $foundCards[$card] = $qsCardId;
        }

        $index = 0;
        $duplicatesCount = 0;
        $insertCount = 0;

        foreach ($data as $row) {
            $upd = [];

            if (array_key_exists($row['CardName'], $foundCards)) {
                $upd[] = ['field' => 'QsCreditCardID', 'value' => $foundCards[$row['CardName']], 'type' => \PDO::PARAM_INT];
            }

            $upd[] = ['field' => 'ClickDate', 'value' => $row['ClickDate']->format('Y-m-d'), 'type' => \PDO::PARAM_STR];
            !isset($row['SearchDate']) ?: $upd[] = ['field' => 'SearchDate', 'value' => $row['SearchDate']->format('Y-m-d'), 'type' => \PDO::PARAM_STR];
            !isset($row['ProcessDate']) ?: $upd[] = ['field' => 'ProcessDate', 'value' => $row['ProcessDate']->format('Y-m-d'), 'type' => \PDO::PARAM_STR];
            $upd[] = ['field' => 'Card', 'value' => $row['CardName'], 'type' => \PDO::PARAM_STR];

            if (isset($row['RefCode']) && array_key_exists($row['RefCode'], $users)) {
                $upd[] = ['field' => 'UserID', 'value' => $users[$row['RefCode']], 'type' => \PDO::PARAM_INT];
            }

            foreach (['Account', 'BlogPostID', 'Clicks', 'Approvals'] as $field) {
                if (array_key_exists($field, $row)) {
                    $upd[] = ['field' => $field, 'value' => (int) $row[$field], 'type' => \PDO::PARAM_INT];
                }
            }

            $upd[] = ['field' => 'Earnings', 'value' => (float) $row['Earnings'], 'type' => \PDO::PARAM_STR];

            if (array_key_exists('EstEarnings', $row)) {
                $upd[] = ['field' => 'EstEarnings', 'value' => (float) $row['EstEarnings'], 'type' => \PDO::PARAM_STR];
            }
            $upd[] = ['field' => 'CPC', 'value' => (float) $row['CPC'], 'type' => \PDO::PARAM_STR];

            foreach (['Source', 'Exit', 'MID', 'CID', 'RefCode', 'RawAccount', 'RawVar1'] as $field) {
                if (array_key_exists($field, $row)) {
                    $upd[] = ['field' => $field, 'value' => $row[$field], 'type' => \PDO::PARAM_STR];
                }
            }

            $upd[] = ['field' => 'Applications', 'value' => (int) $row['Applications'], 'type' => \PDO::PARAM_INT];
            $upd[] = ['field' => 'CreationDate', 'value' => date('Y-m-d'), 'type' => \PDO::PARAM_STR];

            if ($this->isParseSite) {
                $upd[] = ['field' => 'Hash', 'value' => $row['Hash'], 'type' => \PDO::PARAM_STR];
            } else {
                $upd[] = ['field' => 'Click_ID', 'value' => $row['Click_ID'], 'type' => \PDO::PARAM_INT];
            }

            $sql = '
                INSERT INTO 
                    `QsTransaction` (`' . implode('`,`', array_column($upd, 'field')) . '`)
                VALUES
                    (' . rtrim(str_repeat('?,', count($upd)), ',') . ')';

            try {
                $this->connection->executeQuery($sql, array_column($upd, 'value'), array_column($upd, 'type'));
                ++$insertCount;
            } catch (UniqueConstraintViolationException $e) {
                $this->duplicates[] = $row;
                ++$duplicatesCount;

                $sql = 'UPDATE `QsTransaction` SET ';

                foreach ($upd as $field) {
                    if ('Clicks' === $field['field'] && !$this->isParseSite) {
                        continue;
                    }
                    $sql .= $this->connection->quoteIdentifier($field['field']) . ' = ' . $this->connection->quote($field['value'], $field['type']) . ',';
                }
                $sql = rtrim($sql, ',');

                if ($this->isParseSite) {
                    $sql .= ' WHERE Hash = ' . $this->connection->quote($row['Hash']) . ' LIMIT 1';
                } else {
                    $sql .= ' WHERE Click_ID = ' . (int) $row['Click_ID'] . ' LIMIT 1';
                }
                $affectedUpdate = $this->connection->executeUpdate($sql);

                if ($this->isDebug) {
                    if ($this->isParseSite) {
                        echo ++$index . ') [' . $affectedUpdate . '] duplicate Hash = ' . $row['Hash'] . ' : (' . $row['ClickDate']->format('Y-m-d') . ' ; ' . $row['RawAccount'] . ' ; ' . $row['RawVar1'] . ' ; ' . $row['CardName'] . ')' . "\r\n";
                    } else {
                        echo ++$index . ') [' . $affectedUpdate . '] duplicate ClickID = ' . $row['Click_ID'] . "\r\n";
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception('UpdateQSTransactionCommand error: ' . $e->getMessage());
            }
        }
        echo ' - insert rows count: ' . $insertCount . "\r\n";
        echo ' - dupicates ' . ($this->isParseSite ? ' Hash ' : ' Click_ID ') . ' rows: ' . $duplicatesCount . "\r\n";

        $this->updateUserIDbyRefCode();

        return true;
    }

    private function foundQsCard(string $cardName, array $cards = [], array $cardsHistory = []): ?int
    {
        foreach ($cards as $card) {
            if ($cardName === $card['CardName']) {
                return $card['QsCreditCardID'];
            }
        }

        foreach ($cardsHistory as $card) {
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
            if ($cardName === str_replace('credit card', 'card', $card['CardName'])) {
                return $card['QsCreditCardID'];
            }
        }

        return null;
    }

    private function csvFromXlsData(string $filePath): array
    {
        ini_set('memory_limit', '2048M');
        $text = file_get_contents($filePath);
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        $file = explode("\n", $text);

        $csv = [];

        foreach ($file as $item) {
            $csv[] = str_getcsv($item, ';');
        }

        if (1 === count($csv[count($csv) - 1])) {
            unset($csv[count($csv) - 1]);
        }
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
            unset($a['']);
        });
        array_shift($csv);

        $requiredFields = ['clickdate', 'accountname', 'cardname', 'clicks', 'supplierearnings', 'approvals', 'click_id'];

        if ((bool) array_diff_key(array_flip($requiredFields), $csv[0])) {
            throw new \InvalidArgumentException('Incorrect data, check availability: ' . implode(',', $requiredFields));
        }

        $var2Key = array_key_exists('var2', $csv[0]) ? 'var2' : null;

        if (empty($var2Key) && array_key_exists('aa.var2', $csv[0])) {
            $var2Key = 'aa.var2';
        }

        if (empty($var2Key)) {
            throw new \InvalidArgumentException('Field var2 not found: ' . implode(',', $requiredFields));
        }

        $data = [];

        foreach ($csv as $row) {
            $rec = [];

            $searchDate = explode('.', $row['searchdate']);
            $rec['SearchDate'] = new \DateTime('@' . strtotime($searchDate[2] . '-' . $searchDate[1] . '-' . $searchDate[0]));

            $clickDate = explode('.', $row['clickdate']);
            $rec['ClickDate'] = new \DateTime('@' . strtotime($clickDate[2] . '-' . $clickDate[1] . '-' . $clickDate[0]));

            if ('null' !== strtolower($row['processdate']) && !empty($row['processdate'])) {
                $processDate = explode('.', $row['processdate']);
                $rec['ProcessDate'] = new \DateTime('@' . strtotime($processDate[2] . '-' . $processDate[1] . '-' . $processDate[0]));
            }

            $rec['RawAccount'] = $row['accountname'];

            if (false !== strpos($row['accountname'], 'Referral Links to CardRatings')) {
                $rec['Account'] = QsTransaction::ACCOUNT_CARDRATINGS;
            } elseif (false !== strpos($row['accountname'], 'Direct')) {
                $rec['Account'] = QsTransaction::ACCOUNT_DIRECT;
            } elseif (false !== strpos($row['accountname'], 'Award Travel 101')) {
                $rec['Account'] = QsTransaction::ACCOUNT_AWARDTRAVEL101;
            }

            if (!empty($row[$var2Key])) {
                $vars = array_filter(explode('.', $row[$var2Key]));

                if (false === strpos($row[$var2Key], '~') && 1 === count($vars)) {
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

                        if (2 === \count($values)) {
                            [$k, $v] = $values;
                            $vars[$k] = $v;
                        }
                    }

                    foreach ([
                        'source' => 'Source',
                        'pid' => 'BlogPostID',
                        'mid' => 'MID',
                        'cid' => 'CID',
                        'rkbtyn' => 'RefCode',
                        'exit' => 'Exit',
                    ] as $var => $column) {
                        array_key_exists($var, $vars) ? $rec[$column] = $vars[$var] : null;
                    }
                }
            }
            $rec['RawVar1'] = $row[$var2Key];

            if (empty($rec['RawVar1']) && !empty($row['custom'])) {
                $rec['RawVar1'] = 'custom=' . $row['custom'];
            }

            $rec['CardName'] = $row['cardname'];
            $rec['Clicks'] = $row['clicks'];
            $rec['Earnings'] = ltrim($row['supplierearnings'], '$ ');
            $rec['Earnings'] = str_replace(',', '.', $rec['Earnings']);

            $rec['Approvals'] = $row['approvals'];
            $rec['Applications'] = $row['applications'];
            $rec['Click_ID'] = $row['click_id'];

            $data[] = $rec;
        }
        echo 'Row Count: ' . \count($data) . "\r\n";

        return $data;
    }

    private function updateUserIDbyRefCode()
    {
        return $this->connection->executeUpdate('
            UPDATE QsTransaction qt
            JOIN Usr u ON (u.RefCode = qt.RefCode)
            SET qt.UserID = u.UserID
            WHERE
                    qt.RefCode IS NOT NULL
                AND qt.UserID IS NULL
                AND u.UserID IS NOT NULL
        ');
    }

    private function sendQsTransactionCharts(): void
    {
        $setDate = 1 === (int) date('j') ? new \DateTime('-1 day') : null;
        $date = $this->qsTransactionChart->fetchDate($setDate);
        $prevMonth = new \DateTime('@' . strtotime('first day of last month'));

        $clickGraph = $this->qsTransactionChart->getClicksGraph($setDate);

        if (empty($clickGraph)) {
            $date = $prevMonth;
            $clickGraph = $this->qsTransactionChart->getClicksGraph($prevMonth);
        }

        if (!empty($clickGraph)) {
            $clicks_tempFile = tempnam(sys_get_temp_dir(), 'qsCharts');
            $clickGraph->Stroke($clicks_tempFile);
            $clicksUpload = $this->appBot->uploadFile($clicks_tempFile);
        }

        $revenueGraph = $this->qsTransactionChart->getRevenueGraph($setDate);

        if (empty($revenueGraph)) {
            $revenueGraph = $this->qsTransactionChart->getRevenueGraph($prevMonth);
            $dateRevenue = $prevMonth;
        }

        if (!empty($revenueGraph)) {
            $revenue_tempFile = tempnam(sys_get_temp_dir(), 'qsCharts');
            $revenueGraph->Stroke($revenue_tempFile);
            $revenueUpload = $this->appBot->uploadFile($revenue_tempFile);
        }

        $cardsGraph = $this->qsTransactionChart->getCardsGraph($setDate);

        if (empty($cardsGraph)) {
            $cardsGraph = $this->qsTransactionChart->getCardsGraph($prevMonth);
            $dateCards = $prevMonth;
        }

        if (!empty($cardsGraph)) {
            $cards_tempFile = tempnam(sys_get_temp_dir(), 'qsCharts');
            $cardsGraph->Stroke($cards_tempFile);
            $cardsUpload = $this->appBot->uploadFile($cards_tempFile);
        }

        $attachments = [];

        $linkQsReport = function (\DateTime $dateFrom, \DateTime $dateTo): string {
            return 'https://awardwallet.com/manager/list.php?' .
                http_build_query([
                    'Schema' => 'Qs_Transaction',
                    'preset' => 'QS',
                    'dfrom' => $dateFrom->format('Y-m-01'),
                    'dto' => $dateTo->format('Y-m-d'),
                ]);
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
            $this->appBot->send(Slack::CHANNEL_AW_STATS, ['attachments' => $attachments]);
            $this->appBot->send(Slack::CHANNEL_AW_ALL, ['attachments' => $attachments]);
            array_walk($attachments, function (&$arr, $key) {
                unset($arr['title_link']);
            });
            $this->appBot->send(Slack::CHANNEL_AW_AT101_MODS, ['attachments' => $attachments]);
        }

        isset($clicks_tempFile) ? unlink($clicks_tempFile) : null;
        isset($revenue_tempFile) ? unlink($revenue_tempFile) : null;
        isset($cards_tempFile) ? unlink($cards_tempFile) : null;
    }

    private function quinstreetSiteParse(): void
    {
        $csv = $this->fetchQuinstreetSiteCsv();
        $data = $this->parseQuinstreetSiteCsv($csv);

        if (!empty($data)) {
            $this->updateTransactions($data);
        }
    }

    private function fetchQuinstreetSiteCsv(): string
    {
        $curl = new \CurlDriver();
        $http = new \HttpBrowser('none', $curl);

        $http->GetURL('https://www.login.quinstreet.com/Marketplace/Login.aspx');

        if (!$http->ParseForm('form1')) {
            throw new \RuntimeException('Login form Not Found');
        }
        $http->SetInputValue('txtLoginName', $this->quinstreetCredential['login']);
        $http->SetInputValue('txtPassword', $this->quinstreetCredential['password']);
        $http->SetInputValue('__EVENTTARGET', 'btnLogin');
        $http->FormURL = 'https://www.login.quinstreet.com/Marketplace/Login.aspx';
        $http->PostForm();

        $http->GetURL('https://www.login.quinstreet.com/Marketplace/Reporting/ClientReports.aspx');
        $ctl00_sm1_TSM = $http->FindNodes('//script[contains(@src, "_TSM_CombinedScripts_") and contains(@src, "Version")]/@src', null, "/TSM_CombinedScripts_=([^\"]+)/");

        if (!$http->ParseForm('aspnetForm') || !$ctl00_sm1_TSM) {
            throw new \RuntimeException('Form Error or Not Found');
        }
        $ctl00_sm1_TSM = array_map(function ($item) {
            return urldecode($item);
        }, $ctl00_sm1_TSM);
        $http->SetInputValue('ctl00_sm1_TSM', implode('', $ctl00_sm1_TSM) . ";");
        $http->SetInputValue('ctl00$sm1', 'ctl00$ctl00$_Content$pnlContentPanel|ctl00$_Content$btnGetReport');
        $http->SetInputValue('__EVENTTARGET', 'ctl00$_Content$btnGetReport');
        $http->SetInputValue('__ASYNCPOST', 'true');
        $http->SetInputValue('RadAJAXControlID', 'ctl00_MasterRadAjaxManager');

        $http->SetInputValue('ctl00$_Content$chkGroupByDay', 'on');
        $http->SetInputValue('ctl00$_Content$chkGroupByCard', 'on');
        $http->SetInputValue('ctl00$_Content$chkGroupByCustom', 'on');
        $http->SetInputValue('ctl00$_Content$chkGroupByAccount', 'on');

        $http->unsetInputValue('ctl00$_Content$chkGroupByWeek');
        $http->unsetInputValue('ctl00$_Content$chkGroupByCardType');
        $http->unsetInputValue('ctl00$_Content$chkGroupBySearchType');
        $http->unsetInputValue('ctl00$_Content$chkGroupByClickDate');

        $date = new \DateTime();
        $period = (new \DateTime())->modify('-31 days');

        $http->SetInputValue('ctl00$_Content$DateDropdownAndTextBoxesUCControl$txtDateDropdownAndTextboxesBeginDateUC', $period->format('Y-m-d'));
        $http->SetInputValue('ctl00__Content_DateDropdownAndTextBoxesUCControl_txtDateDropdownAndTextboxesBeginDateUC_dateInput_text', $period->format('m/d/Y'));
        $http->SetInputValue('ctl00$_Content$DateDropdownAndTextBoxesUCControl$txtDateDropdownAndTextboxesBeginDateUC$dateInput', $period->format('Y-m-d-00-00-00'));
        $http->SetInputValue('ctl00$_Content$DateDropdownAndTextBoxesUCControl$txtDateDropdownAndTextboxesEndDateUC', $date->format('Y-m-d'));
        $http->SetInputValue('ctl00__Content_DateDropdownAndTextBoxesUCControl_txtDateDropdownAndTextboxesEndDateUC_dateInput_text', $date->format('m/d/Y'));

        $form = $http->Form;
        $http->PostForm();

        // Export
        $http->Form = $form;
        $http->unsetInputValue('__ASYNCPOST');
        $http->unsetInputValue('RadAJAXControlID');
        $http->unsetInputValue('ctl00$sm1');
        $http->SetInputValue('__EVENTTARGET', 'ctl00$_Content$radGridReport$ctl00$ctl02$ctl00$btnExport');
        $target = $http->FindPreg('/__EVENTVALIDATION\|([^\|]+)/');
        $viewState1 = $http->FindPreg('/__VIEWSTATE1\|([^\|]+)/');

        if (!$target || !$viewState1) {
            throw new \RuntimeException('Event/State Error');
        }
        $http->SetInputValue('__EVENTVALIDATION', $target);
        $http->SetInputValue('ctl00__Content_radGridReport_ClientState', '');

        $http->SetInputValue('__VIEWSTATE1', $viewState1);
        $http->PostForm();

        $csv = $http->Response['original_body'];

        $headerColValidation = '"Date","Account","Var1","Custom","Card","Clicks","Earnings","Est Earnings","CPC","Approvals"';

        if (false === strpos($csv, $headerColValidation)) {
            throw new \RuntimeException('CSV Export error');
        }

        return $csv;
    }

    private function parseQuinstreetSiteCsv(string $text): ?array
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        $file = explode("\n", $text);

        $csv = array_map('str_getcsv', $file);

        if (1 === count($csv[count($csv) - 1])) {
            unset($csv[count($csv) - 1]);
        }
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
            unset($a['']);
        });
        array_shift($csv);

        $requiredFields = ['Date', 'Account', 'Var1', 'Custom', 'Card', 'Clicks', 'Earnings', 'Est Earnings', 'Approvals'];

        if ((bool) array_diff_key(array_flip($requiredFields), $csv[0])) {
            throw new \InvalidArgumentException('Incorrect data, check availability: ' . implode(',', $requiredFields));
        }

        $data = [];

        foreach ($csv as $row) {
            $rec = [];

            $rec['RawAccount'] = $row['Account'];

            if (false !== strpos($row['Account'], 'Referral Links to CardRatings')) {
                $rec['Account'] = QsTransaction::ACCOUNT_CARDRATINGS;
            } elseif (false !== strpos($row['Account'], 'Direct')) {
                $rec['Account'] = QsTransaction::ACCOUNT_DIRECT;
            } elseif (false !== strpos($row['Account'], 'Award Travel 101')) {
                $rec['Account'] = QsTransaction::ACCOUNT_AWARDTRAVEL101;
            }

            if (!empty($row['Var1'])) {
                $vars = array_filter(explode('.', $row['Var1']));

                if (false === strpos($row['Var1'], '~') && 1 === count($vars)) {
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

                    foreach ([
                        'source' => 'Source',
                        'pid' => 'BlogPostID',
                        'mid' => 'MID',
                        'cid' => 'CID',
                        'rkbtyn' => 'RefCode',
                        'exit' => 'Exit',
                    ] as $var => $column) {
                        array_key_exists($var, $vars) ? $rec[$column] = $vars[$var] : null;
                    }
                }
            }

            $rec['Applications'] = null;
            $rec['Click_ID'] = null;

            $rec['RawVar1'] = $row['Var1'];

            $rec['CardName'] = $row['Card'];
            $rec['Clicks'] = $row['Clicks'];
            $rec['Earnings'] = ltrim($row['Earnings'], '$ ');
            $rec['EstEarnings'] = ltrim($row['Est Earnings'], '$ ');
            $rec['Earnings'] = str_replace(',', '.', $rec['Earnings']);
            $rec['Approvals'] = $row['Approvals'];
            $rec['CPC'] = str_replace(',', '.', trim($row['CPC'], '$ '));
            $rec['ClickDate'] = $this->fetchDate($row['Date']);

            if ((float) $rec['Earnings'] > 0.01) {
                $rec['ProcessDate'] = $rec['ClickDate'];
            }

            $rec['Hash'] = sha1($rec['ClickDate']->format('Y-m-d') . $rec['RawAccount'] . $rec['RawVar1'] . $rec['CardName']);

            $data[] = $rec;
        }

        return $data;
    }
}
