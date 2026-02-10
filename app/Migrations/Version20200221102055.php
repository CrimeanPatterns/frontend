<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200221102055 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Rental ADD Sources JSON COMMENT 'Откуда собрана эта резервация' AFTER ChangeDate");
        $this->addSql("ALTER TABLE Reservation ADD Sources JSON COMMENT 'Откуда собрана эта резервация' AFTER ChangeDate");
        $this->addSql("ALTER TABLE Restaurant ADD Sources JSON COMMENT 'Откуда собрана эта резервация' AFTER ChangeDate");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Rental DROP COLUMN Sources");
        $this->addSql("ALTER TABLE Reservation DROP COLUMN Sources");
        $this->addSql("ALTER TABLE Restaurant DROP COLUMN Sources");
    }
}
