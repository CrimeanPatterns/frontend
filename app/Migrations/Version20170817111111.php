<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170817111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ProviderCoupon` CHANGE `Pin` `Pin` VARCHAR(12) NULL DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ProviderCoupon` CHANGE `Pin` `Pin` INT(11) NULL DEFAULT NULL');
    }
}
