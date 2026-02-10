<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170419085013 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr 
            add ZipCodeProviderID int comment 'С какого провайдера был собран зип код',
            add ZipCodeAccountID int comment 'С какого аккаунта был собран зип код',
            add ZipCodeUpdateDate datetime comment 'Дата сбора/обновления зип кода',
            add foreign key(ZipCodeProviderID) references Provider(ProviderID) on delete set null,
            add foreign key(ZipCodeAccountID) references Account(AccountID) on delete set null");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop ZipCodeProviderID, drop ZipCodeAccountID, drop ZipCodeUpdateDate");
    }
}
