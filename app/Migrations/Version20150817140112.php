<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150817140112 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			create table ProviderStatusHistory(
				ProviderStatusHistoryID int not null auto_increment,
				ProviderID int not null,
				DatetimeStamp datetime not null,
				TotalCheckedAccountsCount int not null,
				TotalErrorsCount int not null,
				UnknownErrorsCount int not null,
				primary key(ProviderStatusHistoryID),
				foreign key(ProviderID) references Provider(ProviderID) on delete cascade
			) engine=InnoDB comment 'История статуса сбора аккаунтов провайдера'
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table ProviderStatusHistory");
    }
}
