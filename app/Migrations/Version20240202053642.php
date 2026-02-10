<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240202053642 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            ADD State JSON NULL COMMENT 'Ошибки, состояние, данные для дебага' AFTER LastSearchDate
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RAFlightSearchQuery DROP State');
    }
}
