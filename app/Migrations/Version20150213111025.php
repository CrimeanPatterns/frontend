<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add to Provider table flag that indicates whether this provider can transfer rewards.
 */
class Version20150213111025 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider ADD CanTransferRewards tinyint(4) DEFAULT NULL COMMENT 'Может ли провайдер переводить бонусы в бонусы других провайдеров'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider DROP CanTransferRewards");
    }
}
