<?php

namespace AwardWallet\MainBundle\Service\CreditCards\BackupProcessors;

use AwardWallet\MainBundle\Service\Backup\BackupProcessorInterface;
use AwardWallet\MainBundle\Service\Backup\ProcessorInterestInterface;
use Doctrine\DBAL\Connection;

class ShoppingCategoryTransactionsProcessor implements BackupProcessorInterface
{
    private array $total = [];
    private array $last6Months = [];
    private string $last6StartDate;
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function register(ProcessorInterestInterface $processorInterest): void
    {
        if (!$processorInterest->isFullDump()) {
            return;
        }

        $processorInterest->addOnExportRow('AccountHistory', [$this, "onExportRow"]);
        $processorInterest->addPostProcessor([$this, "postProcess"]);

        $this->last6StartDate = date("Y-m-d", strtotime("-6 month"));
    }

    /**
     * @internal
     */
    public function onExportRow(array $row): array
    {
        if ($row['ShoppingCategoryID'] === null) {
            return $row;
        }

        $this->total[$row['ShoppingCategoryID']] = ($this->total[$row['ShoppingCategoryID']] ?? 0) + 1;

        if ($row['PostingDate'] >= $this->last6StartDate) {
            $this->last6Months[$row['ShoppingCategoryID']] = ($this->last6Months[$row['ShoppingCategoryID']] ?? 0) + 1;
        }

        return $row;
    }

    /**
     * @internal
     */
    public function postProcess(): void
    {
        foreach ($this->connection->executeQuery("select ShoppingCategoryID from ShoppingCategory")->fetchFirstColumn() as $id) {
            $this->connection->executeStatement(
                "update ShoppingCategory set 
                    TransactionsInLast6Months = :last6,
                    Transactions = :total
                where ShoppingCategoryID = :id",
                [
                    "last6" => $this->last6Months[$id] ?? 0,
                    "total" => $this->total[$id] ?? 0,
                    "id" => $id,
                ]
            );
        }
    }
}
