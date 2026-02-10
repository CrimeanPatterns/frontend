<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210115065915 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("drop table if exists AwardValue");
        $this->addSql("drop table if exists AwardRegionLink");
        $this->addSql("drop table if exists Award");
        $this->addSql("drop table if exists AwardCategory");
        $this->addSql("drop table if exists AwardHotel");
        $this->addSql("drop table if exists AwardIntervalDateRegion");
        $this->addSql("drop table if exists AwardIntervalDate");
        $this->addSql("drop table if exists AwardIntervalRegionLink");
        $this->addSql("drop table if exists AwardInterval");
        $this->addSql("drop table if exists AwardUnit");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
