<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191023101112 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `QsTransaction` ADD `Applications` TINYINT(1) NULL DEFAULT NULL AFTER `Approvals`, ADD `Click_ID` INT(12) UNSIGNED NULL DEFAULT NULL AFTER `Applications`');
        $this->addSql('ALTER TABLE `QsTransaction` ADD UNIQUE(`Click_ID`)');
        $this->addSql('ALTER TABLE `QsTransaction` DROP INDEX `Hash`');
        $this->addSql("ALTER TABLE `QsTransaction` CHANGE `TransactionDate` `TransactionDate` DATE NOT NULL COMMENT 'DateOfClick; ClickDate'");
    }

    public function down(Schema $schema): void
    {
    }
}
