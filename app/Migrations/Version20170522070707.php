<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170522070707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` ADD `DisableExtension` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Отключение работы extension для аккаунта' AFTER `AuthInfo`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Account` DROP `DisableExtension`');
    }
}
