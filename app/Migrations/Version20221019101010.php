<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20221019101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `IsUs` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Может ли пользователь открывать кредитную карту в банке'");
        $this->addSql('ALTER TABLE `Usr` ADD INDEX(`IsUs`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` DROP `IsUs`');
    }
}
