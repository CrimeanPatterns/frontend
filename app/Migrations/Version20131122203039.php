<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131122203039 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            create table AAMembership(
                AAMembershipID int unsigned not null auto_increment,
                FirstName varchar(30),
                LastName varchar(30),
                Visits int unsigned,
                Balance float,
                Expiration datetime,
                Account varchar(80),
                Status varchar(40),
                Tier1 int,
                Tier2 int,
                Tier3 int,
                PRIMARY KEY (AAMembershipID)
            )
            ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table AAMembership");
    }
}
