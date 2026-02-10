<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170315145536 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table TripSegment add ScheduledDepDate datetime comment 'Дата вылета по расписанию, может отличаться от DepDate если рейс задерживается'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table TripSegment drop ScheduledDepDate");
    }
}
