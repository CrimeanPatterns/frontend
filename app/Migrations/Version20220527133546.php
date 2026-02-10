<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220527133546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            create table MerchantPattern(
                MerchantPatternID int not null auto_increment,
                Name varchar(250) not null comment 'Название мерчанта', 
                Patterns varchar(512) not null comment 'Паттерны для матчинга AccountHistory.Description к этой записи. Формат в PatternLoader::PATTERNS_SYNTAX_HELP',
                ClickUrl varchar(512) comment 'Ссылка на описание в блоге',
                Transactions int not null default 0 comment 'Сколько записей AccountHistory заматчили на эту запись',
                DetectPriority tinyint not null default 100 comment 'Приоритет при детекте, от 0 до 255. Чем меньше тем выше приоритет',
                unique key akName(Name),
                unique key akNamesOrPatterns(Patterns),
                key idxTransactions(Transactions),
                primary key (MerchantPatternID)
            ) engine InnoDB comment 'Паттерны для группировки и переименования мерчантов (таблицы Merchant)'
        ");
        $this->addSql("
            create table MerchantPatternGroup(
                MerchantGroupID int not null,
                MerchantPatternID int not null,
                unique key akContent(MerchantGroupID, MerchantPatternID),
                foreign key fkMerchantGroup(MerchantGroupID) references MerchantGroup(MerchantGroupID) on delete cascade,
                foreign key fkMerchantPattern(MerchantPatternID) references MerchantPattern(MerchantPatternID) on delete cascade
            ) engine InnoDB comment 'Связь MerchantGroup <-> MerchantPattern'
        ");
        $this->addSql("
            alter table Merchant 
                add MerchantPatternID int,
                algorithm INSTANT
        ");
        $this->addSql("
            alter table Merchant 
                add foreign key fkMerchantPattern(MerchantPatternID) references MerchantPattern(MerchantPatternID) on delete set null
        ");
        $this->addSql("drop table MerchantTEST");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table MerchantPatternGroup");
        $this->addSql("alter table Merchant 
            drop foreign key Merchant_ibfk_1,
            drop MerchantPatternID");
        $this->addSql("drop table MerchantPattern");
    }
}
