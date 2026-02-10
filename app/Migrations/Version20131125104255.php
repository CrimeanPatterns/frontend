<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131125104255 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `AbCustomProgram` CHANGE `Balance` `Balance` DECIMAL(15,2)  NULL  DEFAULT NULL  COMMENT 'Баланс по кастомной программе';");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `AbCustomProgram` CHANGE `Balance` `Balance` DECIMAL(15,2)  NOT NULL  DEFAULT '0.00'  COMMENT 'Баланс по кастомной программе';");
    }
}
