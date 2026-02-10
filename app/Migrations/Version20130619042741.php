<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
// refs# 6232
class Version20130619042741 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table UserEmail add column UseGoogleOauth tinyint(1) not null default 0");
        $this->addSql("
			create table UserEmailToken (
			UserEmailTokenID int(11) not null auto_increment,
			UserEmailID int(11) not null,
			Token varchar(128) not null,
			Added datetime not null,
			primary key (UserEmailTokenID),
			foreign key (UserEmailID) references UserEmail(UserEmailID) on delete cascade on update cascade
			) engine=InnoDB;
		");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table UserEmail modify column Password varchar(4000) collate utf8_unicode_ci not null, drop column UseGoogleOauth");
        $this->addSql("drop table UserEmailToken");
    }
}
