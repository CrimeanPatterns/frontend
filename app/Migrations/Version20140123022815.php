<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140123022815 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if (!$schema->getTable('UserAgent')->hasColumn('Alias')) {
            $this->addSql("alter table UserAgent add Alias varchar(80)");
        }
        $this->addSql("delete from UserAgent where AgentID not in (select UserID from Usr)");
        $this->addSql("ALTER TABLE UserAgent ADD CONSTRAINT FK_421F0DE8E3385715 FOREIGN KEY (AgentID) REFERENCES Usr (UserID) on delete cascade");
        $this->addSql("ALTER TABLE UserAgent ADD CONSTRAINT FK_421F0DE82804AB20 FOREIGN KEY (ClientID) REFERENCES Usr (UserID) on delete cascade");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table UserAgent drop Alias");
        $this->addSql("ALTER TABLE UserAgent DROP FOREIGN KEY FK_421F0DE8E3385715");
        $this->addSql("ALTER TABLE UserAgent DROP FOREIGN KEY FK_421F0DE82804AB20");
    }
}
