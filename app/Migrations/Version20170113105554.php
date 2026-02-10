<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170113105554 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update Usr set Subscription = " . Usr::SUBSCRIPTION_PAYPAL . " where PaypalRecurringProfileID like 'I-%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update Usr set Subscription = null where PaypalRecurringProfileID like 'I-%'");
    }
}
