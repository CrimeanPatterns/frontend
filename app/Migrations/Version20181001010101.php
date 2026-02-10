<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181001010101 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` ADD `UpdateLimitDisabledUntil` DATETIME NULL DEFAULT NULL COMMENT 'Отключение лимита обновлений до этой даты для !awplus'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` DROP `UpdateLimitDisabledUntil`");
    }
}
