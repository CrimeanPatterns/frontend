<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170801093426 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `Provider` 
            add column `CanParseCardImages` int(1) default 0 
            comment 'Нужно ли парсить данные с карточки' 
            after `CanMarkCoupons`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `Provider` drop column `CanParseCardImages`');
    }
}
