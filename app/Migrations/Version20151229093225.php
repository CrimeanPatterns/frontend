<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * refs #12066 adding boarding passes to trip segments.
 */
class Version20151229093225 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table TripSegment add column BoardingPassURL varchar(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table TripSegment drop column BoardingPassURL');
    }
}
