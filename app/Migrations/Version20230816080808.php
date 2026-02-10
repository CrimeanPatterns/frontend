<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230816080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `UserAuthStat` ADD `AuthType` TINYINT(1) NULL DEFAULT NULL COMMENT 'Авторизация через мобильный раут /m/ (1) или десктоп (2)' AFTER `IsDesktop`;   
        ");

        $this->addSql('UPDATE UserAuthStat SET AuthType = 1 WHERE IsMobile = 1');
        $this->addSql('UPDATE UserAuthStat SET AuthType = 2 WHERE IsDesktop = 1');

        $this->addSql('ALTER TABLE `UserAuthStat` DROP FOREIGN KEY UserAuthStat_UserID');
        $this->addSql('ALTER TABLE `UserAuthStat` DROP INDEX `UserAuthStat_uniq`');
        $this->addSql('ALTER TABLE `UserAuthStat` ADD UNIQUE KEY `UserAuthStat_uniq` (`UserID`,`IP`,`Platform`,`Browser`, `AuthType`)');
        $this->addSql('ALTER TABLE `UserAuthStat` ADD CONSTRAINT `UserAuthStat_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `UserAuthStat` DROP `AuthType`');
    }
}
