<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250123115230 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr
            CHANGE COLUMN EmailExpiration EmailExpiration TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Отправлять ли письма о протухании балансов. 0 - <нет>, 1 - <90, 60, 30, and every day for the last 7 days before expiration>, 2 - <90, 60, 30, and 7 days before expiration>';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr
            CHANGE COLUMN EmailExpiration EmailExpiration TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Отправлять ли письма о протухании балансов';
        ");
    }
}
