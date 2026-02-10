<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150817151611 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			create table ProviderSupportNote(
				ProviderSupportNoteID int not null auto_increment,
				ProviderID int not null,
				DatetimeStamp datetime not null,
				Author varchar(30) not null,
				Comment text not null,
				RedmineCommentLink varchar(60) not null,
				RelatedCommits text,
				primary key(ProviderSupportNoteID),
				foreign key(ProviderID) references Provider(ProviderID) on delete cascade
			) engine=InnoDB comment 'Заметки по поддержке'
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table ProviderSupportNote");
    }
}
