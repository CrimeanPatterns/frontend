<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220113131313 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` ADD `PointValue` DECIMAL(10,4) NULL DEFAULT NULL COMMENT 'Ценность для кастомных аккаунтов'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Account` DROP `PointValue`');
    }
}
