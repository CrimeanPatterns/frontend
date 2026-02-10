<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170325235044 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table `CustomLoyaltyProperty` modify `Value` varchar(4096) not null comment 'Значение свойства'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `CustomLoyaltyProperty` modify `Value` varchar(512) not null comment 'Значение свойства'");
    }
}
