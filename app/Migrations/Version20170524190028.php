<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170524190028 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->executeQuery("ALTER TABLE SocialAd ADD Weight INT DEFAULT '0' NOT NULL COMMENT 'Вес рекламы, в случае, если есть несколько активных на данный момент для данного юзера, то выбирается реклама с наименьшим весом для равномерного показа' AFTER InternalNote");
        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("
                SELECT SocialAdID, SUM(Messages) AS Count FROM AdStat GROUP BY SocialAdID
            ");

            while ($ad = $stmt->fetch()) {
                $this->connection->executeQuery("UPDATE SocialAd SET Weight = ? WHERE SocialAdID = ?", [
                    $ad['Count'], $ad['SocialAdID'],
                ], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
            }
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE SocialAd DROP COLUMN Weight");
    }
}
