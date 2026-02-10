<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140627122254 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account add BackgroundCheck tinyint not null default 1");
        $this->addSql('create index idxBackgroundCheck on Account(BackgroundCheck, NextCheckPriority, QueueDate)');
        $this->addSql('drop index idxQueueDate on Account');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop index idxBackgroundCheck on Account');
        $this->addSql("alter table Account drop BackgroundCheck");
        $this->addSql('create index idxQueueDate on Account(QueueDate)');
    }
}
