<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220331104328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider ADD COLUMN RewardAvailabilityLockAccount TINYINT(4) NOT NULL DEFAULT 0 COMMENT 'Необходимо ли блокировать аккаунты на время парсинга'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Provider DROP COLUMN RewardAvailabilityLockAccount');
    }
}
