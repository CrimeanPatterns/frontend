<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210806081751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT INTO adminLeftNav (parentID, caption, path, rank, note, visible) VALUES (1, 'Debug Reward Availability', '/admin/debugRewardAvailability.php', 70, null, 1);");

        $this->addSql("ALTER TABLE Provider ADD COLUMN RewardAvailabilityPriority INT(11) NOT NULL DEFAULT 0 COMMENT 'Приоритет сбора/починки для сервиса Reward Availability'");

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM adminLeftNav WHERE caption = 'Debug Reward Availability'");

        $this->addSql('ALTER TABLE Provider DROP COLUMN RewardAvailabilityPriority');

    }
}
