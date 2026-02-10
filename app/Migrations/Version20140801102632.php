<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140801102632 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AbBookerInfo ADD PayPalPassword VARCHAR(250) NULL DEFAULT NULL COMMENT 'Пароль от PayPal профиля' AFTER CurrencyID");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AbBookerInfo DROP PayPalPassword");
    }
}
