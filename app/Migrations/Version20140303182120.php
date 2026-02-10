<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140303182120 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AbMessage');
        $table->addColumn('FromBooker', 'boolean', ['default' => false, 'notnull' => true, 'after' => 'Type', 'comment' => 'Сообщение от букера']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('AbMessage');
        $table->dropColumn('FromBooker');
    }
}
