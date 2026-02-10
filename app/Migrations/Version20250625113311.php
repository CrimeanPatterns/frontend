<?php

/** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlDialectInspection */

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250625113311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'refs #25696 user signals';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
        create table `ProviderSignal` (
            `ProviderSignalID` int not null auto_increment,
            `Name` varchar(255) not null,
            primary key(`ProviderSignalID`),
            unique key (`Name`)
        ) engine=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Типы сигналов программ'
        ");
        $this->addSql("
        create table `UserSignal`(
            `UserSignalID` int not null auto_increment,
            `UserID` int not null,
            `ProviderSignalID` int not null,
            `DetectedOn` datetime not null,
            primary key(`UserSignalID`),
            foreign key (`UserID`) references `Usr`(`UserID`) on delete cascade,
            foreign key(`ProviderSignalID`) references `ProviderSignal`(`ProviderSignalID`) on delete cascade
        ) engine=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Сигналы программ пользователей'
        ");
        $this->addSql("
        create table `SignalAttribute`(
            `SignalAttributeID` int not null auto_increment,
            `ProviderSignalID` int not null,
            `Name` varchar(255) not null,
            `Type` tinyint not null COMMENT 'SignalAttribute::TYPE_*',
            `PromptHelper` varchar(255) COMMENT 'Доп описание чтобы подставлять в промпт',
            primary key (`SignalAttributeID`),
            foreign key (`ProviderSignalID`) references `ProviderSignal`(`ProviderSignalID`),
            unique key (`ProviderSignalID`, `Name`)
        ) engine=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Доп аттрибуты сигналов'
        ");
        $this->addSql("
        create table `UserSignalAttribute`(
            `UserSignalAttributeID` int not null auto_increment,
            `UserSignalID` int not null,
            `SignalAttributeID` int not null,
            `Value` mediumtext,
            primary key (`UserSignalAttributeID`),
            foreign key (`UserSignalID`) references `UserSignal`(`UserSignalID`),
            foreign key (`SignalAttributeID`) references `SignalAttribute`(`SignalAttributeID`),
            unique key (`UserSignalID`, `SignalAttributeID`)
        ) engine=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Значения доп аттрибуты сигналов пользователей'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table if exists UserSignalAttribute, SignalAttribute, UserSignal, ProviderSignal');
    }
}
