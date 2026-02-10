<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20220824121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` ADD `IsOfferPriorityPass` TINYINT(1) NOT NULL DEFAULT '0'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` DROP `IsOfferPriorityPass`");
    }
}
