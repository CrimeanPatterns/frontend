<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151016111456 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` ADD `LastCheckHistoryDate` DATETIME  NULL  DEFAULT NULL  COMMENT 'Дата последнего сбора истории аккаунта'  AFTER `LastCheckItDate`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` DROP `LastCheckHistoryDate`");
    }
}
