<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161213102324 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE PromotionCard CHANGE Visible VisibleInDetails tinyint");
        $this->addSql("ALTER TABLE PromotionCard ADD COLUMN VisibleInList tinyint NOT NULL DEFAULT 0 AFTER VisibleInDetails");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE PromotionCard CHANGE VisibleInDetails Visible tinyint");
        $this->addSql("ALTER TABLE PromotionCard drop VisibleInList");
    }
}
