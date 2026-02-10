<?php

namespace AwardWallet\MainBundle\Service\ProviderSignal;

use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class ProviderSignalList extends \TBaseList
{
    private Connection $connection;

    public function __construct(
        string $table,
        array $fields,
        Connection $connection
    ) {
        parent::__construct($table, $fields);
        $this->connection = $connection;
        $this->SQL = "select *, null as Attributes from ProviderSignal";
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if ($output === 'html') {
            $this->Query->Fields["Attributes"] = stmtAssoc($this->connection->executeQuery("
                select Name from SignalAttribute 
                where ProviderSignalID = ?
                order by SignalAttributeID", [$this->OriginalFields["ProviderSignalID"]]))
                ->map(fn (array $row) => "{$row['Name']}")
                ->joinToString(", ");
        }
    }
}
