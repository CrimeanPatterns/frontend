<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150130010432 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Trip ADD ShareCode VARCHAR(20)");
        $this->addSql("ALTER TABLE Rental MODIFY ShareCode VARCHAR(20)");
        $this->addSql("ALTER TABLE Reservation MODIFY ShareCode VARCHAR(20)");
        $this->addSql("ALTER TABLE Restaurant MODIFY ShareCode VARCHAR(20)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Trip DROP ShareCode");
        $this->addSql("ALTER TABLE Rental MODIFY ShareCode VARCHAR(32)");
        $this->addSql("ALTER TABLE Reservation MODIFY ShareCode VARCHAR(32)");
        $this->addSql("ALTER TABLE Restaurant MODIFY ShareCode VARCHAR(32)");
    }
}
