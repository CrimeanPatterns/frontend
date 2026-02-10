<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221223141414 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative('
            SELECT *
            FROM TransferStat
            ORDER BY TransferStatID ASC
        ');

        $unique = [];
        foreach ($rows as $row) {
            $key = $row['SourceProviderID']
                . '_'
                . $row['TargetProviderID']
                . '_'
                . (empty($row['SourceProgramRegion']) ? '' : $row['SourceProgramRegion'])
                . '_'
                . (empty($row['TargetProgramRegion']) ? '' : $row['TargetProgramRegion']);

            if (!array_key_exists($key, $unique)) {
                $unique[$key] = [];
            }

            $unique[$key][] = $row;
        }
        foreach ($unique as $key => $items) {
            if (1 === count($items)) {
                $this->connection->update('TransferStat', ['SourceProgramRegion' => '', 'TargetProgramRegion' => ''], ['TransferStatID' => $items[0]['TransferStatID']]);
                unset($unique[$key]);
            }
        }

        foreach ($unique as $key => $items) {
            $index = 0;
            foreach ($items as $row) {
                if (0 === $index) {
                    //$this->connection->update('TransferStat', ['SourceProgramRegion' => '', 'TargetProgramRegion' => ''], ['TransferStatID' => $row['TransferStatID']]);
                } else {
                    $this->connection->delete('TransferStat', ['TransferStatID' => $row['TransferStatID']]);
                    echo var_export($row, true) . PHP_EOL;
                }

                ++$index;
            }
        }

        $this->addSql("UPDATE TransferStat SET SourceProgramRegion = '' WHERE SourceProgramRegion IS NULL");
        $this->addSql("UPDATE TransferStat SET TargetProgramRegion = '' WHERE TargetProgramRegion IS NULL");

        $this->addSql("
            ALTER TABLE `TransferStat`
                CHANGE `SourceProgramRegion` `SourceProgramRegion` VARCHAR(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT 'Регион/страна источник',
                CHANGE `TargetProgramRegion` `TargetProgramRegion` VARCHAR(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT 'Регион/страна назначение' 
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
