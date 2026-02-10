<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140610145624 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
			ALTER TABLE `Provider` ADD `CanRetrievePassword` TINYINT(1) DEFAULT NULL AFTER `PasswordMaxSize`;
		");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("
			ALTER TABLE `Provider` DROP `CanRetrievePassword`;
		");
    }
}
