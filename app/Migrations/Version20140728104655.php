<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140728104655 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
			ALTER TABLE `Provider` ADD `DontSendEmailsSubaccExpDate` TINYINT(1)  NOT NULL  DEFAULT '0'
 COMMENT 'Не отправлять письма о протухании субаккаунтов' AFTER `CanCheckExpiration`;
		");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("
			ALTER TABLE `Provider` DROP `DontSendEmailsSubaccExpDate`;
		");
    }
}
