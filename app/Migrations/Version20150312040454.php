<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150312040454 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Session` DROP `LoginDate`;");
        $this->addSql("ALTER TABLE `Session` DROP `LastActivityDate`;");
        $this->addSql("ALTER TABLE `Session` ADD `LoginDate` DATETIME  NOT NULL  COMMENT 'Дата и время логина' AFTER `Valid`;");
        $this->addSql("ALTER TABLE `Session` ADD `LastActivityDate` DATETIME  NOT NULL  COMMENT 'Дата и время последней активности' AFTER `LoginDate`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Session` DROP `LoginDate`;");
        $this->addSql("ALTER TABLE `Session` DROP `LastActivityDate`;");
        $this->addSql("ALTER TABLE `Session` ADD `LoginDate` int(11) NOT NULL COMMENT 'Таймстамп логина' AFTER `Valid`;");
        $this->addSql("ALTER TABLE `Session` ADD `LastActivityDate` int(11) NOT NULL COMMENT 'Таймстамп последней активности' AFTER `LoginDate`;");
    }
}
