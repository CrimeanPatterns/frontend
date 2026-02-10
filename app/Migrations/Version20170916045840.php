<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170916045840 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Cart
            add CalcDate datetime comment 'Когда была создана/пересчитана корзина, для пересчета старых корзин'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Cart 
            drop column CalcDate");
    }
}
