<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160822141155 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add InviteCouponsCorrection smallint not null default 0 comment 'Число купонов заработанных в результате бага, #13496, эти купоны вычитаются при расчете бонусов за инвайты'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop InviteCouponsCorrection");
    }
}
