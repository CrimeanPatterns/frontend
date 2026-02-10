<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181119110526 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `TravelerProfile` text;");
        $this->addSql("ALTER TABLE `UserAgent` ADD `TravelerProfile` text;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` DROP `TravelerProfile`;");
        $this->addSql("ALTER TABLE `UserAgent` DROP `TravelerProfile`;");
    }
}
