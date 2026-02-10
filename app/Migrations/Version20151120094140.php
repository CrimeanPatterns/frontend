<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151120094140 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider add CanCheckOneTime tinyint not null default 0 comment 'Аккаунт этого провайдера можно проверить только один раз, при добавлении аккаунта. Для southwest.'");
        $this->addSql("update Provider set CanCheckOneTime = 1 where Code = 'rapidrewards'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Provider drop CanCheckOneTime");
    }
}
