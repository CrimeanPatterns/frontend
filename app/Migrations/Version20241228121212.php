<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241228121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `SubAccountType` (
              `SubAccountTypeID` int NOT NULL AUTO_INCREMENT,
              `ProviderID` int NOT NULL,
              `Patterns` varchar(255) DEFAULT NULL COMMENT 'Вхождения подстроки названия субаккаунта для связи или regexp. Ex. Schema/SubAccountType',
              `BlogIds` varchar(255) DEFAULT NULL COMMENT 'ID постов через запятую из админки блога',
              `MatchingOrder` smallint unsigned DEFAULT NULL COMMENT 'Если указано по возрастанию, далее STRLEN(Patterns) DESC',
              PRIMARY KEY (`SubAccountTypeID`),
              KEY `SubAccountType_ProviderID` (`ProviderID`),
              CONSTRAINT `SubAccountType_ProviderID` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB, COMMENT 'Связывание субаккаунтов по вхождению имени с блогпостами';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `SubAccountType`');
    }
}
