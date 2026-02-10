<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220610092523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('drop table CreditCardShoppingCategory');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            create table CreditCardShoppingCategory (
                CreditCardShoppingCategoryID int auto_increment
                    primary key,
                CreditCardID                 int           not null,
                ShoppingCategoryID           int           null,
                Multiplier                   decimal(4, 2) not null comment 'Мультипликатор = отношение полученных миль к потраченным $ в рамках транзакции',
                StartDate                    date          null comment 'Дата начала расчетного квартала',
                Description                  mediumtext    null comment 'обьяснения как получить такой multiplier на такой категории на такой карте',
                constraint unique_key
                    unique (CreditCardID, ShoppingCategoryID, StartDate),
                constraint CreditCardShoppingCategory_ibfk_1
                    foreign key (CreditCardID) references CreditCard (CreditCardID)
                        on delete cascade,
                constraint CreditCardShoppingCategory_ibfk_2
                    foreign key (ShoppingCategoryID) references ShoppingCategory (ShoppingCategoryID)
                        on delete cascade
            )
        ");
        $this->addSql("create index ShoppingCategoryID on CreditCardShoppingCategory (ShoppingCategoryID)");
    }
}
