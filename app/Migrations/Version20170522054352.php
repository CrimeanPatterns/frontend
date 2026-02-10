<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170522054352 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE SocialAd ADD GeoGroups INT NULL COMMENT 'Какие геогруппы увидят рекламу' AFTER Kind
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE SocialAd DROP COLUMN GeoGroups
        ");
    }
}
