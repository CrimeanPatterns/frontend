<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create table for TimelineShare entity.
 */
class Version20160204092606 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			create table `TimelineShare`(
				`TimelineShareID` int not null auto_increment,
				`UserAgentID` int not null,
				`TimelineOwnerID` int not null comment 'Владелец таймлайна',
				`FamilyMemberID` int null comment 'Фэмили мембер владельца',
				`RecipientUserID` int not null comment 'Кому был расшарен таймлайн',
				primary key(`TimelineShareID`),
				foreign key(`TimelineOwnerID`) references Usr(`UserID`) on delete cascade,
				foreign key(`UserAgentID`) references UserAgent(`UserAgentID`) on delete cascade,
				foreign key(`FamilyMemberID`) references UserAgent(`UserAgentID`) on delete cascade,
				foreign key(`RecipientUserID`) references Usr(`UserID`) on delete cascade
			) engine=InnoDB comment='Информация о расшаренных таймлайнах';
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `TimelineShare`;");
    }
}
