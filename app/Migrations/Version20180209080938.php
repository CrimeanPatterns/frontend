<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180209080938 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Airline 
            ADD COLUMN Active BOOLEAN DEFAULT TRUE, 
            ADD COLUMN FSCode VARCHAR(5) DEFAULT NULL AFTER ICAO,
            ADD CONSTRAINT fs_code_unique UNIQUE (FSCode),
            DROP INDEX Name
        ");
    }

    public function down(Schema $schema): void
    {
        //clean doubles with the same Name first or constraint will fail
        $this->addSql('DELETE a1 FROM Airline a1 JOIN Airline a2 ON a1.Name = a2.Name WHERE a2.Name IS NOT NULL AND a1.AirlineID < a2.AirlineID');
        $this->addSql('ALTER TABLE Airline DROP COLUMN Active, DROP COLUMN FSCode, ADD CONSTRAINT Name UNIQUE (Name)');
    }
}
