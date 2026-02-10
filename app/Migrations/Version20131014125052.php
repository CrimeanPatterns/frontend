<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131014125052 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM AbPassenger WHERE FirstName IS NULL OR LastName IS NULL OR Birthday IS NULL");
        $this->addSql("ALTER TABLE `AbPassenger` CHANGE `FirstName` `FirstName` VARCHAR(255)  NOT NULL;");
        $this->addSql("ALTER TABLE `AbPassenger` CHANGE `LastName` `LastName` VARCHAR(255)  NOT NULL;");
        $this->addSql("ALTER TABLE `AbPassenger` CHANGE `Birthday` `Birthday` DATETIME  NOT NULL;");

        $table = $schema->getTable('AbAccountProgram');
        $table->addColumn('Requested', 'boolean', ['default' => false, 'after' => 'AccountID']);
        $table = $schema->getTable('AbCustomProgram');
        $table->addColumn('ProviderID', 'integer', ['notnull' => false, 'after' => 'RequestID']);
        $table->addIndex(['ProviderID'], 'IDX_AbCPProviderID');
        $table->addForeignKeyConstraint($schema->getTable('Provider'), ['ProviderID'], ['ProviderID'], ['onDelete' => 'SET NULL'], 'FK_AbCPProviderID');
        $table->addColumn('Requested', 'boolean', ['default' => false, 'after' => 'ProviderID']);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbPassenger` CHANGE `FirstName` `FirstName` VARCHAR(255) DEFAULT NULL;");
        $this->addSql("ALTER TABLE `AbPassenger` CHANGE `LastName` `LastName` VARCHAR(255) DEFAULT NULL;");
        $this->addSql("ALTER TABLE `AbPassenger` CHANGE `Birthday` `Birthday` DATETIME  DEFAULT NULL;");

        $table = $schema->getTable('AbAccountProgram');

        if ($table->hasColumn('Requested')) {
            $table->dropColumn('Requested');
        }
        $table = $schema->getTable('AbCustomProgram');

        if ($table->hasForeignKey('FK_AbCPProviderID')) {
            $table->removeForeignKey('FK_AbCPProviderID');
        }

        if ($table->hasIndex('IDX_AbCPProviderID')) {
            $table->dropIndex('IDX_AbCPProviderID');
        }

        if ($table->hasColumn('ProviderID')) {
            $table->dropColumn('ProviderID');
        }

        if ($table->hasColumn('Requested')) {
            $table->dropColumn('Requested');
        }
    }
}
