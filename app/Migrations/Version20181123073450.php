<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181123073450 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `MerchantReport`
                ADD `Transactions` bigint(20) NOT NULL DEFAULT '1' COMMENT 'Кол-во транзакций в таблице AccountHistory';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `MerchantReport` DROP COLUMN `Transactions`;");
    }
}
