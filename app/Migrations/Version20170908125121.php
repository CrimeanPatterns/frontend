<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170908125121 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table CartItem add ScheduledDate date comment 'Когда эта сумма должна быть списана'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table CartItem drop ScheduledDate");
    }
}
