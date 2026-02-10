<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210114073424 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table RewardsPrice(
            RewardsPriceID int not null auto_increment,
            ProviderID int not null comment 'провайдер чьи мили будут тратиться',
            FromRegionID int comment 'выпадуха с регионами отфильтрованная по kind = Airline Region. Может быть пустым', 
            ToRegionID int comment 'выпадуха с регионами отфильтрованная по kind = Airline Region. Может быть пустым',
            MinDistance int comment 'минимальное кол-во миль, например 1152',
            MaxDistance int comment 'максимальное кол-во миль, например 2000',
            DistanceUnit char comment 'Null (default) / M -Miles / K - Kilometers',
            primary key (RewardsPriceID),
            foreign key (ProviderID) references Provider(ProviderID) on delete cascade,
            foreign key (FromRegionID) references Region(RegionID) on delete cascade,
            foreign key (ToRegionID) references Region(RegionID) on delete cascade
        ) engine InnoDB comment 'Цены на перелеты в бонусных милях, #19560, note 2'");

        $this->addSql("create table RewardsPriceAirline(
            RewardsPriceAirlineID int not null auto_increment,
            RewardsPriceID int not null,
            AirlineID int not null,
            foreign key (RewardsPriceID) references RewardsPrice(RewardsPriceID) on delete cascade , 
            foreign key (AirlineID) references Airline(AirlineID) on delete cascade , 
            primary key (RewardsPriceAirlineID),
            unique key (RewardsPriceID, AirlineID)
        ) engine InnoDB comment 'одна или больше авиалиний которые могут оперировать перелет'");

        $this->addSql("create table RewardsPriceMileCost(
            RewardsPriceMileCostID int not null auto_increment,
            RewardsPriceID int not null,
            MileCost int not null comment ' количество миль которое требуется для перелета',
            TicketClass varchar(40) not null comment 'see MileValue\Constants::CLASSES_OF_SERVICE',
            AwardTypeID int not null,
            RoundTrip tinyint not null default 0 comment 'boolean', 
            primary key (RewardsPriceMileCostID),
            foreign key (RewardsPriceID) references RewardsPrice(RewardsPriceID) on delete cascade,
            foreign key (AwardTypeID) references AwardType(AwardTypeID) on delete cascade,
            unique key(RewardsPriceID, MileCost, TicketClass, AwardTypeID, RoundTrip)
        ) engine InnoDB comment 'часть схемы RewardsPrice, сколько миль требуется для какого перелета'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
