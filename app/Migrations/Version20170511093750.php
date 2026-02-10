<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170511093750 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE TripSegment ADD TripAlertsMonitored tinyint not null default 0 COMMENT 'Мониторится ли данный сегмент через FlightStats' AFTER TripInfoID");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE TripSegment DROP COLUMN TripAlertsMonitored');
    }
}
