<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add rewards transfer duration field.
 */
class Version20150217182807 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RewardsTransfer ADD TransferDuration VARCHAR(50) DEFAULT NULL COMMENT "Длительность трансфера"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RewardsTransfer DROP TransferDuration');
    }
}
