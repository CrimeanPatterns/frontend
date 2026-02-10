<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201208121034 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table Fingerprint(
            FingerprintID int not null auto_increment,
            UserID int not null,
            Hash varchar(80) not null comment 'используется для вычисления уникальных отпечатков, смотри FingerprintsController',
            BrowserFamily varchar(80) comment 'используется для поиска по семейству браузеров, например firefox, lowercase',
            BrowserVersion int  comment 'используется для поиска по версии браузера, например 80',
            Platform varchar(80) not null comment 'используется для поиска по платформе, например MacIntel',
            IsMobile tinyint not null,
            Fingerprint JSON not null comment 'key-value массив различных свойств браузера. Смотри fingerprints.js',
            Created datetime not null default current_timestamp(),
            LastSeen datetime not null default current_timestamp(),
            primary key (FingerprintID),
            foreign key fkUser (UserID) references Usr(UserID) on delete cascade,
            unique key(UserID, Hash) 
        ) engine=InnoDb comment 'отпечатки браузера, используются на loyalty, для эмуляции разных браузеров'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table Fingerprint");
    }
}
