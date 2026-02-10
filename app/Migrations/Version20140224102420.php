<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140224102420 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('File');
        $table->addColumn('UploadDateTime', 'datetime', ['comment' => 'Дата/время загрузки файла']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('File');
        $table->dropColumn('UploadDateTime');
    }
}
