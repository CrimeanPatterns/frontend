<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170106050003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add Subscription tinyint comment 'Тип подписки, константы Usr::SUBSCRIPTION_'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop Subscription");
    }
}
