<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160830154623 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			ALTER TABLE `EmailLog` ADD KEY `idx_UserID_MessageKind` (`UserID`, `MessageKind`);
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
			ALTER TABLE `EmailLog` DROP KEY `idx_UserID_MessageKind`;
		");
    }
}
