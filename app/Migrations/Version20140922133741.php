<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140922133741 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserEmailInfo add column UserAgentID int, drop column FirstName, drop column LastName");
        $this->addSql("alter table UserEmailInfo add constraint UserEmailInfo_fk_ua foreign key fk_uaid(UserAgentID) references UserAgent(UserAgentID) on update cascade on delete cascade");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table UserEmailInfo drop foreign key UserEmailInfo_fk_ua");
        $this->addSql("alter table UserEmailInfo drop column UserAgentID, add column FirstName varchar(100) not null, add column LastName varchar(100) not null");
    }
}
