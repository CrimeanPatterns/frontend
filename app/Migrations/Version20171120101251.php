<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171120101251 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Cart add Source tinyint not null default 0 comment 'see Cart::SOURCE_ constants'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Cart drop Source");
    }
}
