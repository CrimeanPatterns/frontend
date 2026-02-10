<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190227073001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `BalanceWatch` ADD `IsBusiness` TINYINT(1) NOT NULL DEFAULT '0' AFTER `StopDate`");
    }

    public function down(Schema $schema): void
    {
    }
}
