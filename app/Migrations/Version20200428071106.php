<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200428071106 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Trip DROP COLUMN Tax");
        $this->addSql("ALTER TABLE Reservation DROP COLUMN Tax");
        $this->addSql("ALTER TABLE Rental DROP COLUMN Tax");
        $this->addSql("ALTER TABLE Restaurant DROP COLUMN Tax");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Trip ADD COLUMN Tax DECIMAL(12, 2) NULL COMMENT 'Amount paid in taxes' AFTER Fees");
        $this->addSql("ALTER TABLE Reservation ADD COLUMN Tax DECIMAL(12, 2) NULL COMMENT 'Amount paid in taxes' AFTER Fees");
        $this->addSql("ALTER TABLE Rental ADD COLUMN Tax DECIMAL(12, 2) NULL COMMENT 'Amount paid in taxes' AFTER Fees");
        $this->addSql("ALTER TABLE Restaurant ADD COLUMN Tax DECIMAL(12, 2) NULL COMMENT 'Amount paid in taxes' AFTER Fees");
    }
}
