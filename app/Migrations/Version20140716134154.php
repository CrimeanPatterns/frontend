<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140716134154 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('AbTransaction')->addColumn('Title', 'string', ['notnull' => false, 'length' => 100, 'comment' => 'Комментарий к транзакции']);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('AbTransaction')->dropColumn('Gender');
    }
}
