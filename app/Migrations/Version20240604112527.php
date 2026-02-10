<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240604112527 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD NewUserNotificationStatus TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 - новые настройки уведомлений для юзеров'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Usr DROP NewUserNotificationStatus');
    }
}
