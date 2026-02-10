<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181211100000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `BalanceWatchCredits` SMALLINT(5) UNSIGNED NULL DEFAULT '0'");
        $this->addSql("ALTER TABLE `Account` ADD `BalanceWatchStartDate` DATETIME NULL DEFAULT NULL AFTER `AcceleratedUpdateStartDate`, ADD INDEX (`BalanceWatchStartDate`)");
    }

    public function down(Schema $schema): void
    {
    }
}
