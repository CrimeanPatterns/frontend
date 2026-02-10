<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161013084440 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add WpRewardsActivity tinyint not null default 1 comment 'Посылать веб-пуши об изменениях баланса программ'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop WpRewardsActivity");
    }
}
