<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180925063911 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbRequest` ADD `CabinPremiumEconomy` TINYINT(1)  NOT NULL  COMMENT 'Премиум эконом класс (опция)'  AFTER `CabinEconomy`");
        $this->addSql("ALTER TABLE `AbRequest` ADD `BusinessTravel` TINYINT(1)  NOT NULL  COMMENT 'business/personal travel'");
        $this->addSql("ALTER TABLE `AbBookerInfo` ADD `AllowBusinessOrPersonalSelect` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Опция - business/personal travel'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbRequest` DROP `CabinPremiumEconomy`;");
        $this->addSql("ALTER TABLE `AbRequest` DROP `BusinessTravel`;");
        $this->addSql("ALTER TABLE `AbBookerInfo` DROP `AllowBusinessOrPersonalSelect`;");
    }
}
