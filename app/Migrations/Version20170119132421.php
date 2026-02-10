<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170119132421 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE `CardImage` (
                `CardImageID` int(11) NOT NULL AUTO_INCREMENT,
                `UserID` int(11) DEFAULT NULL COMMENT 'Пользователь, которому принадлежит изображение',
                `AccountID` int(11) DEFAULT NULL COMMENT 'Аккаунт',
                `ProviderCouponID` int(11) DEFAULT NULL COMMENT 'Купон',
                `Kind` int(11) DEFAULT NULL COMMENT 'Вид изображения: 1 - front, 2 - back, 3 - scaled front, 4 - scaled back',
                `Width` int(11) NOT NULL COMMENT 'Ширина',
                `Height` int(11) NOT NULL COMMENT 'Высота',
                `FileName` VARCHAR(140) NOT NULL COMMENT 'Имя файла(которое доступно в т.ч. клиенту)',
                `FileSize` int(11) NOT NULL COMMENT 'Размер файла в байтах',
                `Format` VARCHAR(20) NOT NULL COMMENT 'Тип файла изображения',
                `StorageKey` VARCHAR(128) NOT NULL COMMENT 'Ключ в хранилище',
                `UploadDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время добавления',
                
                PRIMARY KEY (`CardImageID`),
                UNIQUE KEY (`AccountID`, `Kind`),
                UNIQUE KEY (`ProviderCouponID`, `Kind`),
                UNIQUE KEY (`StorageKey`),
                KEY(`UserID`, `StorageKey`),
                
                CONSTRAINT `CardImage_Usr_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE SET NULL,
                CONSTRAINT `CardImage_Account_AccountID` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE SET NULL,
                CONSTRAINT `CardImage_Account_ProviderCouponID` FOREIGN KEY (`ProviderCouponID`) REFERENCES `ProviderCoupon` (`ProviderCouponID`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `CardImage`');
    }
}
