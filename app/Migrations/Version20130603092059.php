<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */

//refs #6232
class Version20130603092059 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
			create table MailServer (
				MailServerID int not null auto_increment,
				Domain varchar(64) not null,
				Server varchar(64) not null,
				Port int not null,
				UseSsl tinyint,
				Protocol tinyint,
				MxKeyWords varchar(250),
				Connected tinyint,
				primary key (MailServerID),
				unique key(Domain)
				) ENGINE=InnoDB"
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table MailServer");
    }
}
