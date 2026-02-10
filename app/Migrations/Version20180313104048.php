<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180313104048 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('Account');
        $table->addColumn('ChangesConfirmed', 'boolean', ['default' => true, 'after' => 'LastBalance']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('Account');

        if ($table->hasColumn('ChangesConfirmed')) {
            $table->dropColumn('ChangesConfirmed');
        }
    }
}
