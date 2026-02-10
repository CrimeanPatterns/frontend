<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170324113003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->transactional(function () {
            $this->connection->executeQuery("alter table Plan add ShareCode varchar(32) comment 'Хэш для расшаривания травелплана'");

            $stmt = $this->connection->executeQuery("SELECT * FROM Plan");

            while ($plan = $stmt->fetch()) {
                $shareCode = RandomStr(ord('a'), ord('z'), 32);
                $this->connection->executeQuery(
                    "
                      UPDATE Plan SET ShareCode = '{$shareCode}' WHERE PlanID = {$plan['PlanID']}
                    ");
            }
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Plan drop ShareCode");
    }
}
