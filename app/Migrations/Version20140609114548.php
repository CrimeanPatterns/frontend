<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140609114548 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('AbPassenger')->addColumn('Gender', 'string', ['notnull' => true, 'length' => 1, 'comment' => 'Пол пассажира']);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('AbPassenger')->dropColumn('Gender');
    }
}
