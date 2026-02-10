<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140221132700 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM File");
        $table = $schema->getTable('File');
        $table->dropColumn('Resurce');
        $table->dropColumn('Path');
        $table->addColumn('Resource', 'string', ['comment' => 'Название ресурса, идентифицирующего файл (создается отдельная директория с этим названием)']);
        $table->addColumn('Filename', 'string', ['length' => 128, 'comment' => 'Имя файла']);
        $table->addColumn('ResourceId', 'integer', ['comment' => 'Цифровой идентификатор ресурса (участвует в создании директорий)']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('File');
        $table->dropColumn('Resource');
        $table->dropColumn('Filename');
        $table->dropColumn('ResourceId');
        $table->addColumn('Resurce', 'string');
        $table->addColumn('Path', 'string');
    }
}
