<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240517093110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table `ExtensionStat` drop column Success');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `ExtensionStat` add column Success tinyint(1) default 1 not null');
    }
}
