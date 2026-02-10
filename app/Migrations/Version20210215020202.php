<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210215020202 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("DELETE FROM Review WHERE UserID = 7 AND Review = 'Excellent!'");

        $this->addSql("ALTER TABLE `Provider` ADD `BlogTags` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Тэги для поиска связанных блогпостов, через запятую (страница рейтингов)'");

        $this->addSql(
            "
            ALTER TABLE `Review`
                CHANGE `CreationDate` `CreationDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CHANGE `UpdateDate` `UpdateDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ADD `Approved` TINYINT(1) NULL DEFAULT 0"
        );

        $this->addSql('UPDATE Review SET Approved = 1');

        $this->addSql(
            "
            CREATE TABLE `ReviewUserUseful` (
                `ReviewUserUsefulID` int(11) NOT NULL AUTO_INCREMENT,
                `ReviewID` int(11) NOT NULL,
                `UserID` int(11) NOT NULL,
                `CreationDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`ReviewUserUsefulID`),
                CONSTRAINT `reviewUserful_fk_reviewId` FOREIGN KEY (`ReviewID`) REFERENCES `Review` (`ReviewID`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `reviewUserful_fk_userId` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
                UNIQUE( `ReviewID`, `UserID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        "
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `Review` DROP `Approved`');
        $this->addSql('DROP TABLE `ReviewUserUseful`');
    }
}
