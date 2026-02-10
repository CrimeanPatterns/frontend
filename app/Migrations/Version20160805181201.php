<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160805181201 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AccountHistory');
        $table->addColumn('Note', 'text', ['notnull' => false, 'length' => 1000]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('AccountHistory');
        $table->dropColumn('Note');
    }
}
