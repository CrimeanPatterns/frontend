<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140922063253 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add RecurringPaymentAmount tinyint");
        $this->addSql("update Usr set RecurringPaymentAmount = " . \TCart::AWPLUS_COST . " where PayPalRecurringProfileID is not null");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop RecurringPaymentAmount");
    }
}
