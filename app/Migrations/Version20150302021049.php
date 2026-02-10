<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150302021049 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD FailedRecurringPayments TINYINT NOT NULL DEFAULT 0 COMMENT 'Сколько раз PayPal не совершить Recurring. Нужно для того чтобы понять, нормальная ли ситуация даунгрейдить этого пользователя'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP FailedRecurringPayments");
    }
}
