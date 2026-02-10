<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230306060606 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Airline` ADD `AllianceID` INT NULL DEFAULT NULL AFTER `FSCode`');
        $this->addSql('ALTER TABLE `Airline` ADD CONSTRAINT `Airline_fk_AllianceID` FOREIGN KEY (`AllianceID`) REFERENCES `Alliance`(`AllianceID`) ON DELETE SET NULL ON UPDATE CASCADE');

        $this->addSql('
            UPDATE Airline a
            JOIN Provider p ON (p.IATACode = a.Code)
            SET a.AllianceID = p.AllianceID 
            WHERE
                    p.AllianceID IS NOT NULL
                AND p.IATACode IS NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Airline` DROP FOREIGN KEY Airline_fk_AllianceID');
        $this->addSql('ALTER TABLE `Airline` DROP INDEX `Airline_fk_AllianceID`');
        $this->addSql('ALTER TABLE `Airline` DROP `AllianceID`');
    }
}
