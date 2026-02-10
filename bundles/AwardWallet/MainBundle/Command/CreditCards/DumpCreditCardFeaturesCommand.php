<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCreditCardFeaturesCommand extends Command
{
    public static $defaultName = 'aw:dump-credit-card-features';
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function configure()
    {
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'analyze only cards opened in N last days', 30)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) $input->getOption('days');
        $this->logger->info("searching cards opened in last {$days}");
        $cards = $this->connection->executeQuery("select * from UserCreditCard where EarliestSeenDate > subdate(now(), :days)", ["days" => $days])->fetchAll(FetchMode::ASSOCIATIVE);
        $users = array_unique(array_column($cards, 'UserID'));
        $this->logger->info("loaded " . count($cards) . " cards of " . count($users) . " users");
        $features = array_map([$this, "getCardFeatures"], $cards);
        $this->displayFeatures($cards, $features, $output);

        return 0;
    }

    private function getCardFeatures(array $card): array
    {
        $result = [];
        $result += $this->getCardRowFeatures($card);
        $result += $this->getPreviousCardsFeatures($card);
        $result += $this->getAccountHistoryFeatures($card['UserID'], $card['EarliestSeenDate']);

        // $this->logger->info("card {$card['UserCreditCardID']}: " . json_encode($result));
        return $result;
    }

    private function getCardRowFeatures(array $card): array
    {
        return [
            "age" => $this->calcAge($card['EarliestSeenDate'], $card['LastSeenDate']),
        ];
    }

    private function displayFeatures(array $cards, array $features, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(array_merge(["ID"], array_keys($features[0])));
        $index = -1;
        $rows = array_map(function (array $featuresRow) use ($cards, &$index) {
            $index++;

            return ['ID' => $cards[$index]['UserCreditCardID']] + $featuresRow;
        }, $features);
        $table->setRows($rows);
        $table->render();
    }

    private function getPreviousCardsFeatures(array $card): array
    {
        static $q;

        if ($q === null) {
            $q = $this->connection->prepare("select * from UserCreditCard  
            where EarliestSeenDate < :date and UserID = :userId 
            order by EarliestSeenDate");
        }

        $q->execute(["date" => $card['EarliestSeenDate'], 'userId' => $card['UserID']]);
        $result = [];

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $result["{$row['CreditCardID']}.fs"] = $this->calcAge($row['EarliestSeenDate'], $card['EarliestSeenDate']);
            $result["{$row['CreditCardID']}.ls"] = max(0, $this->calcAge($row['LastSeenDate'], $card['EarliestSeenDate']));
            $result["{$row['CreditCardID']}.c"] = $row['IsClosed'];
        }

        return $result;
    }

    private function calcAge($fromDateStr, $toDateStr): int
    {
        return round((strtotime($toDateStr) - strtotime($fromDateStr)) / 86400);
    }

    private function getAccountHistoryFeatures(int $userId, string $baseDateStr): array
    {
        static $q;

        if ($q === null) {
            $q = $this->connection->prepare(
                "
                select
                    a.ProviderID,
                    sum(case when ah.Miles > 0 then ah.Miles else 0 end) as AddMilesTotal,
                    sum(case when ah.Miles < 0 then ah.Miles else 0 end) as SubMilesTotal,
                    sum(case when ah.Miles > 0 then 1 else 0 end) as AddMilesCount, 
                    sum(case when ah.Miles < 0 then 1 else 0 end) as SubMilesCount
                from
                    AccountHistory ah
                    join Account a on ah.AccountID = a.AccountID
                where
                    a.UserID = :userId
                    and ah.PostingDate < :date      
                    and ah.PostingDate > adddate(:date, interval -1 year)      
                group by    
                    a.ProviderID
                "
            );
        }

        $q->execute(["date" => $baseDateStr, "userId" => $userId]);
        $result = [];

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $result["{$row['ProviderID']}.amt"] = round($row['AddMilesTotal']);
            $result["{$row['ProviderID']}.amc"] = $row['AddMilesCount'];
            $result["{$row['ProviderID']}.smt"] = round($row['SubMilesTotal']);
            $result["{$row['ProviderID']}.smc"] = $row['SubMilesCount'];
        }

        return $result;
    }
}
