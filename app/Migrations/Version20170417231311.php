<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170417231311 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `ProviderCoupon`
                ADD COLUMN `TypeID` TINYINT(3) NOT NULL DEFAULT '0' AFTER `UserID`,
                ADD COLUMN `Pin` SMALLINT(5) UNSIGNED NULL DEFAULT NULL AFTER `ProgramName`,
                ADD COLUMN `CardNumber` VARCHAR(64) NOT NULL DEFAULT '' AFTER `ProgramName`,
                ADD COLUMN `DontTrackExpiration` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Установлена ли юзером галочка, что поинты не протухают' AFTER `ExpirationDate`,
                CHANGE `Description` `Description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `ProviderCoupon`
              DROP `TypeID`,
              DROP `CardNumber`,
              DROP `DontTrackExpiration`,
              DROP `Pin`
        ");
    }
}
