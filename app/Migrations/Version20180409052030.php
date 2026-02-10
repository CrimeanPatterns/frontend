<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180409052030 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `Tip` (
                `TipID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `Code` varchar(64) DEFAULT NULL,
                `Title` varchar(255) DEFAULT NULL,
                `Description` text,
                `ReshowInterval` smallint(5) unsigned DEFAULT NULL,
                `Route` varchar(64) DEFAULT NULL,
                `Element` varchar(64) DEFAULT NULL,
                `Enabled` tinyint(1) NOT NULL DEFAULT '1',
                `CreateDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `SortIndex` mediumint(8) unsigned DEFAULT NULL,
                PRIMARY KEY (`TipID`),
                UNIQUE KEY `Code` (`Code`),
                UNIQUE KEY `ElementRoute` (`Element`,`Route`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->addSql("
            CREATE TABLE `UserTip` (
                `UserTipID` int(12) unsigned NOT NULL AUTO_INCREMENT,
                `UserID` int(11) NOT NULL,
                `TipID` int(11) unsigned NOT NULL,
                `ShowDate` datetime DEFAULT NULL,
                `ClickDate` datetime DEFAULT NULL,
                `CloseDate` datetime DEFAULT NULL,
                PRIMARY KEY (`UserTipID`),
                UNIQUE KEY `TipUserID` (`UserID`,`TipID`),
                KEY `UserID` (`UserID`),
                KEY `FK_TipID` (`TipID`),
                CONSTRAINT `FK_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE,
                CONSTRAINT `FK_TipID` FOREIGN KEY (`TipID`) REFERENCES `Tip` (`TipID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `Tip`");
        $this->addSql("DROP TABLE `UserTip`");
    }
}
