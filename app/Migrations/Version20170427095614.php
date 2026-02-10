<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170427095614 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Invites add UserAgentID int DEFAULT NULL after InviteeID");
        $this->addSql("alter table Invites add constraint fkUserAgent foreign key (UserAgentID) references UserAgent(UserAgentID) on delete cascade");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Invites drop foreign key fkUserAgent");
        $this->addSql("alter table Invites drop column UserAgentID");
    }
}
