<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180131112507 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE Reservation SET Rooms = 'a:0:{}' WHERE Rooms IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE Reservation SET Rooms = NULL WHERE Rooms = 'a:0:{}'");
    }
}
