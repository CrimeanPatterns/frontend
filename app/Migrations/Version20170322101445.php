<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170322101445 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table TripSegment
           add ScheduledArrDate datetime comment 'Дата прилета по расписанию'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table TripSegment
           drop ScheduledArrDate");
    }
}
