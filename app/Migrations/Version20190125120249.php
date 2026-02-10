<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190125120249 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ExtensionStat ADD AccountID INT NULL AFTER `ProviderID`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ExtensionStat DROP AccountID;");
    }
}
