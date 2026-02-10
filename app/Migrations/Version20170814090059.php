<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170814090059 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table TripSegment 
            add SourceKind char(1) comment 'Откуда была собрана резервация, смотри константы SOURCE_KIND_',
            add SourceID varchar(40) comment 'ID сущности Откуда была собрана резервация, связана с SourceKind'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table TripSegment drop SourceKind, drop SourceID");
    }
}
