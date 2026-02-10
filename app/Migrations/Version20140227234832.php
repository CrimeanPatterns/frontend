<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140227234832 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('drop table DetectedCard');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("CREATE TABLE `DetectedCard` (
          `DetectedCardID` int(11) NOT NULL AUTO_INCREMENT,
          `AccountID` int(11) NOT NULL COMMENT 'AccountID из таблицы Account',
          `DisplayName` varchar(255) DEFAULT NULL COMMENT 'Название карты',
          `Balance` float DEFAULT NULL COMMENT 'Баланс карты',
          `CardDescription` text COMMENT 'Описание для карты',
          `Code` varchar(250) NOT NULL COMMENT 'Код карты',
          PRIMARY KEY (`DetectedCardID`),
          UNIQUE KEY `Code` (`AccountID`,`Code`),
          CONSTRAINT `DetectedCard_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Список всех карт, найденных в аккаунте юзера';");
    }
}
