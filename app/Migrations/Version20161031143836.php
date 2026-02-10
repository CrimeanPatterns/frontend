<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161031143836 extends AbstractMigration
{
    protected $replace = [
        ['awplus_trial_expired', 'aw_plus_expired'],
        ['awplus_by_coupon_account_expired', 'aw_plus_expired'],
        ['awplus_account_expired', 'aw_plus_expired'],
        ['awplus_account_expire_soon', 'aw_plus_expire_soon'],
    ];

    public function up(Schema $schema): void
    {
        $stmt = $this->connection->prepare("
            SELECT
                *
            FROM
                EmailStat
            WHERE
                Kind = ? OR Kind = ?
            ORDER BY StatDate ASC
        ");

        foreach ($this->replace as $data) {
            [$oldKind, $newKind] = $data;
            $stmt->execute([$oldKind, $newKind]);

            $date = $oldKindRow = $newKindRow = null;

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (!$date) {
                    $date = $row['StatDate'];
                } elseif ($row['StatDate'] != $date) {
                    $this->process($oldKindRow, $newKindRow, $newKind);
                    $oldKindRow = $newKindRow = null;
                    $date = $row['StatDate'];
                }

                if ($row['Kind'] == $oldKind) {
                    $oldKindRow = $row;
                } else {
                    $newKindRow = $row;
                }
            }

            if (isset($oldKindRow) || isset($newKindRow)) {
                $this->process($oldKindRow, $newKindRow, $newKind);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    private function process($oldKindRow, $newKindRow, $newKind)
    {
        if (isset($oldKindRow) && !isset($newKindRow)) {
            $this->connection->executeUpdate("UPDATE EmailStat SET Kind = ? WHERE EmailStatID = ?", [$newKind, $oldKindRow['EmailStatID']]);
        } elseif (isset($oldKindRow) && isset($newKindRow)) {
            $this->connection->executeUpdate("UPDATE EmailStat SET Messages = ? WHERE EmailStatID = ?", [$newKindRow['Messages'] + $oldKindRow['Messages'], $newKindRow['EmailStatID']]);
            $this->connection->delete('EmailStat', [
                'EmailStatID' => $oldKindRow['EmailStatID'],
            ]);
        }
    }
}
