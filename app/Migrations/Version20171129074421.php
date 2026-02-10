<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171129074421 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
                ALTER TABLE `CreditCard`
                ADD `ClickURL` varchar(512) NOT NULL COMMENT 'Ссылка на описание кредитной карты в блоге'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` DROP COLUMN `ClickURL`
        ");
    }
}
