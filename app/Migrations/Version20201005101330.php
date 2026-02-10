<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201005101330 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP EmailBooking");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD EmailBooking TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Отправлять ли букеру письма, связанные с букингом' AFTER EmailBookingMessages");
    }
}
