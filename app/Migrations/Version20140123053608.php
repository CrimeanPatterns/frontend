<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140123053608 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('UserAgent')->addUniqueIndex(['AgentID', 'Alias'], 'uk_Alias');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('UserAgent')->dropIndex('uk_Alias');
    }
}
