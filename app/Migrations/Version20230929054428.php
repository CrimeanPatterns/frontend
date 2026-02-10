<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230929054428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if (!$schema->hasTable('DetectedCard')) {
            $this->addSql(/** @lang MySQL */ "
            CREATE TABLE `DetectedCard` (
               `DetectedCardID` INT unsigned NOT NULL AUTO_INCREMENT,
               `AccountID` int(11) NOT NULL COMMENT 'AccountID из таблицы Account',
               `Code` varchar(250) NOT NULL COMMENT 'Код карты',
               `DisplayName` varchar(255) NOT NULL COMMENT 'Название карты',
               `Description` text COMMENT 'Описание для карты',
               `SubAccountID` int(11) COMMENT 'SubAccountID из таблицы SubAccount',
               `CreditCardID` int(11) COMMENT 'CreditCardID из таблицы CreditCard',
                PRIMARY KEY (`DetectedCardID`),
                UNIQUE KEY `Code` (`AccountID`,`Code`),
                CONSTRAINT `DetectedCard_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `Account` (`AccountID`) ON DELETE CASCADE ON UPDATE RESTRICT,
                CONSTRAINT `DetectedCard_ibfk_2` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE SET NULL ON UPDATE RESTRICT,  
                CONSTRAINT `DetectedCard_ibfk_3` FOREIGN KEY (`SubAccountID`) REFERENCES `SubAccount` (`SubAccountID`) ON DELETE SET NULL ON UPDATE RESTRICT  
            ) ENGINE=InnoDB COMMENT='Список всех карт, найденных в аккаунте юзера';
            ");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        if ($schema->hasTable('DetectedCard')) {
            $this->addSql(/** @lang MySQL */ 'DROP TABLE DetectedCard');
        }
    }
}
