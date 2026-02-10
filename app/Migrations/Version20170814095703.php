<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170814095703 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table `Provider` add column `CollectingRequests` int(1) not null default 0 comment 'Можно ли голосовать за эту программу на странице provider status (бывший State = PROVIDER_COLLECTING_REQUESTS (-2))' after `State`");
        $this->addSql('
            update Provider
            set 
                CollectingRequests = 1,
                State = ?
            where State = ?
        ', [
            PROVIDER_DISABLED,
            -2,
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            update Provider
            set
                State = ?
            where
                State = ? and
                CollectingRequests = 1
        ', [
            -2,
            PROVIDER_DISABLED,
        ]);

        $this->addSql('alter table `Provider` drop column `CollectingRequests`');
    }
}
