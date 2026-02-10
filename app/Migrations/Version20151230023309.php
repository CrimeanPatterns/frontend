<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151230023309 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("SET FOREIGN_KEY_CHECKS=0");

        foreach (['Restaurant', 'Rental', 'Reservation', 'Trip'] as $table) {
            $this->addSql("delete from $table where UserID is null");
            $this->addSql("alter table $table modify UserID int not null");
        }
        $this->addSql("SET FOREIGN_KEY_CHECKS=1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("SET FOREIGN_KEY_CHECKS=0");

        foreach (['Restaurant', 'Rental', 'Reservation', 'Trip'] as $table) {
            $this->addSql("alter table $table modify UserID int");
        }
        $this->addSql("SET FOREIGN_KEY_CHECKS=1");
    }
}
