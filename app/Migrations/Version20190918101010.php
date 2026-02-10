<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190918101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `BalanceWatch` CHANGE `StopReason` `StopReason` TINYINT(3) NULL DEFAULT NULL COMMENT 'BalanceWatch::REASONS';");
    }

    public function down(Schema $schema): void
    {
    }
}
