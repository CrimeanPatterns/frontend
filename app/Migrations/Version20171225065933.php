<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171225065933 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
                ALTER TABLE `Merchant`
                ADD `Transactions` BIGINT(0) NOT NULL DEFAULT '0' COMMENT 'Кол-во транзакций в таблице AccountHistory'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Merchant` DROP COLUMN `Transactions`
        ");
    }
}
