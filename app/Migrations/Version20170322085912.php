<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170322085912 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr 
            add TripAlertsHash varchar(80) comment 'Хэш трипа по которому мы получаем алерты от FlightStats',
            add TripAlertsStartDate datetime comment 'Дата начала трипа по которому мы получаем алерты от FlightStats',
            add TripAlertsEndDate datetime comment 'Дата завершения трипа по которому мы получаем алерты от FlightStats',
            add TripAlertsUpdateDate datetime comment 'Дата обновленияй подписки FlightStats',
            add TripAlertsCalcDate datetime comment 'Дата расчета подписки FlightStats',
            add TripAlertsMonitorable tinyint comment 'Подписка FlightStats активна, изменения отслеживаются'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr
            drop TripAlertsHash,
            drop TripAlertsStartDate,
            drop TripAlertsUpdateDate");
    }
}
