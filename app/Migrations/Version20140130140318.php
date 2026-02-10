<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140130140318 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
//        $schema->getTable('AbRequest')
//            ->addColumn("InternalStatus", "tinyint", ['default' => 0, 'notnull' => true])
//            ->setComment('Внутренний статус запроса');
        $this->addSql("ALTER TABLE `AbRequest`  ADD `InternalStatus` tinyint(2) not null default '0' COMMENT 'Внутренний статус запроса' after `Status`;");
    }

    public function down(Schema $schema): void
    {
        $schema->getTable("AbRequest")->dropColumn("InternalStatus");
    }
}
