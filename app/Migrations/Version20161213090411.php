<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161213090411 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `FlightInfoConfig` ADD COLUMN `RegionFlag` tinyint(1) not null default '0'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `FlightInfoConfig` DROP COLUMN `RegionFlag`');
    }
}
