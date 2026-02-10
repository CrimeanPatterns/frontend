<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151126064357 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo add CreditCardPaymentType tinyint not null default 1 comment 'Шлюз для оплаты кредитной картой, Cart::PAYMENTTYPE_CREDITCARD или Cart::PAYMENTTYPE_RECURLY (abroaders)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo drop CreditCardPaymentType");
    }
}
