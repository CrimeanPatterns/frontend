<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161114122155 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add wpCheckins tinyint not null default 1 comment 'Разрешить web-push уведомления о чекинах на перелет'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop wpCheckins");
    }
}
