<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170316093523 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('Aircraft', ['comment' => 'Таблица наименований самолетов и их IATA-кодов. Собирается с FlightStats.']);
        $table->addColumn('AircraftID', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['AircraftID']);

        $table->addColumn('IataCode', 'string', ['length' => 3, 'comment' => 'IATA-код самолета']);
        $table->addColumn('Name', 'string', ['comment' => 'Наименование самолета']);
        $table->addColumn('TurboProp', 'boolean', ['default' => false]);
        $table->addColumn('Jet', 'boolean', ['default' => false]);
        $table->addColumn('WideBody', 'boolean', ['default' => false]);
        $table->addColumn('Regional', 'boolean', ['default' => false]);
        $table->addColumn('ShortName', 'string', ['Короткое название самолета, при создании дублирует Name, но есть возможность редактировать через менеджерку']);
        $table->addColumn('Icon', 'string', ['default' => '', 'comment' => 'Название иконки самолета из /web/images/aircrafts без расширения.']);
        $table->addColumn('UpdatedAt', 'datetime');
        $table->addUniqueIndex(['IataCode']);
        $table->addIndex(['IataCode']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('Aircraft');
    }
}
