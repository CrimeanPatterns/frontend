<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190328055803 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` ADD `DisplayNameFormat` varchar(250) NULL COMMENT 'Формат отображения на аккаунт листе' AFTER `Name`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` DROP COLUMN `DisplayNameFormat`");
    }
}
