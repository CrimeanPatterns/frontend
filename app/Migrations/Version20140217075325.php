<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140217075325 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('File');
        $table->addColumn('FileId', 'integer', ['unsigned' => true, 'autoincrement' => 'auto']);
        $table->addColumn('Path', 'string');
        $table->addColumn('Resurce', 'string', ['notnull' => false]);
        $table->setPrimaryKey(['FileId']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('File');
    }
}
