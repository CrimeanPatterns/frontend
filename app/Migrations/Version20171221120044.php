<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171221120044 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
                ALTER TABLE `ShoppingCategory`
                ADD `ClickURL` varchar(512) NULL COMMENT 'Ссылка на описание в блоге';
                ALTER TABLE `CreditCard` 
                CHANGE `ClickURL` VARCHAR(512) NULL COMMENT 'Ссылка на описание в блоге';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `ShoppingCategory` DROP COLUMN `ClickURL`
        ");
    }
}
