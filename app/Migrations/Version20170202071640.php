<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170202071640 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MobileDevice 
            modify UserID int comment 'кому принадлежит устройство',
            add CountryID int comment 'Страна, определяется по IP',
            add IP varchar(20),
            add foreign key fkCountry(CountryID) references Country(CountryID) on delete set null");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table MobileDevice 
            modify UserID int not null  comment 'кому принадлежит устройство',
            drop foreign key fkCountry,
            drop Column IP,
            drop column CountryID");
    }
}
