<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140305101255 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `AbRequest` add column `FinalServiceFee` DECIMAL(10,2) DEFAULT NULL;");
        $this->addSql("ALTER TABLE `AbRequest` add column `FinalTaxes` DECIMAL(10,2) DEFAULT NULL;");
        $this->addSql("ALTER TABLE `AbRequest` add column `FeesPaidTo` int unsigned DEFAULT NULL;");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `AbRequest` CHANGE drop column `FinalServiceFee`;");
        $this->addSql("ALTER TABLE `AbRequest` CHANGE drop column `FinalTaxes`;");
        $this->addSql("ALTER TABLE `AbRequest` CHANGE drop column `FeesPaidTo`;");
    }
}
