<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171019104527 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `ShoppingCategory` (
              `ShoppingCategoryID` int(11) NOT NULL AUTO_INCREMENT,
              `Name` varchar(250) NOT NULL COMMENT 'Имя категории',
              `Patterns` mediumtext COMMENT 'Способы определения, разделены переносом строки',
              PRIMARY KEY (`ShoppingCategoryID`),
              UNIQUE KEY (`Name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->addSql("
            CREATE TABLE `CreditCardShoppingCategory` (
              `CreditCardShoppingCategoryID` int(11) NOT NULL AUTO_INCREMENT,
              `CreditCardID` int(11) NOT NULL,
              `ShoppingCategoryID` int(11) NOT NULL,
              `Multiplier` decimal(3,1) NOT NULL COMMENT 'Мультипликатор = отношение полученных миль к потраченным $ в рамках транзакции',
              `StartDate` date NOT NULL COMMENT 'Дата начала расчетного квартала',
              `Description` mediumtext COMMENT 'обьяснения как получить такой multiplier на такой категории на такой карте',
              PRIMARY KEY (`CreditCardShoppingCategoryID`),
              UNIQUE KEY (`ShoppingCategoryID`,`CreditCardID`,`Multiplier`,`StartDate`),
              CONSTRAINT `CreditCardShoppingCategory_ibfk_1` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE,
              CONSTRAINT `CreditCardShoppingCategory_ibfk_2` FOREIGN KEY (`ShoppingCategoryID`) REFERENCES `ShoppingCategory` (`ShoppingCategoryID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `CreditCardShoppingCategory`");
        $this->addSql("DROP TABLE `ShoppingCategory`");
    }
}
