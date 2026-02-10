<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160804111344 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE AccountHistory h SET h.UUID = UUID() WHERE h.UUID IS NULL');
        $table = $schema->getTable('AccountHistory');
        $table->setPrimaryKey(['UUID']);
    }

    public function down(Schema $schema): void
    {
    }
}
