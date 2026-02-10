<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170412050128 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AirCode');
        $table->dropColumn('DST');
        $table->dropColumn('Preference');
        $table->dropColumn('TimeZoneUpdateDate');
        $table->dropColumn('Type');
    }

    public function down(Schema $schema): void
    {
    }
}
