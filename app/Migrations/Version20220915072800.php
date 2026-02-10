<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220915072800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table Merchant add index `idxDisplayName` (DisplayName)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Merchant drop index `idxDisplayName`');
    }
}
