<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161102030257 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Invites add constraint fkInvites_ref_Usr foreign key (InviterID) references Usr(UserID) on delete cascade");
        $this->addSql("alter table Invites drop foreign key Invites_ibfk_1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Invites add constraint Invites_ibfk_1 foreign key Invites_ibfk_1(InviterID) references Usr(UserID) on delete cascade");
        $this->addSql("alter table Invites drop foreign key fkInvites_ref_Usr, drop key fkUsr");
    }
}
