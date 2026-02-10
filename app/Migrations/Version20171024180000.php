<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171024180000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` DROP FOREIGN KEY `Usr_ibfk_2`');
        $this->addSql('ALTER TABLE `Usr` DROP INDEX `fk_Usr_ref_TimeZone`');
        $this->addSql('ALTER TABLE `Usr` DROP `TimeZoneID`');
    }

    public function down(Schema $schema): void
    {
    }
}
