<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230418051743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fixed filed description';
    }

    public function up(Schema $schema): void
    {
        return;
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            ALTER TABLE Account 
                modify ProviderID int null comment 'ProviderID из таблицы Provider', ALGORITHM=INSTANT
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
