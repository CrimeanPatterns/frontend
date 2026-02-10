<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150427072429 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `MobileFeedback` (
                `MobileFeedbackID` INT(11) NOT NULL AUTO_INCREMENT,
                `Action` INT(11) NOT NULL COMMENT 'Тип фидбека',
                `Date` DATETIME NOT NULL COMMENT 'Дата фидбека',
                `UserID` INT(11) NOT NULL COMMENT 'Пользователь',
                `AppVersion` VARCHAR(20) NOT NULL COMMENT 'Версия мобильного приложения',
                PRIMARY KEY (`MobileFeedbackID`),
                KEY `idx_MobileFeedback_UserID` (`UserID`),
                CONSTRAINT `MobileFeedback_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Лог фидбека для мобильного приложения';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `MobileFeedback`');
    }
}
