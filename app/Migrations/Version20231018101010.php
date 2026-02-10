<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231018101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `QsTransaction` WHERE Version = 3");

        $this->addSql("
            ALTER TABLE `QsTransaction`
                ADD `Category` VARCHAR(64) NULL DEFAULT NULL, 
                ADD `CountryCode` CHAR(6) NULL DEFAULT NULL, 
                ADD `State` VARCHAR(64) NULL DEFAULT NULL, 
                ADD `StateCode` CHAR(6) NULL DEFAULT NULL, 
                ADD `Exchange` VARCHAR(32) NULL DEFAULT NULL,
                ADD `ClickTime` DATETIME NULL DEFAULT NULL,
            ALGORITHM INSTANT 
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
