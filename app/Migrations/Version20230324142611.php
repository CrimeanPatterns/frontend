<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230324142611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE RAFlight
                ADD COLUMN SegmentClassOfService varchar(255) COMMENT "classOfService по сегментам через зпт", 
                ADD INDEX `idxSegmentCOS` (`SegmentClassOfService`)
                ');
        $this->addSql('ALTER TABLE AirClassDictionary
                ADD COLUMN SourceFareClass INT NOT NULL DEFAULT 1 COMMENT "1 - MileValue, 2 - RewardAvailability", 
                ADD COLUMN ProviderID INT,
                ADD COLUMN FirstSeenDate datetime,
                ADD COLUMN LastSeenDate datetime,
                ADD INDEX `idxSourceFareClass` (`SourceFareClass`),
                ADD INDEX `idxProviderID` (`ProviderID`),
                ADD INDEX `idxFirstSeenDate` (`FirstSeenDate`),
                ADD INDEX `idxLastSeenDate` (`LastSeenDate`)
                ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE RAFlight
                DROP COLUMN segmentClassOfService, 
                ');
        $this->addSql('ALTER TABLE AirClassDictionary
                DROP COLUMN SourceFareClass, 
                DROP COLUMN ProviderID INT,
                DROP COLUMN FirstSeenDate datetime,
                DROP COLUMN LastSeenDate datetime,
                ');
    }
}
