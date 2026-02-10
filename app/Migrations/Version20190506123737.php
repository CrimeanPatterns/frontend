<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190506123737 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table BalanceWatch 
            add Status char(1) not null default 'N' comment 'admin area status, see BalanceWatch::STATUS_ constants',
            add Note varchar(500) comment 'Notes for admin area'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
