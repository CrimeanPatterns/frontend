<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220422050503 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
                ADD AvailableCardsUpdateDate DATETIME NULL DEFAULT NULL COMMENT 'Дата выбора юзером имеющихся у него карт для доступа к лаунджам в аэропортах',
                ADD HavePriorityPassCard TINYINT NOT NULL DEFAULT '0' COMMENT 'Есть ли у юзера карта Priority Pass для доступа к лаунджам в аэропортах';
        ");
        $this->addSql("
            CREATE TABLE CreditCardLoungeCategory (
                CreditCardID INT(11) NOT NULL COMMENT 'Ссылка на карту',
                LoungeCategoryID TINYINT(2) NOT NULL COMMENT 'Категория лаунджей',
                PRIMARY KEY (CreditCardID, LoungeCategoryID),
                KEY LoungeCategoryID (LoungeCategoryID),
                CONSTRAINT CreditCardLoungeCategory_CreditCardID_fk FOREIGN KEY (CreditCardID) REFERENCES CreditCard (CreditCardID) ON DELETE CASCADE
            ) ENGINE=InnoDB COMMENT 'Связь карт с категориями лаунджей';

            INSERT INTO CreditCardLoungeCategory (CreditCardID, LoungeCategoryID) VALUES 
                (19, 2),
                (140, 3),
                (128, 4),
                (127, 5),
                (96, 5);
        ");
        $this->addSql("
            CREATE TABLE UserCard (
                UserID INT(11) NOT NULL COMMENT 'Юзер, имеющий карту',
                CreditCardID INT(11) NOT NULL COMMENT 'Ссылка на кредитную карту',
                PRIMARY KEY (UserID, CreditCardID),
                CONSTRAINT UserCard_UserID_fk FOREIGN KEY (UserID) REFERENCES Usr (UserID) ON DELETE CASCADE,
                CONSTRAINT UserCard_CreditCardID_fk FOREIGN KEY (CreditCardID) REFERENCES CreditCard (CreditCardID) ON DELETE CASCADE
            ) ENGINE=InnoDB COMMENT 'Юзер выбрал какие карты у него есть. В отличие от UserCreditCard, юзер указывает сам вне зависимости от добавленных LP';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE IF EXISTS UserCard;
            DROP TABLE IF EXISTS CreditCardLoungeCategory;
            ALTER TABLE Usr DROP HavePriorityPassCard, DROP AvailableCardsUpdateDate;
        ");
    }
}
