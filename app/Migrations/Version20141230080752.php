<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141230080752 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('Provider');
        $table->addColumn('IATACode', 'string', ['length' => 2, 'notnull' => false, 'comment' => 'Airlines IATA Code']);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('Provider')->dropColumn('IATACode');
    }
}
