<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241225121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE `BlogLinkClick` (
              `BlogLinkClickID` int NOT NULL,
              `PrettyLink` varchar(255) NOT NULL,
              `TargetLink` varchar(255) NOT NULL,
              `Source` varchar(64) DEFAULT NULL,
              `Exit` varchar(64) DEFAULT NULL,
              `MID` varchar(64) DEFAULT NULL,
              `CID` varchar(64) DEFAULT NULL,
              `RefCode` varchar(16) DEFAULT NULL,
              `UserID` int DEFAULT NULL,
              `BlogPostID` bigint unsigned DEFAULT NULL,
              `UserAgent` varchar(255) DEFAULT NULL,
              `ClickDate` datetime NOT NULL,
              PRIMARY KEY (`BlogLinkClickID`),
              KEY `blogLinkClick_userId` (`UserID`),
              CONSTRAINT `blogLinkClick_userId` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `BlogLinkClick`');
    }
}
