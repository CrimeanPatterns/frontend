<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210121043115 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table AwardChart(
            AwardChartID int not null auto_increment,
            Name varchar(250) not null,
            unique key (Name),
            primary key (AwardChartID)
        ) engine InnoDB comment 'Часть схемы RewardsPrice'");

        $this->addSql("create table AwardChartAirline(
            AwardChartAirlineID int not null auto_increment,
            AwardChartID int not null,
            AirlineID int not null,
            unique key (AwardChartID, AirlineID),
            foreign key (AirlineID) references Airline(AirlineID) on delete cascade ,
            primary key (AwardChartAirlineID)
        ) engine InnoDB comment 'Часть схемы RewardsPrice'");

        $this->addSql("alter table RewardsPrice
            add AwardChartID int comment 'Should be not null, modify after filling in blanks' after ProviderID,
            add foreign key (AwardChartID) references AwardChart(AwardChartID) on delete cascade
        ");

        $this->addSql("alter table RewardsPrice 
            
        ");

        $this->addSql("drop table RewardsPriceAirline");

        $this->addSql("alter table Region
            add AwardChartID int after Kind,
            add foreign key (AwardChartID) references AwardChart(AwardChartID) on delete cascade,
            add AirCode char(3) after AwardChartID,
            add foreign key (AirCode) references AirCode(AirCode) on delete cascade");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
