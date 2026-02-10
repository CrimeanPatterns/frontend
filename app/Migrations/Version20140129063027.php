<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140129063027 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
          CREATE TABLE AbPhoneNumber (
          AbPhoneNumberID int(11) unsigned NOT NULL AUTO_INCREMENT,
          Provider varchar(255) NOT NULL COMMENT 'Провайдер',
          Phone varchar(255) NOT NULL COMMENT 'Номер телефона',
          MessageID bigint(15) NOT NULL COMMENT 'Ссылка на сообщение для показа в переписке между букером и юзером',
          PRIMARY KEY (AbPhoneNumberID),
          KEY `IDX_AbPNMessageID` (`MessageID`),
          CONSTRAINT `FK_AbPNMessageID` FOREIGN KEY (`MessageID`) REFERENCES `AbMessage` (`AbMessageID`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Букер указывает номера телефонов авиалиний для юзера, написавшего букинг-запрос, для уточнения номеров мест';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table AbPhoneNumber');
    }
}
