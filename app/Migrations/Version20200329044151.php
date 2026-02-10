<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200329044151 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `AbShare` (
              `AbShareID` int(11) NOT NULL AUTO_INCREMENT,
              `UserID` int(11) NOT NULL COMMENT 'Автор букзапроса',
              `BookerID` int(11) NOT NULL COMMENT 'Букер, который сделал full запрос на шаринг',
              `RequestDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата full запроса на шаринг',
              `IsApproved` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Одобрен ли запрос',
              `ApproveDate` DATETIME NULL COMMENT 'Дата одобрения',
              PRIMARY KEY (`AbShareID`),
              KEY `IsApproved` (`IsApproved`),
              CONSTRAINT `AbShare_UserID_fk` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE,
              CONSTRAINT `AbShare_BookerID_fk` FOREIGN KEY (`BookerID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `AbShare`");
    }
}
