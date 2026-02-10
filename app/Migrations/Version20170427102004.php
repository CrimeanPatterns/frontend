<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170427102004 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            create table `ZipCode`(
                Zip varchar(40) not null comment 'ZIP-код USPS',
                Lat double not null comment 'широта зоны',
                Lng double not null comment  'долгота зоны',
                
                primary key(Zip)
            );
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table `ZipCode`');
    }
}
