<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Service\AccountHistory\MultiplierService;
use AwardWallet\MainBundle\Service\CreditCards\MerchantDisplayNameGenerator;
use AwardWallet\MainBundle\Service\CreditCards\MerchantNameNormalizer;
use AwardWallet\MainBundle\Service\CreditCards\PatternMatcher;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;
use AwardWallet\MainBundle\Service\CreditCards\Structures\MerchantScanInfo;
use AwardWallet\MainBundle\Service\CreditCards\Structures\NameInfo;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ScanHistoryCommand extends Command
{
    public const PACKAGE_SIZE = 100;
    public const DUMP_STATUS_TIME = 30;

    public static $defaultName = 'aw:credit-cards:scan-history';

    private LoggerInterface $logger;
    private Connection $connection;
    private PatternMatcher $patternMatcher;
    private ShoppingCategoryMatcher $categoryMatcher;
    private Connection $unbufConnection;
    private MerchantNameNormalizer $merchantNameNormalizer;

    /**
     * @var MerchantScanInfo[]
     */
    private array $merchantsByPattern = [];
    /**
     * @var MerchantScanInfo[]
     */
    private array $merchantsByName = [];
    private array $groupByCategoryId;
    private ClockInterface $clock;
    private array $creditCardToIndex;
    private array $creditCardFromIndex;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        Connection $unbufConnection,
        PatternMatcher $patternMatcher,
        ShoppingCategoryMatcher $categoryMatcher,
        MerchantNameNormalizer $merchantNameNormalizer,
        ClockInterface $clock
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->unbufConnection = $unbufConnection;
        $this->patternMatcher = $patternMatcher;
        $this->categoryMatcher = $categoryMatcher;
        $this->merchantNameNormalizer = $merchantNameNormalizer;

        parent::__construct();
        $this->clock = $clock;
    }

    protected function configure()
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'sql where')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('save-to-file', null, InputOption::VALUE_REQUIRED, 'save data read from db to file, to use in --load-from-file')
            ->addOption('load-from-file', null, InputOption::VALUE_REQUIRED, 'read from file instead of db. for benchmarking')
            ->addOption('dump-memory-usage', null, InputOption::VALUE_NONE)
            ->addOption('save-merchants', null, InputOption::VALUE_REQUIRED, 'save merchants to directory')
            ->addOption('load-merchants', null, InputOption::VALUE_REQUIRED, 'load merchants from directory')
            ->addOption('skip-scan', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '6G');

        $this->groupByCategoryId = $this->connection->fetchAllKeyValue("select ShoppingCategoryID, ShoppingCategoryGroupID 
        from ShoppingCategory where ShoppingCategoryGroupID is not null");
        $this->patternMatcher->logStats();
        $creditCards = $this->connection->fetchFirstColumn("select CreditCardID from CreditCard");

        if (count($creditCards) > 254) {
            throw new \Exception("too many credit cards, could not pack them to one byte");
        }

        $this->creditCardToIndex = array_combine($creditCards, range(1, count($creditCards)));
        $this->creditCardFromIndex = array_flip($this->creditCardToIndex);

        // $this->patternMatcher->logPatterns();

        if ($dir = $input->getOption('load-merchants')) {
            $this->logger->notice("mem before unserializing: " . number_format(memory_get_usage(true) / 1024));
            $this->merchantsByPattern = $this->loadMap($dir . '/byPattern.jsonl');
            $this->merchantsByName = $this->loadMap($dir . '/byName.jsonl');
            $this->logger->notice("mem after unserializing: " . number_format(memory_get_usage(true) / 1024));
        }

        if (!$input->getOption('skip-scan')) {
            $this->scanHistory($input);
        }

        if ($input->getOption('dump-memory-usage')) {
            $this->logger->info("merchantsByPattern size: " . number_format(strlen(json_encode($this->merchantsByPattern))));
            $this->logger->info("merchantsByName size: " . number_format(strlen(json_encode($this->merchantsByName))));
            $this->logger->notice("mem: " . number_format(memory_get_usage(true) / 1024));
        }

        if ($dir = $input->getOption('save-merchants')) {
            $this->saveMapToFile($dir . '/byPattern.jsonl', $this->merchantsByPattern);
            $this->saveMapToFile($dir . '/byName.jsonl', $this->merchantsByName);
        }

        if (!$input->getOption('dry-run')) {
            $this->connection->executeStatement("truncate MerchantTEST");
            $this->saveMerchantsToDatabase($this->merchantsByPattern);
            $this->merchantsByPattern = [];
            $this->saveMerchantsToDatabase($this->merchantsByName);
            $this->merchantsByName = [];
        }
    }

    private function scanHistory(InputInterface $input): void
    {
        $saveToFile = $input->getOption('save-to-file');

        if ($saveToFile) {
            $this->logger->info("saving to file $saveToFile");
            $saveFileHandle = fopen($saveToFile, "wb");
        }

        $loadFromFile = $input->getOption('load-from-file');

        if ($loadFromFile) {
            $source = $this->openFile($loadFromFile);
        } else {
            $source = $this->openDatabase($input->getOption('where'));
        }

        $processed = 0;
        $progressLogger = new ProgressLogger($this->logger, 500, self::DUMP_STATUS_TIME);
        $limit = (int) $input->getOption('limit');
        $startTime = $this->clock->monothonic();
        $startMem = memory_get_usage(true);

        foreach ($source as $row) {
            if (($processed % 1000) === 0) {
                $progressLogger->showProgress("merchants by Pattern: " . number_format(count($this->merchantsByPattern)) . ", by Name: " . number_format(count($this->merchantsByName)),
                    $processed);
            }

            $this->processRow($row);

            if ($saveToFile) {
                fputcsv($saveFileHandle, $row);
            }

            if ($processed === 0) {
                $startMem = memory_get_usage(true);
                $this->logger->info("memory on first iteration: " . number_format($startMem));
            }

            $processed++;

            if ($limit > 0 && $processed === $limit) {
                $this->logger->info("limit $limit hit");

                break;
            }
        }

        $this->logger->notice("$processed rows processed, we got " . count($this->merchantsByPattern) . " pattern merchants, and " . count($this->merchantsByName) . " name merchants, time: " . $this->clock->monothonic()->sub($startTime)->getAsSecondsInt() . ", mem: " . number_format((memory_get_usage(true) - $startMem) / 1024));

        if ($saveToFile) {
            fclose($saveFileHandle);
            $this->logger->info("saved to file $saveToFile");
        }
    }

    private function processRow(array $row): void
    {
        $amount = (float) $row["Amount"];
        $providerId = (int) $row["ProviderID"];
        $merchantName = $row["Description"] === null ? null : $this->merchantNameNormalizer->normalize($row["Description"]);
        $categoryId = !empty($row["Category"]) ? $this->categoryMatcher->identify($row["Category"], $providerId) : null;
        $groupId = $this->groupByCategoryId[$categoryId] ?? null;
        $nameIndex = $merchantName . '_' . $groupId;
        $merchantWithPattern = null;
        $postingDate = strtotime($row['PostingDate']);

        if (!isset($this->merchantsByName[$nameIndex])) {
            $merchantWithPattern = $merchantName !== null ? $this->patternMatcher->identify($merchantName) : null;
        }

        $multiplier = MultiplierService::calculate($amount, (float) $row["Miles"], $providerId);

        if ($merchantWithPattern !== null) {
            $this->saveMerchantMatch($this->merchantsByPattern, $merchantWithPattern['MerchantID'] . '_' . $groupId, $merchantWithPattern['MerchantID'], $merchantWithPattern['Name'], $merchantWithPattern['DisplayName'], $row["Description"], $providerId, $row['CreditCardID'], $categoryId, $multiplier, $postingDate);

            return;
        }

        if ($merchantName !== null) {
            $this->saveMerchantMatch($this->merchantsByName, $nameIndex, null, null, null, $row["Description"], $providerId, $row['CreditCardID'], $categoryId, $multiplier, $postingDate);
        }
    }

    private function saveMerchantMatch(
        array &$merchants,
        $key,
        ?int $merchantId,
        ?string $name,
        ?string $displayName,
        string $description,
        int $providerId,
        ?int $creditCardId,
        ?int $categoryId,
        ?float $multiplier,
        int $postingDate
    ) {
        if (!array_key_exists($key, $merchants)) {
            $merchants[$key] = new MerchantScanInfo($merchantId, $name, $displayName, $postingDate);
        }

        $merchant = &$merchants[$key];

        $this->incrementMap($merchant->providers, $providerId);
        $this->incrementMap($merchant->categories, (int) $categoryId);
        $this->incrementMap($merchant->multipliers, round($multiplier * 10));
        $this->incrementMap($merchant->creditCards, ($creditCardId ? $this->creditCardToIndex[$creditCardId] : 0) + round($multiplier * 10) * 256);
        $merchant->transactions++;

        if ($displayName === null) {
            if ($merchant->names === null) {
                $merchant->names = new \SplDoublyLinkedList();
            }

            $this->addName($merchant->names, $description);
        }

        if ($postingDate > $merchant->lastSeenDate) {
            $merchant->lastSeenDate = $postingDate;
        }
    }

    private function incrementMap(string &$map, int $key): void
    {
        if ($key > 65535) {
            throw new \Exception("key is too big: $key");
        }

        for ($index = strlen($map) - 4; $index >= 0; $index -= 4) {
            $aKey = ord($map[$index]) * 256 + ord($map[$index + 1]);

            if ($aKey === $key) {
                $l = ord($map[$index + 3]);

                if ($l < 255) {
                    $map[$index + 3] = chr($l + 1);

                    return;
                }

                $h = ord($map[$index + 2]);

                if ($h === 255) {
                    return;
                }

                $map[$index + 3] = 0;
                $map[$index + 2] = chr($h + 1);

                return;
            }
        }

        $map .= chr(intdiv($key, 256)) . chr($key & 0xFF) . chr(0) . chr(1);
    }

    private function unpackMap(string $map): array
    {
        $result = [];

        for ($index = strlen($map) - 4; $index >= 0; $index -= 4) {
            $result[ord($map[$index]) * 256 + ord($map[$index + 1])] = ord($map[$index + 2]) * 256 + ord($map[$index + 3]);
        }

        return $result;
    }

    private function addName(\SplDoublyLinkedList &$names, string $description): void
    {
        $description = strtolower($description);
        $description = trim(preg_replace('/\#\d{2}\d+/', '', $description));

        foreach ($names as $nameInfo) {
            /** @var NameInfo $nameInfo */
            if ($nameInfo->name === $description) {
                $nameInfo->count++;

                return;
            }
        }

        $names->push(new NameInfo(strtolower($description)));

        if ($names->count() >= 20) {
            $names = $this->packNames($names);
        }
        //        $description = strtolower("\t{$description}\t");
        //        $p = strpos($names, $description);
        //
        //        if ($p === false) {
        //            $names .= chr(0) . chr(1) . $description;
        //            return;
        //        }
        //
        //        $l = ord($names[$p - 1]);
        //        if ($l < 255) {
        //            $names[$p - 1] = chr($l + 1);
        //            return;
        //        }
        //
        //        $h = ord($names[$p - 2]);
        //        if ($h === 255) {
        //            return;
        //        }
        //
        //        $names[$p - 1] = chr(0);
        //        $names[$p - 2] = chr($h + 1);
        //
        //        if (strlen($names) >= 512) {
        //            $names = $this->packNames($names);
        //            asort($names);
        //            array_splice($names, 0, 10);
        //        }
    }

    private function packNames(\SplDoublyLinkedList $names): \SplDoublyLinkedList
    {
        $arr = $this->nameListToArray($names);

        $result = new \SplDoublyLinkedList();
        $count = 0;

        foreach ($arr as $name => $matches) {
            $result->push(new NameInfo($name, $matches));
            $count++;

            if ($count === 10) {
                break;
            }
        }

        return $result;
    }

    private function nameListToArray(\SplDoublyLinkedList $names): array
    {
        $arr = [];

        foreach ($names as $nameInfo) {
            /** @var NameInfo $nameInfo */
            $arr[$nameInfo->name] = $nameInfo->count;
        }

        arsort($arr);

        return $arr;
    }

    private function saveMerchantsToDatabase(array $merchants): void
    {
        $batchUpdater = new BatchUpdater($this->connection);
        $this->logger->info("saving merchants");

        it($merchants)
            ->onNthMillis(15000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey) {
                $this->logger->info("saved $iteration merchants, mem: " . round(memory_get_usage(true) / 1024 / 1024) . " Mb");
            })
            ->mapIndexed(function (MerchantScanInfo $merchant, string $key) {
                $p = strrpos($key, '_');

                if ($merchant->name === null) {
                    $name = substr($key, 0, $p);
                } else {
                    $name = $merchant->name;
                }

                $groupId = substr($key, $p + 1);

                if ($groupId === '') {
                    $groupId = null;
                }

                return
                    [
                        "MerchantID" => $merchant->merchantId,
                        "Providers" => json_encode($this->unpackMap($merchant->providers)),
                        "CreditCards" => json_encode($this->unpackCreditCards($this->unpackMap($merchant->creditCards))),
                        "Categories" => json_encode($this->unpackMap($merchant->categories)),
                        "Multipliers" => json_encode(
                            it($this->unpackMap($merchant->multipliers))
                                ->mapKeys(fn (int $multiplier) => sprintf("%0.1f", $multiplier / 10))
                                ->toArrayWithKeys()
                        ),
                        "Name" => $name,
                        "DisplayName" => $merchant->displayName ?? $this->calcDisplayName($merchant->names),
                        "ShoppingCategoryGroupID" => $groupId,
                        "Transactions" => $merchant->transactions,
                        "FirstSeenDate" => date("Y-m-d", $merchant->firstSeenDate),
                        "LastSeenDate" => date("Y-m-d", $merchant->lastSeenDate),
                    ];
            })
            ->chunk(self::PACKAGE_SIZE)
            ->apply(function (array $chunk) use ($batchUpdater) {
                $batchUpdater->batchUpdate($chunk, "insert into MerchantTEST(MerchantID, Name, DisplayName, ShoppingCategoryGroupID, Providers, CreditCards, Categories, Multipliers, Transactions, FirstSeenDate, LastSeenDate)
                values (:MerchantID, :Name, :DisplayName, :ShoppingCategoryGroupID, :Providers, :CreditCards, :Categories, :Multipliers, :Transactions, :FirstSeenDate, :LastSeenDate)", 0);
            })
        ;

        $this->logger->info("saved");
    }

    private function calcDisplayName(\SplDoublyLinkedList $names): string
    {
        $arr = $this->nameListToArray($names);
        $name = array_keys($arr)[0];

        return MerchantDisplayNameGenerator::create($name);
    }

    private function openDatabase(?string $where): \Generator
    {
        $sql = 'SELECT 
            h.UUID, h.Description, h.Miles, h.Amount, h.PostingDate, 
            h.Category, h.ShoppingCategoryID, h.MerchantID, h.Multiplier,
            a.ProviderID, sa.CreditCardID
        FROM 
            AccountHistory h
            JOIN Account a ON h.AccountID = a.AccountID
            JOIN SubAccount sa ON h.SubAccountID = sa.SubAccountID
        WHERE 
            h.Amount <> 0
            AND h.Amount IS NOT NULL
            AND h.SubAccountID IS NOT NULL';

        if ($where) {
            $sql .= " and $where";
        }

        $this->logger->info("opening query: $sql");
        $result = $this->unbufConnection->executeQuery($sql);
        $this->logger->info("query open");

        while ($row = $result->fetchAssociative()) {
            yield $row;
        }
    }

    private function openFile(string $fileName): \Generator
    {
        $this->logger->info("loading from file $fileName");
        $f = fopen($fileName, "rb");

        while ($row = fgetcsv($f)) {
            if (count($row) !== 10) {
                $this->logger->info("invalid row: " . json_encode($row));

                continue;
            }

            yield array_combine(["UUID", "Description", "Miles", "Amount", "PostingDate", "Category", "ShoppingCategoryID", "MerchantID", "Multiplier", "ProviderID"], $row);
        }
        fclose($f);
    }

    private function unpackCreditCards(array $ccAndMultiplierToCount): array
    {
        $result = [];

        foreach ($ccAndMultiplierToCount as $ccAndMultiplier => $count) {
            $multiplier = intdiv($ccAndMultiplier, 256);
            $cc = $ccAndMultiplier - $multiplier * 256;
            $multiplier = sprintf("%0.1f", $multiplier / 10);

            if ($cc > 0) {
                $cc = $this->creditCardFromIndex[$cc];
            }

            if (!isset($result[$cc])) {
                $result[$cc] = [];
            }

            $result[$cc][$multiplier] = $count;
        }

        return $result;
    }

    private function saveMapToFile(string $fileName, array $map): void
    {
        $this->logger->info("saving map to $fileName, map size: " . count($map));
        $f = fopen($fileName, "wb");
        gc_collect_cycles();

        $total = 0;
        it($map)
            ->onNthMillis(1000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey) {
                $this->logger->info("saving row $iteration, key: $currentKey, mem: " . round(memory_get_usage(true) / 1024 / 1024));
            })
            ->onNthMillis(20000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey) {
                $this->logger->info("garbage collection, mem: " . round(memory_get_usage(true) / 1024 / 1024));
                gc_collect_cycles();
            })
            ->applyIndexed(function ($value, $key) use (&$total, $f) {
                fwrite($f, json_encode(["k" => $key, "v" => base64_encode(serialize($value))]) . "\n");
                $total++;
            });

        fclose($f);
        $this->logger->info("saved, total $total");
    }

    private function loadMap(string $fileName): array
    {
        $this->logger->info("loading $fileName");
        $f = fopen($fileName, "rb");
        $result = [];

        while ($s = fgets($f)) {
            $json = json_decode($s, true);
            $result[$json["k"]] = unserialize(base64_decode($json["v"]), ['allowed_classes' => [MerchantScanInfo::class, \SplDoublyLinkedList::class, NameInfo::class]]);
        }

        fclose($f);
        $this->logger->info("loaded " . count($result) . " merchants");

        return $result;
    }
}
