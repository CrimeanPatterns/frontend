<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231005100510 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard`
                ADD `CashBackType` TINYINT(1) NULL DEFAULT NULL COMMENT 'Тип возвращаемого кэшбека (usd or point)\r\nCreditCard::CASHBACK_TYPE_'
                AFTER `IsCashBackOnly`,
            ALGORITHM=INSTANT
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `CreditCard`
                DROP `CashBackType`
        ');
    }
}
