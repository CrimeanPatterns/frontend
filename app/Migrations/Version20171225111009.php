<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171225111009 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `Track` (
              `TrackID` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `SiteAdID` int(10) DEFAULT NULL COMMENT 'Table SiteAd.SiteAdID',
              `UserID` int(10) NOT NULL,
              `UpdateDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `CtID` int(10) unsigned DEFAULT NULL COMMENT 'Идентификатор пользователя eMiles',
              PRIMARY KEY (`TrackID`),
              KEY `AdUserCt_ID` (`SiteAdID`,`UserID`,`CtID`) USING BTREE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `Track`");
    }
}
