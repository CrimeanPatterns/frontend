<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180208101757 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('Cart');

        if (!$table->hasIndex('CartAttrHash')) {
            $table->addUniqueIndex(['CartAttrHash'], 'CartAttrHash');
        }
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('Cart')->dropIndex('CartAttrHash');
    }
}
