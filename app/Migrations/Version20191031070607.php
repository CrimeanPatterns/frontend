<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191031070607 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `QsTransaction` CHANGE `TransactionDate` `ClickDate` DATE NOT NULL');
        $this->addSql('ALTER TABLE `QsTransaction`
	        ADD `SearchDate` DATE NULL DEFAULT NULL AFTER `ClickDate`,
	        ADD `ProcessDate` DATE NULL DEFAULT NULL AFTER `SearchDate`');
        $this->addSql('ALTER TABLE `QsTransaction` CHANGE `CreationDate` `CreationDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql("ALTER TABLE `QsTransaction` CHANGE `Hash` `Hash` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'remove after full import #17380'");
    }

    public function down(Schema $schema): void
    {
    }
}
