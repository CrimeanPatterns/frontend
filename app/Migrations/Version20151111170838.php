<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151111170838 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account add Disabled tinyint not null default 0 COMMENT 'Отключенный аккаунт'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Account drop Disabled");
    }
}
