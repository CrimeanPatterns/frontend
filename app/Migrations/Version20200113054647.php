<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200113054647 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Reservation CHANGE Rooms Rooms text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Rooms as serialized array of \\AwardWallet\\MainBundle\\Entity\\Room objects'");
        $this->addSql("ALTER TABLE Reservation CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER DATABASE awardwallet CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
