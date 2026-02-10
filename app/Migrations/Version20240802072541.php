<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240802072541 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            ADD COLUMN DeleteDate DATETIME NULL COMMENT 'Дата удаления' AFTER Subscribers;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            DROP COLUMN DeleteDate;
        ");
    }
}
