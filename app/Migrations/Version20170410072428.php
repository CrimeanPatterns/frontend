<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170410072428 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AirCode');
        $table->dropColumn('AirCountryCode');
        $table->dropColumn('ServiceType');
        $table->dropColumn('Flag');
        $table->dropColumn('AirType');
    }

    public function down(Schema $schema): void
    {
    }
}
