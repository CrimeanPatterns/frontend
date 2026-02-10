<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140521064916 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserEmail add column MailboxType tinyint not null default 1");
        $this->addSql("alter table UserEmail add column Connected tinyint not null default 4;");
        $this->addSql("alter table UserEmail add column ErrorMessage varchar(255)");
        $this->addSql("update UserEmail set MailboxType = 2 where UseGoogleOauth = 1");
        $this->addSql("update UserEmail set Connected = case
							when Status > 1 then Status - 1
							else 1
							end");
        $this->addSql("update UserEmail set Status = 3");
        $this->addSql("alter table UserEmail drop column UseGoogleOauth");
        $this->addSql("alter table UserEmail add unique (Email)");
        $this->addSql("create table ScanHistory(
							ScanHistoryID int not null auto_increment,
							UserEmailID int not null,
							ProviderID int,
							AccountID int,
							ParsedJson text,
							Processed tinyint not null default 0,
							EmailToken varchar(128) not null,
							EmailDate datetime not null,
							foreign key (UserEmailID) references UserEmail(UserEmailID) on delete cascade on update cascade,
							foreign key (ProviderID) references Provider(ProviderID) on delete cascade on update cascade,
							foreign key (AccountID) references Account(AccountID) on delete set null on update cascade,
							primary key (ScanHistoryID)
						) engine=InnoDB");
        $this->addSql("create index idxEmailToken on ScanHistory(EmailToken)");
        $this->addSql("drop table if exists UserEmailAccountHistory ");
        $this->addSql("drop table if exists UserEmailParseHistory");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update UserEmail set Status = Connected + 1");
        $this->addSql("alter table UserEmail add column UseGoogleOauth tinyint(1) not null default 0");
        $this->addSql("update UserEmail set UseGoogleOauth = 1 where MailboxType = 2");
        $this->addSql("alter table UserEmail drop column MailboxType");
        $this->addSql("alter table UserEmail drop column Connected");
        $this->addSql("alter table UserEmail drop column ErrorMessage");
        $this->addSql("drop index Email on UserEmail");
        $this->addSql("drop table ScanHistory");
    }
}
