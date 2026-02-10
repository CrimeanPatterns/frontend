<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class HistoryWriter
{
    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function saveHistory(int $mileValueId, array $old, array $new): array
    {
        $diff = $this->calcDiff($old, $new);

        if ($diff === null) {
            return [];
        }

        $history = $this->connection->fetchColumn("select History from MileValue where MileValueID = ?", [$mileValueId]);

        if ($history !== null) {
            $history = json_decode($history, true);
        } else {
            $history = [];
        }
        $history[] = $diff;
        $params = [
            'History' => json_encode($history),
            'UpdateDate' => date("Y-m-d H:i:s"),
        ];
        $this->logger->info("updating trip history for {$mileValueId} in MileValue");
        $this->connection->update("MileValue", $params, ["MileValueID" => $mileValueId]);

        unset($diff['Date']);

        return array_keys($diff);
    }

    private function calcDiff(array $old, array $new): ?array
    {
        foreach (['CreateDate', 'UpdateDate', 'Note', 'History', 'MileValueID', 'Status', 'Hash', 'FoundPrices'] as $key) {
            unset($old[$key], $new[$key]);
        }
        $old = array_intersect_key($old, $new);

        foreach (['TotalMilesSpent', 'TotalTaxesSpent', 'AlternativeCost', 'MileValue', 'MileDuration', 'TotalSpentInLocalCurrency', 'PriceAdjustment'] as $key) {
            if (isset($new[$key])) {
                $new[$key] = number_format($new[$key], 2, '.', '');
            }

            if (isset($old[$key])) {
                $old[$key] = number_format($old[$key], 2, '.', '');
            }
        }
        $diff = array_diff_assoc($old, $new);

        if (count($diff) === 0) {
            return null;
        }
        $changes = [];

        foreach ($diff as $key => $oldValue) {
            $changes[$key] = "{$oldValue} => {$new[$key]}";
        }
        $this->logger->info("diff: " . json_encode($changes));
        $diff['Date'] = date('Y-m-d H:i:s');

        return $diff;
    }
}
