<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140131181945 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('AbMessage')
            ->addColumn("LastUpdateDate", "datetime", ['default' => null, 'notnull' => false])
            ->setComment('Дата обновления сообщения');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable("AbMessage")->dropColumn("LastUpdateDate");
    }
}
