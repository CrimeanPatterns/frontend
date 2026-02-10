<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220916100949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table Merchant add fulltext idxFTDisplayNameTokenSize1(DisplayName)');
        $this->addSql('alter table Merchant drop index idxFTDisplayName');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Merchant drop index idxFTDisplayNameTokenSize1');
    }
}
