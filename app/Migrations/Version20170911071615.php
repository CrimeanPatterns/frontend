<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170911071615 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
          CREATE TABLE Location
          (
            LocationID INT NOT NULL AUTO_INCREMENT,
            UserID INT DEFAULT NULL COMMENT 'Пользователь, которому принадлежит область для мониторинга',
            AccountID INT DEFAULT NULL COMMENT 'Привязка к аккаунту',
            SubAccountID INT DEFAULT NULL COMMENT 'Привязка к субаккаунту',
            ProviderCouponID INT DEFAULT NULL COMMENT 'Привязка к ваучеру',
            Name VARCHAR(250) NOT NULL COMMENT 'Название области. Адрес или координаты через запятую.',
            Lat DECIMAL(10,8) NOT NULL COMMENT 'Географическая широта центра области',
            Lng DECIMAL(11,8) NOT NULL COMMENT 'Географическая долгота центра области',
            Radius INT DEFAULT 50 NOT NULL COMMENT 'Радиус области',
            CreationDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время добавления',
            
            PRIMARY KEY (`LocationID`),
            KEY(`Name`),
            
            CONSTRAINT `Location_Usr_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE,
            CONSTRAINT `Location_Account_AccountID` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE CASCADE,
            CONSTRAINT `Location_Account_ProviderCouponID` FOREIGN KEY (`ProviderCouponID`) REFERENCES `ProviderCoupon` (`ProviderCouponID`) ON DELETE CASCADE,
            CONSTRAINT `Location_SubAccount_SubAccountID` FOREIGN KEY (`SubAccountID`) REFERENCES `SubAccount` (`SubAccountID`) ON DELETE CASCADE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
          ALTER TABLE Location COMMENT = 'Области для мониторинга и оповещения пользователя при входе в них';
          
          CREATE TABLE LocationSetting
          (
            LocationSettingID INT NOT NULL AUTO_INCREMENT,
            LocationID INT NOT NULL COMMENT 'Ссылка на область',
            UserID INT NOT NULL COMMENT 'Пользователь, который может менять настройки области мониторинга под себя',
            Tracked TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Отслеживается область или нет',
            
            PRIMARY KEY (`LocationSettingID`),
            UNIQUE KEY (`LocationID`, `UserID`),
            
            CONSTRAINT `LocationSetting_Location_LocationID` FOREIGN KEY (`LocationID`) REFERENCES `Location` (`LocationID`) ON DELETE CASCADE,
            CONSTRAINT `LocationSetting_Usr_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
          ALTER TABLE LocationSetting COMMENT = 'Настройки областей для мониторинга';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `LocationSetting`, `Location`');
    }
}
