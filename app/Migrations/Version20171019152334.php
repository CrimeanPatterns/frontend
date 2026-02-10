<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171019152334 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Rental ADD COLUMN `Type` VARCHAR (20) DEFAULT 'rental' COMMENT 'See Rental entity for constants for available types'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Rental DROP COLUMN `Type`");
    }
}
