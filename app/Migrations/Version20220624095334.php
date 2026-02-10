<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220624095334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE Provider ADD COLUMN CanRegisterRewardAvailabilityAccount TINYINT(4) NOT NULL DEFAULT 0 COMMENT 'Есть рабочий парсер с регистрацией аккаунта'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Provider DROP COLUMN CanRegisterRewardAvailabilityAccount');
    }
}
