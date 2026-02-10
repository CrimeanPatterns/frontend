<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140814115631 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AirCode modify Lat double not null, modify Lng double not null");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AirCode modify Lat int not null, modify Lng int not null");
    }
}
