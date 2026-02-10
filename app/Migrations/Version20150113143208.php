<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150113143208 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE `MobileDevice` (
                `MobileDeviceID` INT(11) NOT NULL AUTO_INCREMENT,
                `DeviceKey` VARCHAR(4096) NOT NULL,
                `DeviceType` TINYINT(1) UNSIGNED NOT NULL,
                `UserID` INT(11) NOT NULL,
                `CreationDate` DATETIME NOT NULL,
                PRIMARY KEY (`MobileDeviceID`),
                KEY `idx_MobileDevice_Key` (`DeviceKey`),
                KEY `idx_MobileDevice_UserID` (`UserID`),
                KEY `idx_MobileDevice_UserID_Key` (`UserID`, `DeviceKey`),
                CONSTRAINT `MobileDevice_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE
            )
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `MobileDevice`');
    }
}
