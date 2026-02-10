<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231212071612 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
                DROP COLUMN MileCostLimit;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
                ADD COLUMN MileCostLimit DECIMAL(10,2) NULL COMMENT 'Лимит стоимости мили' AFTER FirstMilesLimit;
        ");
    }
}
