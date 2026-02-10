<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171213112010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` ADD `DisableAutologin` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Автологин отключён' AFTER `DisableExtension`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` DROP `DisableAutologin`");
    }
}
