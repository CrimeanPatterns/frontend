<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140521091304 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo
			add ImapMailbox varchar(200) comment 'Строка коннекта к imap, для сканирования ответов на букинг запросы',
			add ImapLogin varchar(80) comment 'Имя пользователя imap',
			add ImapPassword varchar(80) comment 'Пароль imap'");
        $this->addSql("update AbBookerInfo set ImapMailbox = '{imap.gmail.com:993/ssl}INBOX' where UserID = 116000");
        $this->addSql("alter table AbMessage add ImapMessageID varchar(250) comment 'заголовок Message-ID если комментарий был импортирован с почты по imap'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AbBookerInfo drop ImapMailbox, drop ImapLogin, drop ImapPassword");
        $this->addSql("alter table AbMessage drop ImapMessageID");
    }
}
