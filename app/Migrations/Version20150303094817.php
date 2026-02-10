<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150303094817 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `Session` (
              `SessionID` varchar(32) NOT NULL DEFAULT '' COMMENT 'Идентификатор сессии',
              `UserID` int(11) NOT NULL COMMENT 'Идентификатор авторизованного юзера',
              `IP` varchar(60) NOT NULL COMMENT 'IP-адрес',
              `UserAgent` varchar(250) DEFAULT NULL COMMENT 'User agent',
              `Valid` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Если 1, то сессия верна. Если 0, то произойдет logout',
              `LoginDate` int(11) NOT NULL COMMENT 'Таймстамп логина',
              `LastActivityDate` int(11) NOT NULL COMMENT 'Таймстамп последней активности',
              PRIMARY KEY (`SessionID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `Session`");
    }
}
