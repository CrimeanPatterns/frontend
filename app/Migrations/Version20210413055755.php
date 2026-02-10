<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210413055755 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("rename table Hotel to Hotel_DELETE");
        $this->addSql("rename table HotelQuery to HotelQuery_DELETE");

        $this->addSql("create table Hotel(
            HotelID int not null auto_increment,
            
            ProviderID int not null,
            HotelBrandID int not null,
            Name varchar(512) not null,
            Address varchar(512) not null,
            Lat decimal(10,4) not null,
            Lng decimal(10,4) not null,
            Phones varchar(512) comment 'через запятую',
            Category tinyint comment 'это что-то типа звездочности. У большинство отелей есть градация категорий от 1 до 7',
            Matches json not null comment 'данные из HotelPointValue по которым создан этот отель',
            PointValue decimal(10,2) not null comment 'по какой средней цене наш народ редимит мили в этом конкретном отеле. Ожидается число типа 0.65 центов',
            CashPrice decimal(10,2) not null comment 'Сколько в среднем стоит номер на сутки, USD',
            PointPrice int not null comment 'сколько в среднем народ платит поинтов за 1 ночь в этом отеле. Например в среднем 1 ночь (одной комнаты) в поинтах стоит 65,000 поинтов',          
            CreateDate datetime default current_timestamp(),
            UpdateDate datetime default current_timestamp(),
            
            foreign key fkProvider(ProviderID) references Provider(ProviderID) on delete cascade,
            foreign key fkHotelBrand(HotelBrandID) references HotelBrand(HotelBrandID) on delete cascade,
            
            primary key (HotelID)
        ) engine=InnoDB comment 'Отели для поиска более выгодных с точки зрения пойнтов отелей, #20095'");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("drop table Hotel");
    }
}
