<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210803040051 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Reservation ADD FreeNights INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во бесплатных ночей. Для корректного вычисления Point Value.' AFTER Total");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Reservation DROP FreeNights");
    }
}
