<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180724121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `AAUCredits` SMALLINT(5) UNSIGNED NULL DEFAULT '0' AFTER `ChangePasswordMethod`");
        $this->addSql("ALTER TABLE `Account` ADD `AcceleratedUpdateStartDate` DATETIME NULL DEFAULT NULL AFTER `UpdateDate`, ADD INDEX (`AcceleratedUpdateStartDate`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` DROP `AAUCredits`");
        $this->addSql("ALTER TABLE `Account` DROP `AcceleratedUpdateStartDate`");
    }
}
