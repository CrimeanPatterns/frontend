<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171214141008 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `ChaseFreedomTotals` (
              `ChaseFreedomTotalsID` int(11) NOT NULL AUTO_INCREMENT,
              `SubAccountID` int(11) NOT NULL,
              `StartDate` date NOT NULL COMMENT 'Дата начала отчетного квартал',
              `Total` double(11,2) NOT NULL DEFAULT '0.00' COMMENT 'Общая сумма транзакций по бонусной категории в текущем квартале',
              PRIMARY KEY (`ChaseFreedomTotalsID`),
              UNIQUE KEY `SubAccountID` (`SubAccountID`,`StartDate`),
              CONSTRAINT `ChaseFreedomTotals_ibfk_1` FOREIGN KEY (`SubAccountID`) REFERENCES `SubAccount` (`SubAccountID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `ChaseFreedomTotals`");
    }
}
