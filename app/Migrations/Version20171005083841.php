<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171005083841 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `Provider`
                add column `StopKeyWords` varchar(2000) default null comment 'ключевые слова, при срабатывании которых не должно учитываться срабатывание ключевиков из поля KeyWords' after `KeyWords` 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            alter table `Provider`
                drop column `StopKeyWords`
        ");
    }
}
