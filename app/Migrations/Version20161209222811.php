<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161209222811 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `FlightInfo`");
        $this->addSql("DELETE FROM `FlightInfoLog`");
    }

    public function down(Schema $schema): void
    {
    }
}
