<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210125121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `TransferStat` ADD `MinimumTransfer` INT(11) NULL DEFAULT NULL AFTER `TargetRate`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `TransferStat` DROP `MinimumTransfer`');
    }
}
