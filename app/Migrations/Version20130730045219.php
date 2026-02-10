<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130730045219 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			ALTER TABLE Airline
			ADD COLUMN Code varchar(2),
			ADD COLUMN ICAO varchar(3),
			ADD COLUMN LastUpdateDate datetime
		");
        // this up() migration is auto-generated, please modify it to your needs
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
			ALTER TABLE Airline
			DROP COLUMN Code,
			DROP COLUMN ICAO,
			DROP COLUMN LastUpdateDate
		");
        // this down() migration is auto-generated, please modify it to your needs
    }
}
