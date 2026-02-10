<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190401131537 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MobileDevice 
            modify column `CreationDate` datetime NOT NULL default current_timestamp COMMENT 'Дата создания записи',
            modify column `UpdateDate` datetime NOT NULL default current_timestamp COMMENT 'Дата и время вылета' 
        ");
        $this->addSql("alter table Plan 
            modify column `CreationDate` datetime NOT NULL default current_timestamp COMMENT 'Дата создания записи'
        ");
        $this->addSql("alter table Rental 
            modify column `CreateDate` datetime NOT NULL default current_timestamp COMMENT 'Дата создания записи'
        ");
        $this->addSql("alter table Trip 
            modify column `CreateDate` datetime NOT NULL default current_timestamp COMMENT 'Дата создания записи'
        ");
        $this->addSql("alter table Reservation 
            modify column `CreateDate` datetime NOT NULL default current_timestamp COMMENT 'Дата создания записи'
        ");
        $this->addSql("alter table Restaurant 
            modify column `CreateDate` datetime NOT NULL default current_timestamp COMMENT 'Дата создания записи'
        ");
        $this->addSql("alter table Overlay 
            modify column `CreateDate` datetime NOT NULL default current_timestamp COMMENT 'Дата создания записи',
            modify column `UpdateDate` datetime NOT NULL default current_timestamp COMMENT 'Дата изменения записи'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
