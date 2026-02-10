<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170622093041 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `SubAccount` ADD `IsHidden` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Скрытие субаккаунта в общем списке' AFTER `Kind`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `SubAccount` DROP `IsHidden`;');
    }
}
