<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201110101222 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AirlineFareClass
            drop key AirlineID,
            drop ClassOfServiceID,
            drop FareClassID,
            drop FareBasisID,
            add foreign key fkAirline (AirlineID) references Airline(AirlineID) on delete cascade,
            add FareClass varchar(2) not null comment 'something like X, C',
            add ClassOfService varchar(40) not null comment 'see AwardWallet\MainBundle\Service\MileValue\Constants::CLASS_',
            add unique key akAll(AirlineID, FareClass, ClassOfService)");
        $this->addSql("drop table FareBasis");
        $this->addSql("drop table FareClass");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
