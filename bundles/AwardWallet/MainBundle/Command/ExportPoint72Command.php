<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportPoint72Command extends Command
{
    private const PROPERTY_KINDS = [PROPERTY_KIND_STATUS, PROPERTY_KIND_STATUS_EXPIRATION, PROPERTY_KIND_YTD_SEGMENTS, PROPERTY_KIND_LAST_ACTIVITY, PROPERTY_KIND_MEMBER_SINCE, PROPERTY_KIND_MILES_TO_NEXT_LEVEL];

    private const PROPERTY_NAMES = [
        PROPERTY_KIND_STATUS => 'EliteLevel',
        PROPERTY_KIND_STATUS_EXPIRATION => 'EliteLevelExpiration',
        PROPERTY_KIND_YTD_SEGMENTS => 'YTDNights',
        PROPERTY_KIND_LAST_ACTIVITY => 'LastActivity',
        PROPERTY_KIND_MEMBER_SINCE => 'MemberSince',
        PROPERTY_KIND_MILES_TO_NEXT_LEVEL => 'PointsToNextLevel',
    ];

    private const HISTORY_TYPE_COLUMN = [
        'marriott' => 'Type',
        'hhonors' => 'Type',
        'ichotelsgroup' => 'Activity Type',
        'goldpassport' => 'Type',
        'triprewards' => 'Activity Type',
    ];

    private const HISTORY_BONUS_COLUMN = [
        'goldpassport' => 'Bonus',
    ];

    private const PROVIDERS = [
        'marriott',
        'hhonors',
        'ichotelsgroup',
        'goldpassport',
        'triprewards',
    ];

    protected static $defaultName = 'aw:export-point72';
    /**
     * @var Connection
     */
    private $unbufConnection;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var string
     */
    private $secret;

    public function __construct(Connection $unbufConnection, string $secret)
    {
        parent::__construct();
        $this->unbufConnection = $unbufConnection;
        $this->secret = $secret;
    }

    public function configure()
    {
        $this
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'output directory')
            ->addOption('last-updated', null, InputOption::VALUE_REQUIRED, 'dump only accounts last updated no more then N days ago', 30)
            ->addOption('last-history', null, InputOption::VALUE_REQUIRED, 'dump only accounts with history records posted N days ago or newer')
            ->addOption('encode', null, InputOption::VALUE_NONE, 'encode ids')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;

        if (empty($input->getOption('output-dir'))) {
            throw new \Exception("output-dir required");
        }

        $this->dumpAccounts($input->getOption('output-dir') . "/accounts.jsonl");
        $this->dumpHistory($input->getOption('output-dir') . "/history.jsonl");
        $this->dumpReservations($input->getOption('output-dir') . "/reservations.jsonl");

        return 0;
    }

    private function dumpAccounts(string $outputFileName)
    {
        $this->output->writeln("dumping accounts to {$outputFileName}");
        $file = fopen($outputFileName, "wb+");

        foreach (self::PROVIDERS as $providerCode) {
            $this->dumpProviderAccounts($providerCode, $file);
        }
        fclose($file);
    }

    private function dumpProviderAccounts(string $providerCode, $file)
    {
        $this->output->writeln("loading {$providerCode} accounts");
        $providerId = $this->unbufConnection->executeQuery("select ProviderID from Provider where Code = ?", [$providerCode])->fetch(FetchMode::COLUMN);

        $properties = $this->loadProperties($providerId);
        $eliteLevels = $this->loadEliteLevels($providerId);
        $textEliteLevels = $this->loadTextEliteLevels($providerId);
        $commonProperties = $this->loadProperties(null);

        $fields = [];
        $joins = [];

        foreach (array_merge($properties, $commonProperties) as $kind => $propertyId) {
            $fields[] = "p{$propertyId}.Val as p{$propertyId}";
            $joins[] = "left join AccountProperty p{$propertyId} on p{$propertyId}.AccountID = a.AccountID and p{$propertyId}.ProviderPropertyID = {$propertyId}";
        }

        $q = $this->unbufConnection->executeQuery(
            "select 
            concat(a.UserID, '_', coalesce(a.UserAgentID, '')) as UserID,
            a.AccountID,
            a.TotalBalance as Balance,
            a.ExpirationDate as BalanceExpirationDate,
            " . implode(", ", $fields) . "
        from
            Account a
            " . implode("\n", $joins) . " 
        where 
            a.ProviderID = :providerId
            and a.UpdateDate > adddate(now(), :lastUpdated)
        ",
            [
                "lastUpdated" => -1 * $this->input->getOption("last-updated"),
                "providerId" => $providerId,
            ]
        );

        $progress = new ProgressLogger(new Logger("main", [new StreamHandler('php://stdout')]), 100, 30);
        $count = 0;
        $encode = $this->input->getOption('encode');

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $count++;
            $progress->showProgress("dumping {$providerCode} accounts", $count);
            $row["ProviderCode"] = $providerCode;
            $row = $this->addProperties($row, $properties, $commonProperties);
            $row = $this->addEliteLevels($row, $eliteLevels, $textEliteLevels);

            if ($encode) {
                $row = $this->encode($row);
            }
            fwrite($file, json_encode($row) . "\n");
        }
        $this->output->writeln("dumped {$count} {$providerCode} accounts");
    }

    private function loadProperties(?int $providerId): array
    {
        return $this->unbufConnection->executeQuery("select 
            Kind, ProviderPropertyID
        from 
            ProviderProperty 
        where 
            Kind in (" . implode(", ", self::PROPERTY_KINDS) . ")
            and ProviderID = ?", [$providerId])->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function loadEliteLevels(int $providerId): array
    {
        $result = [];
        $q = $this->unbufConnection->executeQuery(
            "select
                *
            from
                EliteLevel
            where
                ProviderID = ?
            order by 
                `Rank`",
            [$providerId]
        );

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $result[$row['EliteLevelID']] = $row;
        }

        foreach ($result as &$row) {
            foreach ($result as $nextLevel) {
                if ($nextLevel["Rank"] > $row["Rank"]) {
                    $row["NextLevelName"] = $nextLevel["Name"];

                    break;
                }
            }
        }

        return $result;
    }

    private function loadTextEliteLevels(int $providerId): array
    {
        return $this->unbufConnection->executeQuery(
            "select
                lower(tel.ValueText),
                tel.EliteLevelID
            from
                TextEliteLevel tel
                join EliteLevel el on tel.EliteLevelID = el.EliteLevelID
            where
                el.ProviderID = ?
            ",
            [$providerId]
        )->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function addProperties(array $row, array $properties, array $commonProperties): array
    {
        foreach (self::PROPERTY_NAMES as $kind => $name) {
            foreach ([$properties, $commonProperties] as $map) {
                if (isset($map[$kind])) {
                    $key = "p" . $map[$kind];

                    if (isset($row[$key])) {
                        $row[$name] = $row[$key];
                    }
                }
            }
        }

        foreach (array_keys($row) as $key) {
            if ($key[0] === 'p') {
                unset($row[$key]);
            }
        }

        return $row;
    }

    private function addEliteLevels(array $row, array $eliteLevels, array $textEliteLevels): array
    {
        if (!isset($row["EliteLevel"])) {
            return $row;
        }

        $eliteLevelId = $textEliteLevels[strtolower($row["EliteLevel"])] ?? null;

        if (isset($eliteLevelId)) {
            $row["EliteLevel"] = $eliteLevels[$eliteLevelId]["Name"];
            $row["EliteLevelRank"] = $eliteLevels[$eliteLevelId]["Rank"];

            if (isset($eliteLevels[$eliteLevelId]["NextLevelName"])) {
                $row["NextEliteLevel"] = $eliteLevels[$eliteLevelId]["NextLevelName"];
            }
        }

        return $row;
    }

    private function encode(array $row): array
    {
        foreach (["AccountID", "UserID"] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = sha1($row[$key] . $this->secret . self::$defaultName);
            }
        }

        return $row;
    }

    private function dumpHistory(string $outputFileName)
    {
        $this->output->writeln("dumping history to {$outputFileName}");
        $file = fopen($outputFileName, "wb+");

        foreach (self::PROVIDERS as $providerCode) {
            $this->dumpProviderHistory($providerCode, $file);
        }
        fclose($file);
    }

    private function dumpProviderHistory(string $providerCode, $file)
    {
        $this->output->writeln("dumping history for $providerCode");
        $providerId = $this->unbufConnection->executeQuery("select ProviderID from Provider where Code = ?", [$providerCode])->fetch(FetchMode::COLUMN);
        $q = $this->unbufConnection->executeQuery("select
            h.*,
            concat(a.UserID, '_', coalesce(a.UserAgentID, '')) as UserID
        from
            AccountHistory h
            join Account a on h.AccountID = a.AccountID
        where 
            h.PostingDate >= '2017-07-01'
            and h.PostingDate < '2019-07-01'
            and a.UpdateDate > adddate(now(), :lastUpdated)
            and a.ProviderID = :providerId
        order by 
            a.AccountID,
            h.PostingDate,
            h.Position
        ", [
            "lastUpdated" => -1 * $this->input->getOption("last-updated"),
            "providerId" => $providerId,
        ]);

        $progress = new ProgressLogger(new Logger("main", [new StreamHandler('php://stdout')]), 100, 30);
        $count = 0;
        $accountHistory = [];
        $oldHistoryCount = 0;
        $accountId = null;

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $count++;
            $progress->showProgress("dumping {$providerCode} accounts", $count);
            $row["ProviderCode"] = $providerCode;

            if ($accountId === null) {
                $accountId = $row['AccountID'];
            }

            if ($accountId !== $row['AccountID']) {
                if (!$this->flushAccountHistory($accountHistory, $file)) {
                    $oldHistoryCount++;
                }
                $accountHistory = [];
                $accountId = $row['AccountID'];
            }
            $accountHistory[] = $row;
        }

        if (count($accountHistory) > 0) {
            if ($this->flushAccountHistory($accountHistory, $file)) {
                $oldHistoryCount++;
            }
        }

        $this->output->writeln("dumped {$count} {$providerCode} history rows, skipped rows: {$oldHistoryCount}");
    }

    private function flushAccountHistory(array $accountHistory, $file)
    {
        $maxPostingDate = 0;

        foreach ($accountHistory as $row) {
            $date = strtotime($row["PostingDate"]);

            if ($date > $maxPostingDate) {
                $maxPostingDate = $date;
            }
        }

        if (!empty($this->input->getOption('last-history')) && $maxPostingDate < strtotime("-{$this->input->getOption('last-history')} day")) {
            return false;
        }

        $encode = $this->input->getOption('encode');

        foreach ($accountHistory as $row) {
            $row = $this->formatHistoryRow($row);

            if ($encode) {
                $row = $this->encode($row);
            }
            fwrite($file, json_encode($row) . "\n");
        }
    }

    private function formatHistoryRow(array $row): array
    {
        $result = [
            'AccountID' => $row['AccountID'],
            'UserID' => $row['UserID'],
            'ProviderCode' => $row['ProviderCode'],
            'PostingDate' => $row['PostingDate'],
            'PointsEarned' => $row['Miles'],
        ];

        if (!empty($row['Info'])) {
            $info = unserialize($row['Info'], ['allowed_classes' => false]);

            if (!empty($info[self::HISTORY_TYPE_COLUMN[$row['ProviderCode']]])) {
                $result['ActivityType'] = $info[self::HISTORY_TYPE_COLUMN[$row['ProviderCode']]];
            }

            if (isset(self::HISTORY_BONUS_COLUMN[$row['ProviderCode']]) && !empty($info[self::HISTORY_BONUS_COLUMN[$row['ProviderCode']]])) {
                $result['BonusPointsEarned'] = $info[self::HISTORY_BONUS_COLUMN[$row['ProviderCode']]];
            }
        }

        return $result;
    }

    private function dumpReservations(string $outputFileName)
    {
        $this->output->writeln("dumping reservations to {$outputFileName}");
        $file = fopen($outputFileName, "wb+");

        foreach (self::PROVIDERS as $providerCode) {
            $this->dumpProviderReservations($providerCode, $file);
        }
        fclose($file);
    }

    private function dumpProviderReservations(string $providerCode, $file)
    {
        $this->output->writeln("dumping reservations for $providerCode");
        $providerId = $this->unbufConnection->executeQuery("select ProviderID from Provider where Code = ?", [$providerCode])->fetch(FetchMode::COLUMN);
        $q = $this->unbufConnection->executeQuery("select
            concat(r.UserID, '_', coalesce(r.UserAgentID, '')) as UserID,
            r.AccountID,
            r.Address,
            r.CheckInDate,
            r.CheckOutDate,
            r.Cost,
            r.CurrencyCode,
            r.GuestCount,
            r.HotelName,
            r.RoomCount,
            r.SpentAwards,
            r.Total
        from
            Reservation r
            join Account a on r.AccountID = a.AccountID
        where 
            r.CheckInDate >= '2017-07-01'
            and r.CheckInDate < '2019-07-01'
            and a.UpdateDate > adddate(now(), :lastUpdated)
            and r.ProviderID = :providerId
        ", [
            "lastUpdated" => -1 * $this->input->getOption("last-updated"),
            "providerId" => $providerId,
        ]);

        $progress = new ProgressLogger(new Logger("main", [new StreamHandler('php://stdout')]), 100, 30);
        $count = 0;
        $encode = $this->input->getOption('encode');

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $count++;
            $progress->showProgress("dumping {$providerCode} accounts", $count);
            $row["ProviderCode"] = $providerCode;

            if ($encode) {
                $row = $this->encode($row);
            }
            fwrite($file, json_encode($row) . "\n");
        }

        $this->output->writeln("dumped {$count} {$providerCode} reservations");
    }
}
