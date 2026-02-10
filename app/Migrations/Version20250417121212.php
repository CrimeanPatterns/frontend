<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250417121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` ADD `IsBankTransferable` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Transferable to bank currency'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `CreditCard` DROP `IsBankTransferable`
        ');
    }
}
