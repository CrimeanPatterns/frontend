<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150226110804 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RewardsTransfer ADD UNIQUE KEY SP_TP_SR (SourceProviderID, TargetProviderID, SourceRate)');
        $this->addSql('ALTER TABLE RewardsTransfer DROP KEY SourceProviderID');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RewardsTransfer ADD UNIQUE KEY (SourceProviderID, TargetProviderID)');
        $this->addSql('ALTER TABLE RewardsTransfer DROP KEY SP_TP_SR');
    }
}
