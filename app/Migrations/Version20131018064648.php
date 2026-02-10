<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20131018064648 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update SiteGroup set GroupName = 'Booking business' where GroupName = 'Mileage Bookers'");
        $this->addSql("delete from SiteGroup where GroupName = 'Agents'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update SiteGroup set GroupName = 'Mileage Bookers' where GroupName = 'Booking business'");
        $this->addSql("insert into SiteGroup (GroupName, Description) values('Agents', 'Booking agents')");
    }
}
