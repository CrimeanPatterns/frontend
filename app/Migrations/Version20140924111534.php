<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140924111534 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('AbRequestStatus');
        $table->dropColumn('Level');
        $table->dropColumn('Order');
        $table->addColumn('SortIndex', 'integer', ['notnull' => false, 'length' => 11, 'default' => 100, 'comment' => 'Индекс сортировки']);
        $table->addColumn('TextColor', 'string', ['notnull' => false, 'length' => 6, 'default' => '000000', 'comment' => 'Цвет текста опции в выпадухе']);
        $table->addColumn('BgColor', 'string', ['notnull' => false, 'length' => 6, 'default' => 'FFFFFF', 'comment' => 'Цвет фона опции в выпадухе']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('AbRequestStatus');
        $table->dropColumn('SortIndex');
        $table->dropColumn('TextColor');
        $table->dropColumn('BgColor');
        $table->addColumn('Level', 'string', ['length' => 255, 'comment' => 'Уровень']);
        $table->addColumn('Order', 'integer', ['length' => 6, 'comment' => 'Уровень']);
    }
}
