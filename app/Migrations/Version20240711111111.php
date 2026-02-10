<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240711111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Provider` ADD `TransactionPatterns` TEXT NULL DEFAULT NULL COMMENT 'Исключения расчета сумм для транзакций'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Provider` DROP `TransactionPatterns`");
    }
}
