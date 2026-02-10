<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240610104155 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            ADD COLUMN MileValueID INT NULL,
            ADD CONSTRAINT fk_RAFlightSearchQuery_MileValueID FOREIGN KEY (MileValueID) REFERENCES MileValue(MileValueID)
            ON UPDATE CASCADE ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            DROP FOREIGN KEY fk_RAFlightSearchQuery_MileValueID,
            DROP COLUMN MileValueID
        ");
    }
}
