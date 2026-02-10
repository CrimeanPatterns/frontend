<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210120070707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `QsTransaction`
                ADD `PageViews` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0' AFTER `CPC`,
                ADD `CTR` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0' AFTER `PageViews`,
                ADD `AvgEpc` DECIMAL(10,2) NOT NULL DEFAULT '0' AFTER `CTR`;
        ");
        $this->addSql("ALTER TABLE `QsTransaction` CHANGE `Applications` `Applications` TINYINT(1) NULL DEFAULT '0'");
        $this->addSql("ALTER TABLE `QsTransaction` ADD `Version` TINYINT(1) NOT NULL DEFAULT '1'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `QsTransaction`
              DROP `PageViews`,
              DROP `CTR`,
              DROP `AvgEpc`;
        ');
        $this->addSql('ALTER TABLE `QsTransaction` DROP `Version`');
    }
}
