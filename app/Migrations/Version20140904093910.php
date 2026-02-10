<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140904093910 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table UserEmailInfo (
			UserEmailInfoID int not null auto_increment,
			UserEmailID int not null,
			FirstName varchar(100) not null,
			LastName varchar(100) not null,
			CountryID int not null,
			Zip varchar(20),
			primary key (UserEmailInfoID),
			unique key(UserEmailID),
			foreign key (UserEmailID) references UserEmail(UserEmailID) on delete cascade on update cascade,
			foreign key (CountryID) references Country(CountryID) on delete cascade on update cascade
			) engine=InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table if exists UserEmailInfo");
    }
}
