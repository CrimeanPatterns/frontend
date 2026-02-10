<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150226123607 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE GeoTag
			ADD StateCode VARCHAR(80) COMMENT 'Код или короткое название штата',
			ADD CountryCode VARCHAR(80) COMMENT 'Код или короткое название страны'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE GeoTag DROP StateCode, DROP CountryCode");
    }
}
