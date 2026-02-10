<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171031072311 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `ShoppingCategoryMultiplier` (
              `ShoppingCategoryMultiplierID` int(11) NOT NULL AUTO_INCREMENT,
              `ShoppingCategoryID` int(11) NOT NULL,
              `CreditCardID` int(11) NOT NULL,
              `Multiplier` decimal(3,1) NOT NULL COMMENT 'Мультипликатор = отношение полученных миль к потраченным $ в рамках транзакции',
              `Transactions` int(11) NOT NULL COMMENT 'Число транзакций с таким множителем',
              PRIMARY KEY (`ShoppingCategoryMultiplierID`),
              UNIQUE KEY (`CreditCardID`,`ShoppingCategoryID`,`Multiplier`),
              CONSTRAINT `ShoppingCategoryMultiplier_ibfk_1` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE,
              CONSTRAINT `ShoppingCategoryMultiplier_ibfk_2` FOREIGN KEY (`ShoppingCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Сырые результаты аналитики истории по категориям';
       ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `ShoppingCategoryMultiplier`;");
    }
}
