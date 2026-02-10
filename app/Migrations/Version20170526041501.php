<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170526041501 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("
                SELECT SocialAdID, Weight FROM SocialAd WHERE Weight > 0
            ");

            while ($ad = $stmt->fetch()) {
                $this->connection->executeQuery(
                    "INSERT INTO AdStat(SocialAdID, StatDate, Sent) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Sent = Sent + ?",
                    [$ad['SocialAdID'], date("Y-m-d H:i:s"), $ad['Weight'], $ad['Weight']],
                    [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]
                );
            }
            $this->connection->executeQuery("ALTER TABLE SocialAd DROP COLUMN Weight");
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE SocialAd ADD Weight INT DEFAULT '0' NOT NULL COMMENT 'Вес рекламы, в случае, если есть несколько активных на данный момент для данного юзера, то выбирается реклама с наименьшим весом для равномерного показа' AFTER InternalNote");
    }
}
