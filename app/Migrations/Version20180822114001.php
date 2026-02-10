<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180822114001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `ShoppingCategoryGroup` (
              `ShoppingCategoryGroupID` int(11) NOT NULL AUTO_INCREMENT,
              `Name` varchar(250) NOT NULL COMMENT 'Имя группы категории',
              `ClickURL` varchar(512) DEFAULT NULL COMMENT 'Ссылка на описание в блоге',
              PRIMARY KEY (`ShoppingCategoryGroupID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->addSql("
            ALTER TABLE `ShoppingCategory` 
              ADD `ShoppingCategoryGroupID` int(11) NULL DEFAULT NULL,
              ADD CONSTRAINT `ShoppingCategory_ibfk_2` FOREIGN KEY (`ShoppingCategoryGroupID`) REFERENCES `ShoppingCategoryGroup` (`ShoppingCategoryGroupID`) ON DELETE SET NULL;
        ");

        $this->addSql("
            CREATE TABLE `CreditCardShoppingCategoryGroup` (
              `CreditCardShoppingCategoryGroupID` int(11) NOT NULL AUTO_INCREMENT,
              `CreditCardID` int(11) NOT NULL,
              `ShoppingCategoryGroupID` int(11) NOT NULL,
              `Multiplier` decimal(3,1) NOT NULL COMMENT 'Мультипликатор = отношение полученных миль к потраченным $ в рамках транзакции',
              `StartDate` date DEFAULT NULL COMMENT 'Дата начала расчетного квартала',
              `Description` mediumtext COMMENT 'обьяснения как получить такой multiplier по такой группе категории на такой карте',
              PRIMARY KEY (`CreditCardShoppingCategoryGroupID`),
              UNIQUE KEY (`ShoppingCategoryGroupID`,`CreditCardID`,`StartDate`),
              CONSTRAINT `CCSCG_ibfk_1` FOREIGN KEY (`CreditCardID`) REFERENCES `CreditCard` (`CreditCardID`) ON DELETE CASCADE,
              CONSTRAINT `CCSCG_ibfk_2` FOREIGN KEY (`ShoppingCategoryGroupID`) REFERENCES `ShoppingCategoryGroup` (`ShoppingCategoryGroupID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `ShoppingCategory` 
                DROP FOREIGN KEY `ShoppingCategory_ibfk_2`,
                DROP `ShoppingCategoryGroupID`;
        ");
        $this->addSql("DROP TABLE `CreditCardShoppingCategoryGroup`;");
        $this->addSql("DROP TABLE `ShoppingCategoryGroup`;");
    }
}
