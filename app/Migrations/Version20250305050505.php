<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20250305050505 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `BlogUserPost` (
              `BlogUserPostID` int NOT NULL AUTO_INCREMENT,
              `Type` tinyint(1) NOT NULL,
              `UserID` int NOT NULL,
              `PostID` bigint NOT NULL,
              `CreationDateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`BlogUserPostID`),
              UNIQUE KEY `TypePost` (`Type`,`UserID`,`PostID`) USING BTREE,
              KEY `BlogUserPost_UserID` (`UserID`),
              CONSTRAINT `BlogUserPost_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `BlogUserPost`');
    }
}
