<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170307101703 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AirCode');
        $table->addColumn('IcaoCode', 'string', ['notnull' => false, 'length' => 4, 'comment' => 'ICAO код аэропорта']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('AirCode');
        $table->dropColumn('IcaoCode');
    }
}
