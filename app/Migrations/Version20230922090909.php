<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230922090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Hotel` ADD `IsResourt` TINYINT(1) NULL DEFAULT NULL COMMENT 'Возможность использовать курортный кредит', ALGORITHM INSTANT");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Hotel` DROP `IsResourt`");
    }
}
