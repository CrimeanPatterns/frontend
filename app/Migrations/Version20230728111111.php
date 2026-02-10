<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230728111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `UserAuthStat` (
              `UserAuthStatID` int NOT NULL AUTO_INCREMENT,
              `UserID` int NOT NULL,
              `Browser` varchar(255) DEFAULT NULL,
              `Platform` varchar(64) DEFAULT NULL,
              `CountryID` int DEFAULT NULL,
              `IP` varchar(45) DEFAULT NULL,
              `UserAgent` varchar(255) NOT NULL,
              `Lang` varchar(64) DEFAULT NULL,
              `IsMobile` tinyint(1) DEFAULT NULL,
              `IsDesktop` tinyint(1) DEFAULT NULL,
              `CreateDate` datetime NOT NULL,
              `UpdateDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              `Counter` int NOT NULL DEFAULT '1',
              PRIMARY KEY (`UserAuthStatID`),
              UNIQUE KEY `UserAuthStat_uniq` (`UserID`,`IP`,`Platform`,`Browser`) USING BTREE,
              KEY `UserAuthStat_CountryID` (`CountryID`),
              CONSTRAINT `UserAuthStat_CountryID` FOREIGN KEY (`CountryID`) REFERENCES `Country` (`CountryID`) ON DELETE SET NULL ON UPDATE CASCADE,
              CONSTRAINT `UserAuthStat_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `UserAuthStat`;');
    }
}
