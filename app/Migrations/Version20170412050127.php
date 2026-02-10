<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170412050127 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AirCode');
        $table->addColumn('Fs', 'string', ['length' => 4, 'notnull' => false, 'default' => '']);
        $table->addColumn('Faa', 'string', ['length' => 4, 'notnull' => false, 'default' => '']);
        $table->addColumn('Classification', 'integer', ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('AirCode');
        $table->dropColumn('Fs');
        $table->dropColumn('Faa');
        $table->dropColumn('Classification');
    }
}
