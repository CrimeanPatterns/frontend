<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Service\MySQLFullTextSearchUtils;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\RouterInterface;

class MerchantList extends \TBaseList
{
    private Process $process;
    private RouterInterface $router;
    private array $groupsByCategory;
    private \DateTimeImmutable $merchantUpperDate;
    private Connection $connection;
    private Connection $sphinxConnection;

    public function __construct(
        string $table,
        array $fields,
        Process $process,
        RouterInterface $router,
        Connection $connection,
        Connection $sphinxConnection
    ) {
        parent::__construct($table, $fields);

        $this->process = $process;
        $this->router = $router;
        $this->merchantUpperDate = new \DateTimeImmutable($connection
            ->executeQuery(
                'select Val from Param where Name = ?',
                [ParameterRepository::MERCHANT_UPPER_DATE],
            )
            ->fetchOne() ?: 'now');
        $this->groupsByCategory = $connection->executeQuery("
        select 
            sc.ShoppingCategoryID,
            scg.Name 
        from 
            ShoppingCategory sc
            join ShoppingCategoryGroup scg on sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
        ")->fetchAllKeyValue();
        $this->connection = $connection;
        $this->sphinxConnection = $sphinxConnection;
    }

    public function GetFieldFilter($sField, $arField)
    {
        if ('DisplayName' === $sField) {
            $pattern = MySQLFullTextSearchUtils::createTokens(MySQLFullTextSearchUtils::filterQueryForFullTextSearch($arField["Value"]))
                ->usort(fn (string $a, string $b) => \strlen($b) <=> \strlen($a))
                ->map(fn (string $part) => "{$part}*")
                ->joinToString(' ');

            $merchantIds = $this->sphinxConnection->executeQuery("select id from Merchant where match(:fullTextQuery) limit 1000", ["fullTextQuery" => $pattern])->fetchFirstColumn();

            if (count($merchantIds) === 0) {
                return " and 0 = 1";
            }

            return " and MerchantID in (" . implode(", ", $merchantIds) . ")";
        }

        return parent::GetFieldFilter($sField, $arField);
    }

    public function FormatFields($output = "html")
    {
        $ciStartDate = $this->Query->Fields['ConfidenceIntervalStartDate'];
        unset($this->Query->Fields['ConfidenceIntervalStartDate']);

        parent::FormatFields($output);

        if ($output !== "html") {
            return;
        }

        $transactionsUrl = $this->router->generate("aw_manager_transaction_list", ["MerchantID" => $this->OriginalFields['MerchantID']]);
        $this->Query->Fields["Transactions"] = "<a href=\"{$transactionsUrl}\" target=\"_blank\">{$this->Query->Fields["Transactions"]}</a>";
        $transactionsL3MUrl = $this->router->generate("aw_manager_transaction_list", [
            "MerchantID" => $this->OriginalFields['MerchantID'],
            'EndDate' => ($nowDate = new \DateTimeImmutable('now'))->format('m/d/Y'),
            'StartDate' => $nowDate->modify('-3 months')->format('m/d/Y'),
        ]);
        $ciStartDateTime = new \DateTimeImmutable($ciStartDate);
        $this->Query->Fields["TransactionsLast3Months"] = "<span title='last 3 months'><a href=\"{$transactionsL3MUrl}\" target=\"_blank\">{$this->Query->Fields["TransactionsLast3Months"]}</a></span>";
        $transactionsCIUrl = $this->router->generate("aw_manager_transaction_list", [
            "MerchantID" => $this->OriginalFields['MerchantID'],
            'EndDate' => $nowDate->format('m/d/Y'),
            'StartDate' => $ciStartDateTime->format('m/d/Y'),
        ]);
        $ciWeekLength = (int) ceil($this->merchantUpperDate->diff($ciStartDateTime)->days / 7);
        $this->Query->Fields["TransactionsConfidenceInterval"] =
            isset($ciStartDate) ?
                "<span title='{$ciWeekLength} week(s), since {$ciStartDate}'><a href=\"{$transactionsCIUrl}\" target=\"_blank\">{$this->Query->Fields["TransactionsConfidenceInterval"]}</a></span>" :
                $this->Query->Fields["TransactionsConfidenceInterval"];
        $patternIdUrl = "/manager/edit.php?ID={$this->Query->Fields["MerchantPatternID"]}&Schema=MerchantPattern";
        $this->Query->Fields["Patterns"] = "<a href=\"{$patternIdUrl}\" target=\"_blank\">{$this->Query->Fields["Patterns"]}</a>";
        $this->Query->Fields["MerchantPatternID"] = "<a href=\"{$patternIdUrl}\" target=\"_blank\">{$this->Query->Fields["MerchantPatternID"]}</a>";
    }
}
