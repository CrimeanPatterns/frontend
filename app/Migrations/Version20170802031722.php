<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170802031722 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo
            modify PayPalClientId varchar(250) comment 'PayPal REST API Аккаунт для оплаты букзапросов',
            modify PayPalSecret varchar(250) comment 'PayPal REST API Аккаунт для оплаты букзапросов'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo
            modify PayPalClientId varchar(80) comment 'PayPal REST API Аккаунт для оплаты букзапросов',
            modify PayPalSecret varchar(80) comment 'PayPal REST API Аккаунт для оплаты букзапросов'");
    }
}
