<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140731030240 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add PayPalRecurringProfileID varchar(128) comment 'ID подписки PayPal'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop PayPalRecurringProfileID");
    }
}
