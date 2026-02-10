<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160202195117 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbRequest` ADD `PaymentCash` TINYINT(1)  NOT NULL  DEFAULT '0' COMMENT 'Оплата наличными (опция)' AFTER `CabinEconomy`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbRequest` DROP `PaymentCash`;");
    }
}
