<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150904113903 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr
            add BetaInviterID int comment 'Кто пригласил этого пользователя в бету',
            add constraint fkBetaInviter foreign key(BetaInviterID) references Usr(UserID) on delete set null");
        $this->addSql("update Usr set BetaApproved = 0, InBeta = 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop foreign key fkBetaInviter");
        $this->addSql("alter table Usr drop column BetaInviterID");
    }
}
