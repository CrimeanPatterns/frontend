<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtColumn;

class BuildCardMatcherReportCommand extends Command
{
    public const SOURCE_SUB_ACCOUNT = 'SA';
    public const SOURCE_DETECTED_CARD = 'DC';
    public const SOURCE_NAMES = [
        self::SOURCE_SUB_ACCOUNT => 'SubAccount',
        self::SOURCE_DETECTED_CARD => 'Detected Card',
    ];

    private const HISTORY_DAYS = 365;

    public static $defaultName = 'aw:build-card-matcher-report';

    /** @var Connection */
    private $connection;
    /** @var Connection */
    private $replicaConnection;
    /** @var LoggerInterface */
    private $logger;
    /** @var CreditCardMatcher */
    private $creditCardMatcher;

    public function __construct(LoggerInterface $logger, Connection $connection, Connection $replicaUnbufferedConnection, CreditCardMatcher $creditCardMatcher)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->connection = $connection;
        $this->replicaConnection = $replicaUnbufferedConnection;
        $this->creditCardMatcher = $creditCardMatcher;
    }

    protected function configure()
    {
        $this
            ->addOption('detected-cards', null, InputOption::VALUE_NONE)
            ->addOption('accountIds', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'limit to this array of accountIds')
            ->addOption('sub-accounts', null, InputOption::VALUE_NONE)
            ->addOption('show', null, InputOption::VALUE_NONE)
            ->addOption('save', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $it = it([]);

        if ($input->getOption('detected-cards')) {
            $it = $it->chain($this->loadDetectedCards($output, $input->getOption('accountIds')));
        }

        if ($input->getOption('sub-accounts')) {
            $it = $it->chain($this->loadSubAccounts($output, $input->getOption('accountIds')));
        }

        $cards = $this->processCardNames($it);
        $output->writeln("got " . count($cards) . " cards");

        if ($input->getOption('show')) {
            $this->show($cards, $output);
        }

        if ($input->getOption('save')) {
            $this->save($cards, $output);
        }

        return 0;
    }

    private function loadDetectedCards(OutputInterface $output, array $accountIds): iterable
    {
        $sql = "
            select ap.Val, a.AccountID, a.ProviderID, a.UserID from AccountProperty ap
            join ProviderProperty pp on pp.ProviderPropertyID = ap.ProviderPropertyID 
            join Account a on ap.AccountID = a.AccountID
            join Provider p on a.ProviderID = p.ProviderID
            where a.ProviderID IN (" . implode(", ", Provider::EARNING_POTENTIAL_LIST) . ") 
            AND a.SuccessCheckDate >= adddate(now(), -" . self::HISTORY_DAYS . ")
            and pp.Code = 'DetectedCards'
        ";

        if (count($accountIds) > 0) {
            $sql .= " and a.AccountID in (" . implode(", ", $accountIds) . ")";
        }

        $output->writeln("processing detected cards");

        yield from it($this->replicaConnection->executeQuery($sql))
            ->onNthMillis(15000, fn (int $millisFromStart, int $iteration, $currentValue, $currentKey) => $output->writeln("processed $iteration records.."))
            ->flatMap(function (array $row) {
                $cards = @unserialize($row['Val'], ['allowed_classes' => false]);

                if (!is_array($cards)) {
                    return [];
                }

                return it($cards)
                    ->filter(fn (array $card) => isset($card['DisplayName']))
                    ->map(fn (array $card) => [
                        "DisplayName" => $card["DisplayName"],
                        "AccountID" => $row["AccountID"],
                        "UserID" => $row["UserID"],
                        "ProviderID" => $row["ProviderID"],
                        "Source" => self::SOURCE_DETECTED_CARD,
                    ])
                    ->toArray()
                ;
            })
        ;
    }

    private function loadSubAccounts(OutputInterface $output, array $accountIds): iterable
    {
        $sql = "
            SELECT 
               a.ProviderID, s.AccountID, s.DisplayName, a.UserID
            FROM 
                 SubAccount s 
                 JOIN Account a ON s.AccountID = a.AccountID
            WHERE 
                a.ProviderID IN (" . implode(", ", Provider::EARNING_POTENTIAL_LIST) . ") 
                AND a.SuccessCheckDate >= adddate(now(), -" . self::HISTORY_DAYS . ")
                AND s.DisplayName NOT LIKE '%FICO%'
                AND s.DisplayName NOT LIKE '%$%'
                AND s.DisplayName NOT REGEXP '/^Membership\s+Rewards\s+\(1M\d{8}\)$/'
                AND EXISTS(select 1 from AccountHistory where AccountHistory.SubAccountID = s.SubAccountID)
        ";

        if (count($accountIds) > 0) {
            $sql .= " and a.AccountID in (" . implode(", ", $accountIds) . ")";
        }

        $output->writeln("processing subaccounts..");

        return yield from it($this->replicaConnection->executeQuery($sql))
            ->map(fn ($row) => array_merge($row, ['Source' => self::SOURCE_SUB_ACCOUNT]))
            ->lazy()
        ;
    }

    private function processCardNames(IteratorFluent $it): array
    {
        return $it
            ->map(fn (array $card) => array_merge($card, [
                "CreditCardID" => $this->creditCardMatcher->identify($card['DisplayName'], (int) $card['ProviderID']),
                "Name" => preg_replace('/^([^\d]+)(\d{4,5})([^\d]*)/', '${1}XXXX${3}', $card['DisplayName']),
            ]))
            ->reindex(fn (array $row) => $row['ProviderID'] . '-' . $row['Name'])
            ->collapseByKey()
            ->map(function (array $rows) {
                $undetected = it($rows)
                    ->filter(fn (array $row) => $row['CreditCardID'] === null)
                    ->count();

                $creditCards = it($rows)
                    ->filter(fn (array $row) => $row['CreditCardID'] !== null)
                    ->reindexByColumn('CreditCardID')
                    ->collapseByKey()
                    ->map(fn (array $rows) => count($rows))
                    ->mapKeys(fn (string $key) => (int) $key)
                    ->toArrayWithKeys();

                return [
                    "ProviderID" => $rows[0]['ProviderID'],
                    "Name" => $rows[0]['Name'],
                    "ParseCount" => count($rows),
                    "Undetected" => $undetected,
                    "MatchCount" => count($rows) - $undetected,
                    "MatchedCreditCards" => $creditCards,
                    "Rows" => it($rows)->map(fn (array $row) => array_diff_key($row, ["ProviderID" => false, "Name" => false]))->toArray(),
                ];
            })
            ->uasort(fn (array $a, array $b) => $a['ParseCount'] <=> $b['ParseCount'])
            ->toArrayWithKeys()
        ;
    }

    private function show(array $rows, OutputInterface $output): void
    {
        if (count($rows) > 0) {
            $rows = it($rows)
                ->map(fn (array $row) => array_diff_key($row, ["Rows" => false]))
                ->map(fn (array $row) => array_merge($row, ["MatchedCreditCards" => json_encode($row['MatchedCreditCards'])]))
                ->toArray();

            $table = new Table($output);
            $table->setHeaders(array_keys($rows[0]));
            $table->setRows($rows);
            $table->render();
        }
    }

    private function save(array $cards, OutputInterface $output): void
    {
        $output->writeln("saving " . count($cards) . " records to CardMatcherReport table");

        $q = $this->connection->prepare("insert into CardMatcherReport(ProviderID, Name, ParseCount, Undetected, MatchCount, MatchedCreditCards, `Rows`)
        values (:ProviderID, :Name, :ParseCount, :Undetected, :MatchCount, :MatchedCreditCards, :Rows)
        on duplicate key update ParseCount = :ParseCount, Undetected = :Undetected, MatchCount = :MatchCount, MatchedCreditCards = :MatchedCreditCards, `Rows` = :Rows");

        it($cards)
            ->map(fn (array $row) => array_merge($row, [
                "Rows" => json_encode($row["Rows"]),
                "MatchedCreditCards" => json_encode($row["MatchedCreditCards"]),
            ]))
            ->apply([$q, "execute"])
        ;

        $toDelete = stmtColumn($this->connection->executeQuery("select concat(ProviderID, '-', Name) from CardMatcherReport"))
            ->filter(fn (string $key) => !array_key_exists($key, $cards))
            ->toArray()
        ;

        //        if (count($cards) > 0 && (count($toDelete) / count($cards)) > 0.7) {
        //            throw new \Exception("too much cards to delete. total: " . count($cards) . ", to delete: " . count($toDelete));
        //        }

        $output->writeln("deleting " . count($toDelete) . " outdated records");

        $q = $this->connection->prepare("delete from CardMatcherReport where ProviderID = :ProviderID and Name = :Name limit 1");

        it($toDelete)
            ->map(function (string $key) {
                $index = strpos($key, '-');

                return ['ProviderID' => substr($key, 0, $index), 'Name' => substr($key, $index + 1)];
            })
            ->apply([$q, "execute"])
        ;

        $output->writeln("saved");
    }
}
