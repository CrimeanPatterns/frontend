<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240507073417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ExtensionStat` ADD UNIQUE KEY `ErrorByDay` (ErrorDate, ProviderID, Success, ErrorText, Platform)');
        $this->addSql('ALTER TABLE `ExtensionStat` DROP FOREIGN KEY `ExtensionStat_ibfk_1`');
        $this->addSql('ALTER TABLE `ExtensionStat` DROP KEY `ProviderID`');
        $this->addSql('ALTER TABLE `ExtensionStat` ADD FOREIGN KEY (ProviderID) REFERENCES Provider(ProviderID)');
    }

    public function down(Schema $schema): void
    {
    }
}
