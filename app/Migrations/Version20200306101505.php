<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200306101505 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr
          CHANGE COLUMN ItineraryCalendarCode ItineraryCalendarCode VARCHAR(32) DEFAULT NULL COMMENT 'Код Google Calendar для доступа к календарю поездок',
          ADD COLUMN AccExpireCalendarCode VARCHAR(32) DEFAULT NULL COMMENT 'Код Google Calendar для доступа к календарю Account Expirations' AFTER ItineraryCalendarCode;
        ");
        $this->addSql("ALTER TABLE UserAgent
          ADD COLUMN AccExpireCalendarCode VARCHAR(32) DEFAULT NULL COMMENT 'Код Google Calendar для доступа к календарю Account Expirations' AFTER ItineraryCalendarCode;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP AccExpireCalendarCode");
        $this->addSql("ALTER TABLE UserAgent DROP AccExpireCalendarCode");
    }
}
