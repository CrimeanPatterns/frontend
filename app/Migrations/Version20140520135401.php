<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140520135401 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table AbRequestStatus
                      (AbRequestStatusID int unsigned not null auto_increment,
                       BookerID int unsigned not null,
                       Status varchar(255) not null,
                       PRIMARY KEY (AbRequestStatusID))
                      ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->addSql('create index BookerID on AbRequestStatus(BookerID)');
        $this->addSql('ALTER TABLE AbRequest MODIFY COLUMN InternalStatus INT UNSIGNED NULL');
        $this->addSql('update AbRequest set InternalStatus = null where InternalStatus = 0');

        $this->addSql("create table AbMessageColor
                      (AbMessageColorID int unsigned not null auto_increment,
                       BookerID int unsigned not null,
                       Color varchar(255) not null,
                       Description varchar(255) not null,
                       PRIMARY KEY (AbMessageColorID))
                      ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->addSql('create index BookerID on AbMessageColor(BookerID)');
        $this->addSql('ALTER TABLE AbMessage ADD ColorID int unsigned NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table AbRequestStatus");
        $this->addSql("drop table AbMessageColor");
        $this->addSql("ALTER TABLE AbMessage DROP ColorID");
        $this->addSql('update AbRequest set InternalStatus = 0 where InternalStatus = null');
        $this->addSql('ALTER TABLE AbRequest MODIFY COLUMN InternalStatus tinyint UNSIGNED not NULL');
    }
}
