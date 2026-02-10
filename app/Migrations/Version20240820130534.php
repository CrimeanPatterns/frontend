<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240820130534 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RAFlightSearchQuery DROP COLUMN Subscribers');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
                ADD COLUMN Subscribers JSON NULL COMMENT 'Список подписчиков на результаты поиска' AFTER MileValueID
        ");
    }
}
