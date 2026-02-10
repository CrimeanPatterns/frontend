<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241203080609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider add ExtensionV3ParserReady tinyint not null default 0 comment 'V3 parser written and checked'");
        $this->addSql("update Provider set ExtensionV3ParserReady = IsExtensionV3ParserEnabled");
        $this->addSql("update Provider set IsExtensionV3ParserEnabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
