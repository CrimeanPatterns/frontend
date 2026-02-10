<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170626132103 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `ProviderCountry`
                change column `RetailProviderCountryID` `ProviderCountryID` int(11) not null auto_increment,
                add foreign  key `ProviderCountry_CountryID`(`CountryID`) references Country(`CountryID`) on delete cascade,
                add unique key `ProviderCountry_ProviderID_CountryID`(`ProviderID`, `CountryID`),
                add column `Site` varchar(80) not null comment 'главная страница сайта',
                add column `LoginURL` varchar(512) not null comment 'ссылка на страницу с логином',
                add column `LoginCaption` varchar(80) default '' not null comment 'что требуется для логина'  
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            alter table `ProviderCountry`
                change column `ProviderCountryID` `RetailProviderCountryID` int(11) not null auto_increment,
                drop key `ProviderCountry_ProviderID_CountryID`,
                drop key `ProviderCountry_CountryID`,
                drop foreign key `ProviderCountry_ibfk_2`,
                drop column `Site`,
                drop column `LoginURL`,
                drop column `LoginCaption`
        ");
    }
}
