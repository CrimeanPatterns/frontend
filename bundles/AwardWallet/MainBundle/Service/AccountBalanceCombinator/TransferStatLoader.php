<?php

namespace AwardWallet\MainBundle\Service\AccountBalanceCombinator;

use Doctrine\DBAL\Connection;

class TransferStatLoader
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param int[] $targetProviderIds - providers whose miles we want to get in the end
     * @return array - array of arrays, where each inner array is indexed by source provider id
     */
    public function load(array $targetProviderIds): array
    {
        $stmt = $this->connection->executeQuery(
            "
                SELECT 
                    ts.SourceProviderID,
                    ts.TargetProviderID,
                    ts.SourceRate,
                    ts.TargetRate,
                    ts.BonusStartDate,
                    ts.BonusEndDate,
                    ts.BonusPercentage,
                    ts.MinimumTransfer
                FROM TransferStat ts
                WHERE
                    ts.TargetProviderID IN (:providerIds)
                    AND ts.SourceProviderID <> ts.TargetProviderID
                    AND ts.SourceRate IS NOT NULL
                    AND ts.SourceRate > 0
                    AND ts.TargetRate IS NOT NULL
                    AND ts.TargetRate > 0
            ",
            ['providerIds' => $targetProviderIds],
            ['providerIds' => Connection::PARAM_INT_ARRAY]
        );
        $stats = [];
        $now = date_create('now', new \DateTimeZone('EST'));

        while ($row = $stmt->fetchAssociative()) {
            $targetProviderId = (int) $row['TargetProviderID'];
            $sourceProviderId = (int) $row['SourceProviderID'];
            $sourceRate = (int) $row['SourceRate'];
            $targetRate = (int) $row['TargetRate'];
            $minimumTransfer = $row['MinimumTransfer'];

            $bonus = 100;
            $bonusStartDate = $row['BonusStartDate'] ? date_create($row['BonusStartDate'], new \DateTimeZone('EST')) : null;
            $bonusEndDate = $row['BonusEndDate'] ? date_create($row['BonusEndDate'], new \DateTimeZone('EST')) : null;

            if (
                !empty($row['BonusPercentage'])
                && (!$bonusStartDate || $now >= $bonusStartDate)
                && (!$bonusEndDate || $now < $bonusEndDate)
            ) {
                $bonus += (int) $row['BonusPercentage'];
            }

            $targetRate = $targetRate * $bonus / 100;
            $multiplier = $targetRate / $sourceRate;

            if (!isset($stats[$targetProviderId])) {
                $stats[$targetProviderId] = [];
            }

            $stats[$targetProviderId][$sourceProviderId] = [
                'multiplier' => $multiplier,
                'minimumTransfer' => $minimumTransfer ?? 1,
                'sourceStep' => $sourceRate,
            ];
        }

        return $stats;
    }
}
