<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180925051854 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` ADD `UsCentric` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Показ поля US Citizen при создании букзапроса'");
        $this->addSql("ALTER TABLE `AbBookerInfo` ADD `ServePremiumEconomy` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Работает ли букер с Premium Economy Class'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` DROP `UsCentric`");
        $this->addSql("ALTER TABLE `AbBookerInfo` DROP `ServePremiumEconomy`");
    }
}
