<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160226115523 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('Popularity');
        $table->addColumn('PopularityID', 'integer', ['unsigned' => true, 'autoincrement' => 'auto']);
        $table->addColumn('ProviderID', 'integer', ['notnull' => true, 'comment' => 'Relation to Provider.ProviderID']);
        $table->addColumn('CountryID', 'integer', ['notnull' => true, 'comment' => 'Relation to Country.CountryID']);
        $table->addColumn('Popularity', 'integer', ['notnull' => true, 'default' => 0, 'comment' => 'Популярность провайдера для конкретной страны, подсчитывается скриптом в Cron']);
        $table->addForeignKeyConstraint($schema->getTable('Provider'), ['ProviderID'], ['ProviderID'], ['onDelete' => 'CASCADE'], 'FK_Provider_Popularity');
        $table->addForeignKeyConstraint($schema->getTable('Country'), ['CountryID'], ['CountryID'], ['onDelete' => 'CASCADE'], 'FK_Country_Popularity');
        $table->addUniqueIndex(['ProviderID', 'CountryID'], 'Popularity_Unique_Fields');
        $table->setPrimaryKey(['PopularityId']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('Popularity');
    }
}
