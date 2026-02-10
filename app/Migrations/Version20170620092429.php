<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170620092429 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            create table `RetailProvider` (
                `RetailProviderID` int(11) not null auto_increment,
                `Name` varchar(200) not null comment 'название провайдера',
                `Code` varchar(200) not null comment 'код',
                `Homepage` varchar(512) default null comment 'сайт провайдера',
                `Keywords` varchar(2048) default null comment 'ключевые слова',
                `Regions` varchar(2048) default null comment 'регионы', 
                `State` int(11) default '0' comment 'состояние в процессе обработки',
                `Comment` varchar(1024) default null comment 'служебные комментарии',
                `ReviewerID` int(11) default null comment 'ревьювер, добавивший провадйер в основную таблицу',
                `AdditionalInfo` mediumtext default null comment 'дополнительная неструктурированная информация',
                `LastReviewDate` int(11) default null comment 'дата последней обработки',
                `DetectedProviderID` int(11) default null comment 'автоматически определенный провайдер',
                `ImportedProviderID` int(11) default null comment 'ссылка на добавленный провайдер',
                
                primary key (`RetailProviderID`),
                unique key `RetailProvider_Name_Regions`(`Name`(200), `Regions`(250)),
                foreign key(`ImportedProviderID`) references Provider(`ProviderID`) on delete set null,
                foreign key(`DetectedProviderID`) references Provider(`ProviderID`) on delete set null,
                foreign key(`ReviewerID`) references Usr(`UserID`) on delete set null
            ) engine=InnoDB comment='retail-провайдера';
        ");

        $this->addSql("
            create table `ProviderCountry` (
                `RetailProviderCountryID` int(11) not null auto_increment,
                `ProviderID` int(11) not null,
                `CountryID` int(11) not null,
                
                primary key (`RetailProviderCountryID`),
                foreign key(`ProviderID`) references Provider(`ProviderID`) on delete cascade
            ) engine=InnoDB comment='страны провайдера'
        ");

        $this->addSql("ALTER TABLE `Provider` 
            add column `AdditionalInfo` mediumtext default null comment 'дополнительная неструктурированная информация',
            add column `IsRetail` int(1) default '0' comment 'признак retail-провайдера'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table `ProviderCountry`');
        $this->addSql('drop table `RetailProvider`');
        $this->addSql('alter table `Provider` 
            drop column `AdditionalInfo`,
            drop column `IsRetail`
        ');
    }
}
