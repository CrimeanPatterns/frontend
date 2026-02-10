<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170228084102 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `TripInfo` ADD COLUMN `SyncHash` varchar(200) not null default '' comment 'Хеш последнего запроса'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `TripSegment` drop foreign key TripInfoID");
        $this->addSql("alter table `TripSegment` drop column `TripInfoID`");
        $this->addSql("DROP TABLE `TripInfo`");
    }
}
