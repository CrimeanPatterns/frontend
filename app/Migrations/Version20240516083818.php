<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240516083818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ExtensionStat` ADD UNIQUE KEY `ErrorByDayWithStatus` (ErrorDate, ProviderID, Status, ErrorText, Platform)');
        $this->addSql('ALTER TABLE `ExtensionStat` DROP KEY `ErrorByDay`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ExtensionStat` ADD UNIQUE KEY `ErrorByDay` (ErrorDate, ProviderID, Success, ErrorText, Platform)');
        $this->addSql('ALTER TABLE `ExtensionStat` DROP KEY `ErrorByDayWithStatus`');
    }
}
