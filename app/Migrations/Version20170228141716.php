<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170228141716 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('AirCode')->addIndex(['Lat', 'Lng'], 'idx_Geo');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('AirCode')->dropIndex('idx_Geo');
    }
}
