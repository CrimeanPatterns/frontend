<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150414072718 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider MODIFY CanCheckConfirmation int NOT NULL DEFAULT 0 COMMENT 'возможна ли проверка резерваций по Confirmation Number (значения: нет / сервер / сервер + extension / extension)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider MODIFY CanCheckConfirmation int(11) NOT NULL DEFAULT '0' COMMENT 'если возможна проверка резерваций по Confirmation Number, то нужно поставить true\n*Value*:\ntrue/false'");
    }
}
