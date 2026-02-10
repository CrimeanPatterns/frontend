<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150813133221 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
        create table ItineraryCheckError(
        	ItineraryCheckErrorID int not null auto_increment,
        	DetectionDate datetime not null,
        	ProviderID int not null,
        	ItineraryType char(1),
        	ItineraryID int,
        	ConfirmationNumber varchar(100),
        	AccountID int,
        	ErrorType tinyint not null,
        	ErrorMessage text,
        	Status tinyint not null default 1,
        	Comment text,
			primary key(ItineraryCheckErrorID),
            foreign key(ProviderID) references Provider(ProviderID) on delete cascade,
            foreign key(AccountID) references Account(AccountID) on delete cascade
		) engine=InnoDB comment 'Ошибки сбора резерваций'
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table ItineraryCheckError");
    }
}
