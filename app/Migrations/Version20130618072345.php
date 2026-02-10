<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */

//refs #6510
class Version20130618072345 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
			create table ExtensionStat (
				ExtensionStatID int not null auto_increment,
				ProviderID int not null,
				Success tinyint not null default 1,
				Count int not null default 1,
				ErrorText varchar(200),
				primary key (ExtensionStatID),
				foreign key (ProviderID) references Provider(ProviderID) on delete cascade,
				unique key (ProviderID, Success, ErrorText)
			) engine=InnoDB;");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table ExtensionStat");
    }
}
