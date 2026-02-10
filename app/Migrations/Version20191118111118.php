<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191118111118 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard`
                ADD `CardFullName` VARCHAR(255) NULL DEFAULT NULL, 
                ADD `VisibleOnLanding` TINYINT(1) NULL DEFAULT '0', 
                ADD `VisibleInList` TINYINT(1) NOT NULL DEFAULT '0', 
                ADD `DirectClickURL` VARCHAR(255) NULL DEFAULT NULL,
                ADD `Text` TEXT NULL DEFAULT NULL,
                ADD `PictureVer` int(11) DEFAULT NULL,
                ADD `PictureExt` varchar(5) DEFAULT NULL, 
                ADD `SortIndex` MEDIUMINT(8) NULL DEFAULT NULL;
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
