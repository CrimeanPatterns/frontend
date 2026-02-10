<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190118061102 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `MerchantReport1` ADD `Tms` INT  NULL  AFTER `Transactions`;
            ALTER TABLE `MerchantReport2` ADD `Tms` INT  NULL  AFTER `Transactions`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `MerchantReport1` DROP `Tms`;
            ALTER TABLE `MerchantReport2` DROP `Tms`;
        ");
    }
}
