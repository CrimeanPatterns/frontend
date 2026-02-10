<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201001030629 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table FareClass(
            FareClassID int not null auto_increment,
            Code varchar(2) not null comment 'Y, X, A, etc',
            primary key (FareClassID),
            unique key (Code)                
        ) engine=InnoDb comment 'Таблица состоящая из букв отображающая разные Fare Classes, для MileValue'");

        $this->addSql("create table FareBasis(
            FareBasisID int not null auto_increment,
            Code varchar(120) not null,
            primary key (FareBasisID),
            unique key (Code)                
        ) engine=InnoDB comment 'по сути похожи на Fare Classes но они отличаются в формате, вместо 1 или 2 букв они состоят типа из 8и букв'");

        $this->addSql("create table ClassOfService(
            ClassOfServiceID int not null auto_increment,
            Name varchar(250) not null,
            SortIndex int not null,
            primary key (ClassOfServiceID),
            unique key (Name)                
        ) engine=InnoDB comment 'Basic Economy, Economy, Premium Economy, Business, First Class'");

        $this->addSql("create table AirlineFareClass(
            AirlineFareClassID int not null auto_increment,
            AirlineID int not null,
            ClassOfServiceID int not null,
            FareClassID int not null,
            FareBasisID int not null,
            primary key (AirlineFareClassID),
            unique key (AirlineID, ClassOfServiceID, FareClassID, FareBasisID)                
        ) engine=InnoDB comment 'табличка связвающая авиалини, fare classes, fare basis и classes of service'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table AirlineFareClass");
        $this->addSql("drop table ClassOfService");
        $this->addSql("drop table FareBasis");
        $this->addSql("drop table FareClass");
    }
}
