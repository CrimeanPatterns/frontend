<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class Merchant extends \TBaseSchema
{
    private Process $process;

    private LoggerInterface $logger;

    private Connection $connection;

    public function __construct(Process $process, LoggerInterface $logger, Connection $connection)
    {
        parent::__construct();

        $shoppingCategories = SQLToArray(
            "select ShoppingCategoryID, Name from ShoppingCategory order by Name",
            "ShoppingCategoryID",
            "Name"
        );

        $forcedCategories = ["" => "No Forced Category"];

        foreach ($shoppingCategories as $key => $value) {
            $forcedCategories[$key] = $value;
        }

        $this->Fields = [
            "Name" => [
                "Type" => "string",
                "Size" => 255,
                "Required" => true,
                "FilterField" => "m.Name",
            ],
            "DisplayName" => [
                'Caption' => 'Display Name<br/>(Fulltext search)',
                "Type" => "string",
                "Size" => 255,
            ],
            "ClickURL" => [
                "Type" => "string",
                "Size" => 255,
                "Caption" => "ClickURL",
            ],
            "Patterns" => [
                "Caption" => "Patterns",
                "Type" => "string",
                "InputType" => "textarea",
                'FilterField' => 'mp.Patterns',
                "Required" => false,
            ],
            "DetectPriority" => [
                "Type" => "integer",
            ],
            "Similar" => [
                "Caption" => "Similar Counter",
                "Type" => "integer",
            ],
            "Transactions" => [
                "Caption" => "Transactions",
                "Type" => "integer",
            ],
            "TransactionsLast3Months" => [
                "Caption" => "Recent Transactions",
                "Type" => "integer",
            ],
            'TransactionsConfidenceInterval' => [
                "Caption" => "Transactions CI",
                "Type" => "integer",
            ],
            "IgnorePatternsGrouping" => [
                "Caption" => "Ingore grouping by category",
                "Type" => "boolean",
            ],
            "ShoppingCategoryID" => [
                "Type" => "integer",
                "Caption" => "Shopping Category",
                "FilterField" => "m.ShoppingCategoryID",
                "Options" => $shoppingCategories,
                "Required" => false,
            ],
            "ForcedShoppingCategoryID" => [
                "Type" => "integer",
                "Caption" => "Forced Category",
                "FilterField" => "m.ForcedShoppingCategoryID",
                "Options" => $forcedCategories,
                "Required" => false,
            ],
            "MerchantPatternID" => [
                "Type" => "integer",
                "Caption" => "Merchant Pattern",
                "FilterField" => "m.MerchantPatternID",
                "Required" => false,
            ],
        ];

        $this->process = $process;
        $this->logger = $logger;
        $this->connection = $connection;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();
        unset($result['ClickURL']);

        ArrayInsert($result, "ShoppingCategoryID", true, [
            "ShoppingCategoryGroupID" => [
                "Type" => "integer",
                "Caption" => "Group",
                "Options" => $this->connection->executeQuery("select ShoppingCategoryGroupID, Name from ShoppingCategoryGroup order by Name")->fetchAllKeyValue(),
                "Required" => false,
                'FilterField' => "m.ShoppingCategoryGroupID",
            ],
        ]);

        return $result;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->ReadOnly = true;
        $list->DefaultSort = "Transactions";
        $list->SQL = "
            select 
                m.DetectPriority,
                m.DisplayName,
                m.ForcedShoppingCategoryID,
                m.IgnorePatternsGrouping,
                m.IsCustomDisplayName,
                m.MerchantID, 
                m.MerchantPatternID , 
                m.Name, 
                m.NotNullGroupID,
                m.ShoppingCategoryGroupID,
                m.ShoppingCategoryID,
                m.Similar,
                m.Stat,
                m.Transactions,
                m.TransactionsLast3Months,
                m.TransactionsConfidenceInterval,
                m.ConfidenceIntervalStartDate,
                mp.ClickUrl,
                mp.Patterns,
                sc.ShoppingCategoryGroupID
            from Merchant m use index (`PRIMARY`, idxTransactions, fkMerchantPattern, fkShoppingCategoryGroupID, idxDisplayName, idxTransactionsLast3Months)
            join ShoppingCategory sc on m.ShoppingCategoryID = sc.ShoppingCategoryID
            left join MerchantPattern mp on m.MerchantPatternID = mp.MerchantPatternID";
    }

    public function CreateForm()
    {
        return null;
    }

    public function GetFormFields()
    {
        return [];
    }

    public function ShowForm()
    {
    }

    private function writeMerchantReport($merchantId)
    {
        /** @var Doctrine\DBAL\Connection $connection */
        $connection = getSymfonyContainer()->get("database_connection");
        $paramRepository = getSymfonyContainer()->get(\AwardWallet\MainBundle\Entity\Repositories\ParameterRepository::class);
        $currentVersion = $paramRepository->getParam(\AwardWallet\MainBundle\Entity\Repositories\ParameterRepository::MERCHANT_REPORT_VERSION);

        $sql = <<<SQL
            SELECT mr.*, cc.Name as CardName, sc.Name as CategoryName, scg.Name as GroupName, p.ShortName 
            FROM MerchantReport mr 
            LEFT JOIN ShoppingCategory sc ON mr.ShoppingCategoryID = sc.ShoppingCategoryID
            LEFT JOIN ShoppingCategoryGroup scg ON sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
            JOIN CreditCard cc ON mr.CreditCardID = cc.CreditCardID
            JOIN Provider p ON cc.ProviderID = p.ProviderID 
            WHERE mr.Version = {$currentVersion}
            AND mr.MerchantID = ?
            ORDER BY p.ShortName, mr.Transactions DESC
SQL;

        $rows = $connection->executeQuery($sql, [$merchantId], [\PDO::PARAM_INT])->fetchAll();

        if (empty($rows)) {
            return;
        }

        $htmlList = "<tr><th>Provider</th><th>Credit Card</th><th>Category</th><th>Category Group</th><th>Expected Multiplier Transactions</th><th></th></tr>";
        $router = getSymfonyContainer()->get("router");
        $route = $router->generate("aw_manager_transaction_list");

        foreach ($rows as $row) {
            $transactionsLink = sprintf(
                "$route?ShoppingCategoryID=%s&CreditCardID=%s&MerchantID=%s",
                $row["ShoppingCategoryID"],
                $row["CreditCardID"],
                $row["MerchantID"]
            );

            $htmlList .=
                "<tr>
                    <td>" . $row["ShortName"] . "</td>
                    <td>" . $row["CardName"] . "</td>
                    <td>" . $row["CategoryName"] . "</td>
                    <td>" . $row["GroupName"] . "</td>
                    <td>" . $row["ExpectedMultiplierTransactions"] . "</td>
                    <td><a target='_blank' href='{$transactionsLink}'>transactions (" . $row["Transactions"] . ")</a></td>
                </tr>";
        }
        echo "<div style='text-align: left;'><h2>Merchant pairs (credit card - shopping category):</h2><table cellpadding='3'>$htmlList</table></div>";
    }
}
