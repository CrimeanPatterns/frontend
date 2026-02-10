<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160824113408 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("alter table ExtensionStat add ErrorDate DATETIME NOT NULL DEFAULT now() comment 'Дата возникновения ошибки' after ErrorCode");
//        $schema->getTable('ExtensionStat')
//            ->addColumn("ErrorDate", "datetime", ['default' => null, 'notnull' => true])
//            ->setComment('Дата возникновения ошибки');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $table = $schema->getTable('ExtensionStat');
        $table->dropColumn('ErrorDate');
    }
}
