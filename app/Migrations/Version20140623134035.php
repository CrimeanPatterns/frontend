<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140623134035 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider add Login2Required tinyint not null default 0");
        $this->addSql("alter table Provider add Login3Required tinyint not null default 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Provider drop Login2Required");
        $this->addSql("alter table Provider drop Login3Required");
    }
}
