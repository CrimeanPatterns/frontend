<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210916132447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("create table MerchantTEST(
            MerchantTESTID int auto_increment,
            MerchantID int,
            Name varchar(250) not null,
            DisplayName varchar(250), 
            ShoppingCategoryGroupID int,
            Providers json not null,
            CreditCards json not null,
            Categories json not null,
            Multipliers json not null,
            Transactions int not null,
            FirstSeenDate date not null,
            LastSeenDate date not null,
            unique key akName(Name, ShoppingCategoryGroupID),
            unique key akMerchantID(MerchantID, ShoppingCategoryGroupID),
            primary key (MerchantTESTID)
        ) engine InnoDB comment 'Тестовая аггрегация мерчантов. Удалить.'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table MerchantTEST");
    }
}
