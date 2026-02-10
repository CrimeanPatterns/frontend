<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151109102230 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `EliteLevel` ADD `ByDefault` TINYINT  UNSIGNED  NOT NULL  DEFAULT '0'  COMMENT 'Дефолтовый ли уровень по отношению к одноранковым уровням'  AFTER `Rank`");

        $this->write("set Default elite levels");

        foreach ($this->connection->executeQuery("SELECT DISTINCT(ProviderID) FROM EliteLevel") as $providerRow) {
            $ids = [];
            $stmt = $this->connection->executeQuery("SELECT EliteLevelID FROM EliteLevel WHERE ProviderID = " . intval($providerRow['ProviderID']) . " GROUP BY Rank HAVING COUNT(*) = 1");

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $ids[] = intval($row['EliteLevelID']);
            }

            if (sizeof($ids)) {
                $this->addSql("UPDATE EliteLevel SET `ByDefault` = 1 WHERE EliteLevelID IN (" . implode(",", $ids) . ")");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `EliteLevel` DROP `ByDefault`");
    }
}
