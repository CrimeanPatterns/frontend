<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201229080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
CREATE TABLE `BlogUserReport` (
  `BlogUserReportID` int(12) UNSIGNED NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `BlogPostID` bigint(20) UNSIGNED DEFAULT NULL,
  `InTime` datetime NOT NULL COMMENT 'Время открытия страницы',
  `OutTime` datetime DEFAULT NULL COMMENT 'Время ухода со страницы',
  `TimeZoneOffset` smallint(5) NOT NULL COMMENT 'TZ смещение из браузера пользователя',
  PRIMARY KEY (`BlogUserReportID`),
  CONSTRAINT `UserIDreport_fk` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE BlogUserReport');
    }
}
