<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170322125539 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->write("setting ScheduledDepDate");
        $this->connection->executeUpdate("update TripSegment set ScheduledDepDate = DepDate where ScheduledDepDate is null");
        $this->write("setting ScheduledArrDate");
        $this->connection->executeUpdate("update TripSegment set ScheduledArrDate = ArrDate where ScheduledArrDate is null");
        $this->addSql("alter table TripSegment
            modify ScheduledArrDate datetime comment 'Дата прилета по расписанию' not null,
            modify ScheduledDepDate datetime comment 'Дата вылета по расписанию' not null");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table TripSegment
            modify ScheduledArrDate datetime comment 'Дата прилета по расписанию',
            modify ScheduledDepDate datetime comment 'Дата вылета по расписанию'");
    }
}
