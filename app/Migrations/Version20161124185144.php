<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161124185144 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE FlightInfoLog SET Service = 'sita_aero.subscribe' WHERE Service = 'sita_aero.notifications'");
        $this->addSql("UPDATE FlightInfoLog SET Service = 'sita_aero.notification', Request = CONCAT('https://awardwallet.com/callback/flightinfo/', Request) WHERE Service = 'sita_aero.callback'");
    }

    public function down(Schema $schema): void
    {
    }
}
