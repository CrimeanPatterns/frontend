<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151125081026 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('Account');
        $table->getColumn('State')->setComment('состояние аккаунта(enabled, disabled, pending)');
    }

    public function down(Schema $schema): void
    {
    }
}
