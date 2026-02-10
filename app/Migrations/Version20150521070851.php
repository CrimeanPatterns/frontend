<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150521070851 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `PlusExpirationDate` DATETIME NULL COMMENT 'Дата протухания AW Plus' AFTER `FailedRecurringPayments`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` DROP `PlusExpirationDate`;");
    }
}
