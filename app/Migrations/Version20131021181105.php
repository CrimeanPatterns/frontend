<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131021181105 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("create table InAppPurchase
            (InAppPurchaseID int unsigned not null auto_increment,
            StartDate datetime not null,
            UserID int not null,
            UserAgent varchar(250),
            EndDate datetime,
            PRIMARY KEY (InAppPurchaseID),
            INDEX idx_IAPUserID (UserID)
            )
            ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("drop table InAppPurchase");
    }
}
