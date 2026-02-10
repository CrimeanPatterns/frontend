<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190507073242 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE `DocumentImage` (
                `DocumentImageID` int(11) NOT NULL AUTO_INCREMENT,
                `UserID` int(11) DEFAULT NULL COMMENT 'Пользователь, которому принадлежит изображение',
                `ProviderCouponID` int(11) DEFAULT NULL COMMENT 'Купон',
                `Width` int(11) NOT NULL COMMENT 'Ширина',
                `Height` int(11) NOT NULL COMMENT 'Высота',
                `FileName` VARCHAR(140) NOT NULL COMMENT 'Имя файла(которое доступно в т.ч. клиенту)',
                `FileSize` int(11) NOT NULL COMMENT 'Размер файла в байтах',
                `Format` VARCHAR(20) NOT NULL COMMENT 'Тип файла изображения',
                `StorageKey` VARCHAR(128) NOT NULL COMMENT 'Ключ в хранилище',
                `UploadDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время добавления',
                `UUID` CHAR(36) NOT NULL DEFAULT '' COMMENT 'уникальный идентификатор для доступа к картинке',
                `ClientUUID` VARCHAR(64) DEFAULT NULL COMMENT 'клиентский идентификатор для асинхронной загрузки нескольких изображений',
                
                PRIMARY KEY (`DocumentImageID`),
                UNIQUE KEY (`StorageKey`),
                KEY DocumentImage_UserID_ClientUUID (`UserID`, `ClientUUID`),
                
                CONSTRAINT `DocumentImage_Usr_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE SET NULL,
                CONSTRAINT `DocumentImage_Account_ProviderCouponID` FOREIGN KEY (`ProviderCouponID`) REFERENCES `ProviderCoupon` (`ProviderCouponID`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `DocumentImage`');
    }
}
