<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160804104031 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AccountHistory');
        $table->addColumn('UUID', 'guid', ['notnull' => false]);
        $table->addColumn('Custom', 'smallint', ['notnull' => true, 'default' => 0]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('AccountHistory');
        $table->dropColumn('UUID');
        $table->dropColumn('Custom');
    }
}
