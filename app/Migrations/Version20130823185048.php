<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130823185048 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            create table AdIncome(
                AdIncomeID int unsigned not null auto_increment,
                PayDate date not null,
                Income decimal(19,2) not null,
                primary key(AdIncomeID)
            )
            ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("
            drop table AdIncome
        ");
    }
}
