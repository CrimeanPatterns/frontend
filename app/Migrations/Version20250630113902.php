<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630113902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table `ProviderSignal` add column `Code` varchar(80) not null default "", add unique index (`Code`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `ProviderSignal` drop index `Code`, drop column `Code`');
    }
}
