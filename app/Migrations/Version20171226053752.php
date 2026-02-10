<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171226053752 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
                ALTER TABLE `Merchant`
                ADD `Similar` BIGINT(0) NOT NULL DEFAULT '0' COMMENT 'Кол-во похожих мерчантов. считается скриптом'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Similar` DROP COLUMN `Transactions`
        ");
    }
}
