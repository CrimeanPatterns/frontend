<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190927092959 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table HistoryToTripLink(
            HistoryID char(36) not null comment 'AccountHistory.UUID',
            TripID int not null,
            LinkDate datetime not null comment 'Дата создания связи' default CURRENT_TIMESTAMP,
            unique key(HistoryID, TripID),
            foreign key fkHistory(HistoryID) references AccountHistory(UUID) on delete cascade,
            foreign key fkTrip(TripID) references Trip(TripID) on delete cascade
        ) ENGINE InnoDB comment 'Найденные в истории ссылки на Trip'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table HistoryToTripLink");
    }
}
