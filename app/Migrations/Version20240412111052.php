<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240412111052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table `Provider` add column IsExtensionV3ParserEnabled tinyint(1) not null default 0 comment 'Проверка через Extension\\Manifest V3' after `CheckInBrowser`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `Provider` drop column IsExtensionV3ParserEnabled');
    }
}
