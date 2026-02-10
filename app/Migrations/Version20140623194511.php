<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140623194511 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('update TimeZone set Location = "America/St_Johns" where TimeZoneID = 19');
        $this->addSql('update TimeZone set Location = "Asia/Tehran" where TimeZoneID = 43');
        $this->addSql('update TimeZone set Location = "Asia/Kabul" where TimeZoneID = 46');
        $this->addSql('update TimeZone set Location = "Asia/Kolkata" where TimeZoneID = 49');
        $this->addSql('update TimeZone set Location = "Asia/Kathmandu" where TimeZoneID = 50');
        $this->addSql('update TimeZone set Location = "Asia/Rangoon" where TimeZoneID = 54');
        $this->addSql('update TimeZone set Location = "Australia/Darwin" where TimeZoneID = 65');
    }

    public function down(Schema $schema): void
    {
    }
}
