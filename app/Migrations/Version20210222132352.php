<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210222132352 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Parking add column `Sources` json DEFAULT NULL COMMENT 'Откуда собрана эта резервация'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Parking drop column Sources');
    }
}
