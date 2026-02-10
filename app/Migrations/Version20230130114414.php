<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230130114414 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AbSegment 
                ADD COLUMN DepCheckOtherAirports TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Also check other airports in the metro area, if available',
                ADD COLUMN ArrCheckOtherAirports TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Also check other airports in the metro area, if available'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AbSegment 
                DROP COLUMN DepCheckOtherAirports,
                DROP COLUMN ArrCheckOtherAirports
        ");
    }
}
