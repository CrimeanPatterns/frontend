<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150904122541 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add BetaInvitesCount int not null default 5 comment 'Скольких человек может пригласить в бету'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop BetaInvitesCount'");
    }
}
