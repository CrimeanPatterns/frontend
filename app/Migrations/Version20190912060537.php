<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190912060537 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Region 
            add CountryID int comment 'Если выбрали Kind = Country',
            add foreign key (CountryID) references Country(CountryID) on delete cascade,
            add StateID int comment 'Если выбрали Kind = State',
            add foreign key (StateID) references State(StateID) on delete cascade,
            add UseForLongOrShortHaul tinyint not null default 0 comment 'чекбокс, будет использоваться для расчета перелет между регионами считать long или short haul',
            add UseForPromos tinyint not null default 0 comment 'чекбокс, если зачекан эта опция попадет в выпадуху https://awardwallet.com/promos и эти регионы пусть попадают в выбор в схеме deals',
            drop Address,
            drop AddressText,
            drop Zip,
            drop URL,
            drop key Name,
            modify Name varchar(120) COMMENT 'Название если Kind != State or Country'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Region 
            drop foreign key Region_ibfk_1,
            drop foreign key Region_ibfk_2,
            drop CountryID,
            drop StateID,
            drop UseForLongOrShortHaul,
            drop UseForPromos,
            add Address text,
            add AddressText text,
            add Zip text,
            add URL text,
            add unique key Name (Name)");
    }
}
