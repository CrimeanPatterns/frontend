<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220823080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `UsrDeleted` (
              `UserDeletedID` int NOT NULL AUTO_INCREMENT,
              `UserID` int NOT NULL,
              `RegistrationDate` datetime NOT NULL,
              `FirstName` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
              `LastName` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
              `Email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `CountryID` int NULL DEFAULT NULL,
              `Accounts` int NOT NULL DEFAULT '0',
              `ValidMailboxesCount` tinyint NOT NULL DEFAULT '0',
              `DeletionDate` datetime NOT NULL,
              `TotalContribution` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Общая сумма платежей',
              `CameFrom` int DEFAULT NULL,
              `Referer` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `CardClicks` int NOT NULL DEFAULT '0' COMMENT 'Кол-во кликов по ссылкам/банерам QS карт',
              `CardApprovals` int NOT NULL DEFAULT '0' COMMENT 'Сколько заявок из кликов заапрувлено',
              `CardEarnings` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Общая сумма дохода за счет пользователя по заапрувленным картам',
              `Reason` varchar(4000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              PRIMARY KEY (`UserDeletedID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Отчет по пользователю на момент удаления';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `UsrDeleted`');
    }
}
