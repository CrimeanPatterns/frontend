<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211103073017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("create table CreditCardBonusLimit(
            CreditCardBonusLimitID int not null auto_increment,
            CreditCardID int not null, 
            SpendingLimit decimal (9,2) not null comment 'До какой суммы дается кэшбэк',
            BonusMultiplier decimal(3,1) not null comment 'Процент кэшбэка',
            OnAllCategories tinyint not null default 0 comment 'лимит относится ко всем категориям.',
            `Period` char(1) not null comment 'Y - годовой, M - месячный',
            BeginDate date comment 'дата начала лимита (если он не годовой и не месячный)',
            EndDate date comment 'дата конца лимита (если он не годовой и не месячный)',
            primary key (CreditCardBonusLimitID),
            foreign key (CreditCardID) references CreditCard(CreditCardID) on delete cascade
        ) engine InnoDB comment 'Макс. ограничения по зарабатыванию бонусов. https://redmine.awardwallet.com/issues/20815'");

        $this->addSql("create table CreditCardBonusLimitGroup(
            CreditCardBonusLimitGroupID int not null auto_increment,
            CreditCardBonusLimitID int not null,
            ShoppingCategoryGroupID int not null,
            primary key (CreditCardBonusLimitGroupID),
            foreign key (CreditCardBonusLimitID) references CreditCardBonusLimit(CreditCardBonusLimitID) on delete cascade,
            foreign key (ShoppingCategoryGroupID) references ShoppingCategoryGroup(ShoppingCategoryGroupID),  
            unique key (CreditCardBonusLimitID, ShoppingCategoryGroupID)
        ) engine InnoDB comment 'Категории в которых установлен лимит кэшбэка'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table CreditCardBonusLimitGroup");
        $this->addSql("drop table CreditCardBonusLimit");
    }
}
