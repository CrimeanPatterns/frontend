<?php

namespace Codeception\Module;

use AwardWallet\MainBundle\Service\ClickhouseFactory;
use Codeception\Module;
use Doctrine\DBAL\Connection;

class Clickhouse extends Module
{
    private Connection $connection;

    public function _initialize()
    {
        parent::_initialize();
        /** @var Symfony $symfony2 */
        $symfony2 = $this->getModule('Symfony');
        /** @var ClickhouseFactory $factory */
        $factory = $symfony2->grabService(ClickhouseFactory::class);
        $this->connection = $factory->getConnection();
    }

    public function copyToClickhouse(string $table, string $where)
    {
        /** @var Module\CustomDb $db */
        $db = $this->getModule("CustomDb");
        $columns = $this->connection->executeQuery("describe $table")->fetchFirstColumn();
        $sql = "select " . implode(", ", $columns) . " from $table where $where";

        if ($table === "AccountHistory") {
            unset($columns[array_search("CreditCardID", $columns)]);
            $sql = "select " . implode(", ", array_map(fn (string $column) => "AccountHistory.$column", $columns)) . ", SubAccount.CreditCardID from $table 
            join SubAccount on AccountHistory.SubAccountID = SubAccount.SubAccountID where $where";
        }

        $q = $db->query($sql);

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            $this->connection->insert($table, $row);
        }
    }
}
