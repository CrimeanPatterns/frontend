<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170126070151 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AbSegment add column RoundTripDaysIdeal int DEFAULT NULL");
        $this->addSql("alter table AbSegment add column RoundTripDaysFrom int DEFAULT NULL");
        $this->addSql("alter table AbSegment add column RoundTripDaysTo int DEFAULT NULL");
        $this->addSql("alter table AbSegment add column RoundTripDaysFlex tinyint(1) not null default 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AbSegment drop column RoundTripDaysIdeal");
        $this->addSql("alter table AbSegment drop column RoundTripDaysFrom");
        $this->addSql("alter table AbSegment drop column RoundTripDaysTo");
        $this->addSql("alter table AbSegment drop column RoundTripDaysFlex");
    }
}
