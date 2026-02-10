<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140303043911 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table Reservation
        	add column Hash varchar(64) DEFAULT NULL comment 'Хеш, используется для резерваций с отсутствующим ConfirmationNumber',
        	add KEY `idxHash` (`AccountID`,`Hash`),
        	add KEY `idxUserHash` (`UserID`,`Hash`)");
        $this->addSql("alter table Rental
        	add column Hash varchar(64) DEFAULT NULL comment 'Хеш, используется для резерваций с отсутствующим Number',
        	add KEY `idxHash` (`AccountID`,`Hash`),
        	add KEY `idxUserHash` (`UserID`,`Hash`)");
        $this->addSql("alter table Restaurant
        	add column Hash varchar(64) DEFAULT NULL comment 'Хеш, используется для резерваций с отсутствующим ConfNo',
        	add KEY `idxHash` (`AccountID`,`Hash`),
        	add KEY `idxUserHash` (`UserID`,`Hash`)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table Reservation
        	drop column Hash,
        	drop KEY `idxHash`,
        	drop KEY `idxUserHash`");
        $this->addSql("alter table Rental
        	drop column Hash,
        	drop KEY `idxHash`,
        	drop KEY `idxUserHash`");
        $this->addSql("alter table Restaurant
        	drop column Hash,
        	drop KEY `idxHash`,
        	drop KEY `idxUserHash`");
    }
}
